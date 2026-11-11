<?php

namespace App\Http\Controllers;

use App\Core\Database;
use App\Services\RescueProtocolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SosRescueController extends Controller
{
    private RescueProtocolService $rescueService;

    public function __construct()
    {
        $this->rescueService = new RescueProtocolService;
    }

    public function getActiveAlerts(): JsonResponse
    {
        $alerts = Database::query("
            SELECT s.*, 
                   COALESCE(v.vehicle_number, CONCAT('Vehicle #', s.vehicle_id)) AS vehicle_number, 
                   COALESCE(v.vehicle_type, 'Refrigerated Van') AS vehicle_type,
                   COALESCE(dr.name, CONCAT('Driver #', s.driver_id)) AS driver_name, 
                   dr.phone AS driver_phone,
                   rv.vehicle_number AS rescue_vehicle_number,
                   rdr.name AS rescue_driver_name,
                   d.manifest_number,
                   d.items_json,
                   d.vanilla_load,
                   d.strawberry_load,
                   d.chocolate_load,
                   d.mango_load,
                   d.treacle_pot_load,
                   d.total_loaded_cups,
                   d.remaining_cups AS dispatch_remaining_cups
            FROM sos_alerts s
            LEFT JOIN vehicles v ON s.vehicle_id = v.id
            LEFT JOIN drivers dr ON s.driver_id = dr.id
            LEFT JOIN dispatches d ON s.dispatch_id = d.id
            LEFT JOIN vehicles rv ON s.rescue_vehicle_id = rv.id
            LEFT JOIN drivers rdr ON s.rescue_driver_id = rdr.id
            WHERE s.status IN ('PENDING_RESCUE', 'RESCUE_DISPATCHED')
            ORDER BY s.id DESC
        ");

        return response()->json(['success' => true, 'alerts' => $alerts]);
    }

    public function triggerSos(Request $request): JsonResponse
    {
        $input = $request->all();
        $dispatchId = (int) ($input['dispatchId'] ?? 0);
        $vehicleId = (int) ($input['vehicleId'] ?? 0);
        $driverId = (int) ($input['driverId'] ?? 0);
        $landmark = $input['locationLandmark'] ?? 'Near Hingurakgoda Track - Sector B';
        $reason = $input['breakdownReason'] ?? 'Refrigeration Chiller Failure';
        $temp = (float) ($input['chillerTemperatureCelsius'] ?? 11.8);

        if (! $dispatchId || ! $vehicleId || ! $driverId) {
            return response()->json(['success' => false, 'error' => 'Dispatch, Vehicle, and Driver IDs are required.'], 400);
        }

        try {
            $result = $this->rescueService->initiateBreakdownSos($dispatchId, $vehicleId, $driverId, $landmark, $reason, $temp);

            return response()->json([
                'success' => true,
                'message' => 'Emergency SOS broadcasted successfully to Front Office Command.',
                'sosTicketNumber' => $result['ticketCode'],
                'sosId' => $result['sosId'],
                'status' => 'PENDING_RESCUE',
                'remainingStockAtIncident' => $result['remainingStock'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function dispatchRescue(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $rescueVehicleId = (int) ($input['rescueVehicleId'] ?? 0);
        $rescueDriverId = (int) ($input['rescueDriverId'] ?? 0);
        $notes = $input['notes'] ?? 'Dispatched from Polonnaruwa Depot';

        try {
            $this->rescueService->dispatchRescueTeam($id, $rescueVehicleId, $rescueDriverId, $notes);

            return response()->json(['success' => true, 'message' => 'Rescue vehicle dispatched successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function transferStock(Request $request, int $id): JsonResponse
    {
        $input = $request->all();
        $goodStock = (int) ($input['transferredGoodStock'] ?? 0);
        $spoiledStock = (int) ($input['spoiledUnusableStock'] ?? 0);
        $notes = $input['handshakeNotes'] ?? 'Field verified physical count completed.';
        $itemsJson = isset($input['itemsJson']) ? (is_string($input['itemsJson']) ? $input['itemsJson'] : json_encode($input['itemsJson'])) : null;

        try {
            $res = $this->rescueService->executeStockRescueTransfer($id, $goodStock, $spoiledStock, $notes, $itemsJson);

            return response()->json([
                'success' => true,
                'message' => "Stock rescue completed! {$goodStock} cups transferred to Rescue Manifest ({$res['rescueManifestNo']}).",
                'rescueManifestNo' => $res['rescueManifestNo'],
                'transferredCups' => $res['transferredCups'] ?? $goodStock,
                'spoiledCups' => $res['spoiledCups'] ?? $spoiledStock,
                'ticketCode' => $res['ticketCode'] ?? ('SOS #'.$id),
                'resolvedAt' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
