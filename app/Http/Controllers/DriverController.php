<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Core\Database;

class DriverController extends Controller
{
    public function getActiveDispatches(): JsonResponse
    {
        $dispatches = Database::query("
            SELECT d.*, 
                   v.vehicle_number, v.cooling_system_status,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   COALESCE(sr.name, u.name, 'Field Sales Rep') AS sales_rep_name,
                   r.route_name, r.region_sector
            FROM dispatches d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            LEFT JOIN drivers dr ON d.driver_id = dr.id
            LEFT JOIN users u ON d.sales_rep_id = u.id
            LEFT JOIN sales_representatives sr ON (d.sales_rep_id = sr.id OR d.sales_rep_id = sr.user_id)
            LEFT JOIN routes r ON d.route_id = r.id
            ORDER BY d.id DESC
        ");
        return response()->json(['success' => true, 'dispatches' => $dispatches]);
    }

    public function getDispatchDetails(int $id): JsonResponse
    {
        $dispatch = Database::queryOne("
            SELECT d.*, 
                   v.vehicle_number, v.vehicle_type, v.capacity_cups, v.cooling_system_status,
                   dr.name AS driver_name, dr.phone AS driver_phone,
                   COALESCE(sr.name, u.name, 'Field Sales Rep') AS sales_rep_name,
                   r.route_name, r.region_sector
            FROM dispatches d
            LEFT JOIN vehicles v ON d.vehicle_id = v.id
            LEFT JOIN drivers dr ON d.driver_id = dr.id
            LEFT JOIN users u ON d.sales_rep_id = u.id
            LEFT JOIN sales_representatives sr ON (d.sales_rep_id = sr.id OR d.sales_rep_id = sr.user_id)
            LEFT JOIN routes r ON d.route_id = r.id
            WHERE d.id = ?
        ", [$id]);

        if (!$dispatch) {
            return response()->json(['success' => false, 'error' => 'Dispatch manifest not found.'], 404);
        }

        return response()->json(['success' => true, 'dispatch' => $dispatch]);
    }

    public function createDispatch(Request $request): JsonResponse
    {
        $input = $request->all();
        $totalLoaded = (int)($input['vanillaLoad'] ?? 0) + (int)($input['strawberryLoad'] ?? 0) + 
                       (int)($input['chocolateLoad'] ?? 0) + (int)($input['mangoLoad'] ?? 0) + 
                       (int)($input['treaclePotLoad'] ?? 0);

        if ($totalLoaded <= 0) {
            return response()->json(['success' => false, 'error' => 'Total loaded yogurt cups must be greater than zero.'], 400);
        }

        $today = date('Y-m-d');
        $manifestNo = 'MNF-' . date('Ymd') . '-' . rand(100, 999);
        $salesRepId = !empty($input['salesRepId']) ? (int)$input['salesRepId'] : 4;

        $id = Database::execute("
            INSERT INTO dispatches (
                manifest_number, dispatch_date, vehicle_id, driver_id, sales_rep_id, route_id,
                vanilla_load, strawberry_load, chocolate_load, mango_load, treacle_pot_load,
                total_loaded_cups, remaining_cups, sold_cups, damaged_cups, status, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 0, 'IN_TRANSIT', ?)
        ", [
            $manifestNo, $today, $input['vehicleId'], $input['driverId'], $salesRepId, $input['routeId'],
            $input['vanillaLoad'] ?? 0, $input['strawberryLoad'] ?? 0, $input['chocolateLoad'] ?? 0, $input['mangoLoad'] ?? 0, $input['treaclePotLoad'] ?? 0,
            $totalLoaded, $totalLoaded, $input['notes'] ?? 'Morning Load Dispatch'
        ]);

        Database::execute("UPDATE vehicles SET status = 'IN_TRANSIT' WHERE id = ?", [$input['vehicleId']]);
        Database::execute("UPDATE drivers SET status = 'ON_DELIVERY' WHERE id = ?", [$input['driverId']]);

        return response()->json([
            'success' => true,
            'message' => 'Morning dispatch manifest created successfully.',
            'manifestNumber' => $manifestNo,
            'dispatchId' => $id
        ]);
    }

    public function getVehicles(): JsonResponse
    {
        $vehicles = Database::query("
            SELECT v.*, d.name AS driver_name, r.route_name 
            FROM vehicles v
            LEFT JOIN drivers d ON v.current_driver_id = d.id
            LEFT JOIN routes r ON v.current_route_id = r.id
            ORDER BY v.id ASC
        ");
        return response()->json(['success' => true, 'vehicles' => $vehicles]);
    }

    public function getDrivers(): JsonResponse
    {
        $drivers = Database::query("
            SELECT d.*, v.vehicle_number 
            FROM drivers d
            LEFT JOIN vehicles v ON d.assigned_vehicle_id = v.id
            ORDER BY d.id ASC
        ");
        return response()->json(['success' => true, 'drivers' => $drivers]);
    }

    public function getRoutes(): JsonResponse
    {
        $routes = Database::query("
            SELECT r.*, v.vehicle_number, COUNT(s.id) AS shop_count
            FROM routes r
            LEFT JOIN vehicles v ON r.assigned_vehicle_id = v.id
            LEFT JOIN shops s ON s.route_id = r.id
            GROUP BY r.id
            ORDER BY r.id ASC
        ");
        return response()->json(['success' => true, 'routes' => $routes]);
    }

    public function getProducts(): JsonResponse
    {
        $products = Database::query("SELECT * FROM products ORDER BY id ASC");
        return response()->json(['success' => true, 'products' => $products]);
    }
}
