<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Core\Database;

/**
 * Dispatch Manifest Model (Eloquent)
 */
class Dispatch extends Model
{
    protected $table = 'dispatches';
    public $timestamps = false;
    protected $guarded = [];

    public static function getActive(): array
    {
        return Database::query("
            SELECT d.*, 
                   v.vehicle_number, v.cooling_system_status,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   u.full_name AS sales_rep_name,
                   r.route_name, r.region_sector
            FROM dispatches d
            JOIN vehicles v ON d.vehicle_id = v.id
            JOIN drivers dr ON d.driver_id = dr.id
            JOIN users u ON d.sales_rep_id = u.id
            JOIN routes r ON d.route_id = r.id
            ORDER BY d.id DESC
        ");
    }

    public static function findRecord(int $id): ?array
    {
        return Database::queryOne("SELECT * FROM dispatches WHERE id = ?", [$id]);
    }

    public static function hasSufficientStock(array $dispatch, int $requestedCups): bool
    {
        return ((int)$dispatch['remaining_cups'] >= $requestedCups);
    }

    public static function decrementStock(int $dispatchId, int $cupsSold): array
    {
        $dispatch = self::findRecord($dispatchId);
        $newRemaining = (int)($dispatch['remaining_cups'] ?? 0) - $cupsSold;
        $newSold = (int)($dispatch['sold_cups'] ?? 0) + $cupsSold;

        Database::execute(
            "UPDATE dispatches SET remaining_cups = ?, sold_cups = ? WHERE id = ?",
            [$newRemaining, $newSold, $dispatchId]
        );

        return [
            'remainingStock' => $newRemaining,
            'soldStock' => $newSold
        ];
    }

    public static function createManifest(array $data): int
    {
        $today = date('Y-m-d');
        $manifestNo = 'MNF-' . date('Ymd') . '-' . rand(100, 999);
        $totalLoaded = (int)($data['vanillaLoad'] ?? 0) + (int)($data['strawberryLoad'] ?? 0) + 
                       (int)($data['chocolateLoad'] ?? 0) + (int)($data['mangoLoad'] ?? 0) + 
                       (int)($data['treaclePotLoad'] ?? 0);

        return Database::execute("
            INSERT INTO dispatches (
                manifest_number, dispatch_date, vehicle_id, driver_id, sales_rep_id, route_id,
                vanilla_load, strawberry_load, chocolate_load, mango_load, treacle_pot_load,
                total_loaded_cups, remaining_cups, sold_cups, damaged_cups, status, notes
            ) VALUES (?, ?, ?, ?, 4, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'IN_TRANSIT', ?)
        ", [
            $manifestNo, $today, $data['vehicleId'], $data['driverId'], $data['routeId'],
            $data['vanillaLoad'] ?? 0, $data['strawberryLoad'] ?? 0, $data['chocolateLoad'] ?? 0, $data['mangoLoad'] ?? 0, $data['treaclePotLoad'] ?? 0,
            $totalLoaded, $totalLoaded, $data['notes'] ?? 'Morning Load Manifest'
        ]);
    }

    public static function setStatus(int $id, string $status): void
    {
        Database::execute("UPDATE dispatches SET status = ? WHERE id = ?", [$status, $id]);
    }

    public static function zeroOutForRescue(int $id, int $damagedCups): void
    {
        Database::execute(
            "UPDATE dispatches SET remaining_cups = 0, status = 'COMPLETED', damaged_cups = damaged_cups + ? WHERE id = ?",
            [$damagedCups, $id]
        );
    }
}
