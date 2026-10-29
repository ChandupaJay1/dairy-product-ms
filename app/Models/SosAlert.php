<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Database;

/**
 * SOS Emergency Alert Model (Eloquent)
 */
class SosAlert extends Model
{
    protected $table = 'sos_alerts';
    public $timestamps = false;
    protected $guarded = [];

    public static function getActive(): array
    {
        return Database::query("
            SELECT s.*, 
                   v.vehicle_number, v.vehicle_type,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   rv.vehicle_number AS rescue_vehicle_number,
                   rdr.name AS rescue_driver_name,
                   d.manifest_number
            FROM sos_alerts s
            JOIN vehicles v ON s.vehicle_id = v.id
            JOIN drivers dr ON s.driver_id = dr.id
            JOIN dispatches d ON s.dispatch_id = d.id
            LEFT JOIN vehicles rv ON s.rescue_vehicle_id = rv.id
            LEFT JOIN drivers rdr ON s.rescue_driver_id = rdr.id
            WHERE s.status IN ('PENDING_RESCUE', 'RESCUE_DISPATCHED')
            ORDER BY s.id DESC
        ");
    }

    public static function findRecord(int $id): ?array
    {
        return Database::queryOne("SELECT * FROM sos_alerts WHERE id = ?", [$id]);
    }

    public static function createTicket(array $data): int
    {
        return Database::execute("
            INSERT INTO sos_alerts (
                sos_ticket_number, dispatch_id, vehicle_id, driver_id,
                latitude, longitude, location_landmark, breakdown_reason,
                chiller_temperature_celsius, remaining_stock_at_incident, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING_RESCUE')
        ", [
            $data['ticketNumber'], $data['dispatchId'], $data['vehicleId'], $data['driverId'],
            $data['latitude'] ?? 7.9403, $data['longitude'] ?? 81.0188,
            $data['locationLandmark'], $data['breakdownReason'],
            $data['chillerTemperature'] ?? 11.8, $data['remainingStock']
        ]);
    }

    public static function assignRescue(int $id, int $rescueVehicleId, int $rescueDriverId, string $notes): void
    {
        Database::execute(
            "UPDATE sos_alerts SET status = 'RESCUE_DISPATCHED', rescue_vehicle_id = ?, rescue_driver_id = ?, rescue_handshake_notes = ? WHERE id = ?",
            [$rescueVehicleId, $rescueDriverId, $notes, $id]
        );
    }

    public static function resolve(int $id, int $goodStock, int $spoiledStock, string $notes): void
    {
        $existing = Database::queryOne("SELECT rescue_handshake_notes FROM sos_alerts WHERE id = ?", [$id]);
        $combinedNotes = ($existing['rescue_handshake_notes'] ?? '') . ' | ' . $notes;

        Database::execute("
            UPDATE sos_alerts 
            SET status = 'RESOLVED',
                transferred_good_stock = ?,
                spoiled_unusable_stock = ?,
                rescue_handshake_notes = ?,
                resolved_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ", [$goodStock, $spoiledStock, $combinedNotes, $id]);
    }
}
