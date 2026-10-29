<?php

namespace App\Services;

use App\Core\Database;

/**
 * Stock Rescue Protocol Service
 * 
 * Implements Section 4 of the proposal document:
 * Orchestrates vehicle breakdown emergency broadcast, rescue vehicle dispatch,
 * verified physical count handshake, and atomic inventory manifest transfer.
 */
class RescueProtocolService
{
    private CryptoSealService $cryptoService;

    public function __construct()
    {
        $this->cryptoService = new CryptoSealService();
    }

    /**
     * Broadcasts an emergency breakdown alert from the driver mobile app.
     */
    public function initiateBreakdownSos(int $dispatchId, int $vehicleId, int $driverId, string $landmark, string $reason, float $temperature): array
    {
        $dispatch = Database::queryOne("SELECT remaining_cups FROM dispatches WHERE id = ?", [$dispatchId]);
        $remainingStock = $dispatch ? (int)$dispatch['remaining_cups'] : 0;
        $ticketCode = 'SOS-' . date('Ymd') . '-' . rand(100, 999);

        Database::beginTransaction();
        try {
            $sosId = Database::execute("
                INSERT INTO sos_alerts (
                    sos_ticket_number, dispatch_id, vehicle_id, driver_id,
                    latitude, longitude, location_landmark, breakdown_reason,
                    chiller_temperature_celsius, remaining_stock_at_incident, status
                ) VALUES (?, ?, ?, ?, 7.9403, 81.0188, ?, ?, ?, ?, 'PENDING_RESCUE')
            ", [
                $ticketCode, $dispatchId, $vehicleId, $driverId,
                $landmark, $reason, $temperature, $remainingStock
            ]);

            // Flag vehicle as BREAKDOWN & chiller fault
            Database::execute("UPDATE vehicles SET status = 'BREAKDOWN', cooling_system_status = 'FAIL_CHILLER' WHERE id = ?", [$vehicleId]);
            Database::execute("UPDATE dispatches SET status = 'SOS_BREAKDOWN' WHERE id = ?", [$dispatchId]);

            Database::commit();

            $this->cryptoService->logAuditEvent(
                'SOS_BREAKDOWN_TRIGGERED_OOP',
                'Field Fleet Driver',
                'driver',
                "SOS Ticket {$ticketCode}",
                ['sosId' => $sosId, 'vehicleId' => $vehicleId, 'remainingStock' => $remainingStock]
            );

            return [
                'sosId' => $sosId,
                'ticketCode' => $ticketCode,
                'remainingStock' => $remainingStock
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Dispatches standby rescue vehicle & driver from Front Office Command.
     */
    public function dispatchRescueTeam(int $sosId, int $rescueVehicleId, int $rescueDriverId, string $notes): void
    {
        Database::beginTransaction();
        try {
            Database::execute(
                "UPDATE sos_alerts SET status = 'RESCUE_DISPATCHED', rescue_vehicle_id = ?, rescue_driver_id = ?, rescue_handshake_notes = ? WHERE id = ?",
                [$rescueVehicleId, $rescueDriverId, $notes, $sosId]
            );

            Database::execute("UPDATE vehicles SET status = 'IN_TRANSIT' WHERE id = ?", [$rescueVehicleId]);
            Database::execute("UPDATE drivers SET status = 'ON_DELIVERY' WHERE id = ?", [$rescueDriverId]);

            Database::commit();

            $this->cryptoService->logAuditEvent(
                'RESCUE_TEAM_DISPATCHED_OOP',
                'Front Office Admin',
                'admin',
                "SOS #{$sosId}",
                ['rescueVehicleId' => $rescueVehicleId, 'rescueDriverId' => $rescueDriverId]
            );
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }

    /**
     * Completes dual-driver handshake with verified physical count & atomic inventory transfer.
     */
    public function executeStockRescueTransfer(int $sosId, int $goodStock, int $spoiledStock, string $notes): array
    {
        $alert = Database::queryOne("SELECT * FROM sos_alerts WHERE id = ?", [$sosId]);
        if (!$alert) {
            throw new \Exception('SOS record not found.');
        }

        // Fetch original stranded dispatch for route and sales rep continuity
        $originalDispatch = Database::queryOne("SELECT * FROM dispatches WHERE id = ?", [$alert['dispatch_id']]);
        $routeId = $originalDispatch ? (int)$originalDispatch['route_id'] : 1;
        $salesRepId = $originalDispatch ? (int)$originalDispatch['sales_rep_id'] : 4;

        Database::beginTransaction();
        try {
            // 1. Resolve SOS ticket
            Database::execute("
                UPDATE sos_alerts 
                SET status = 'RESOLVED',
                    transferred_good_stock = ?,
                    spoiled_unusable_stock = ?,
                    rescue_handshake_notes = CONCAT(COALESCE(rescue_handshake_notes, ''), ' | ', ?),
                    resolved_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ", [$goodStock, $spoiledStock, $notes, $sosId]);

            // 2. Zero-out stranded manifest
            Database::execute(
                "UPDATE dispatches SET remaining_cups = 0, status = 'COMPLETED', damaged_cups = damaged_cups + ? WHERE id = ?",
                [$spoiledStock, $alert['dispatch_id']]
            );

            // 3. Create fresh manifest for rescue vehicle
            $rescueManifestNo = 'MNF-RESCUE-' . date('Ymd') . '-' . rand(100, 999);
            Database::execute("
                INSERT INTO dispatches (
                    manifest_number, dispatch_date, vehicle_id, driver_id, sales_rep_id, route_id,
                    total_loaded_cups, remaining_cups, sold_cups, damaged_cups, status, notes
                ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, 0, 0, 'IN_TRANSIT', ?)
            ", [
                $rescueManifestNo, $alert['rescue_vehicle_id'], $alert['rescue_driver_id'],
                $salesRepId, $routeId,
                $goodStock, $goodStock, "Rescued inventory from Ticket {$alert['sos_ticket_number']}"
            ]);

            // 4. Update rescue vehicle & driver states
            if (!empty($alert['rescue_vehicle_id'])) {
                Database::execute("UPDATE vehicles SET status = 'IN_TRANSIT', cooling_system_status = 'NORMAL_COOLING' WHERE id = ?", [$alert['rescue_vehicle_id']]);
            }
            if (!empty($alert['rescue_driver_id'])) {
                Database::execute("UPDATE drivers SET status = 'ON_DELIVERY' WHERE id = ?", [$alert['rescue_driver_id']]);
            }

            Database::commit();

            $this->cryptoService->logAuditEvent(
                'STOCK_RESCUE_HANDSHAKE_COMPLETED_OOP',
                'Dual Field Drivers',
                'driver',
                "Ticket {$alert['sos_ticket_number']}",
                ['goodStock' => $goodStock, 'spoiledStock' => $spoiledStock, 'rescueManifestNo' => $rescueManifestNo]
            );

            return [
                'rescueManifestNo' => $rescueManifestNo,
                'transferredCups' => $goodStock,
                'spoiledCups' => $spoiledStock,
                'ticketCode' => $alert['sos_ticket_number']
            ];
        } catch (\Exception $e) {
            Database::rollBack();
            throw $e;
        }
    }
}
