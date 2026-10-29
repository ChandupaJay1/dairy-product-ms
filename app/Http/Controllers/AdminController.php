<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Core\Database;
use App\Services\CryptoSealService;

class AdminController extends Controller
{
    private CryptoSealService $cryptoService;

    public function __construct()
    {
        $this->cryptoService = new CryptoSealService();
    }

    public function getDashboardStats(): JsonResponse
    {
        $today = date('Y-m-d');
        $dispatchStats = Database::queryOne("
            SELECT 
                COALESCE(SUM(total_loaded_cups), 0) AS total_loaded,
                COALESCE(SUM(remaining_cups), 0) AS total_remaining,
                COALESCE(SUM(sold_cups), 0) AS total_sold,
                COALESCE(SUM(damaged_cups), 0) AS total_damaged
            FROM dispatches
            WHERE dispatch_date = ?
        ", [$today]) ?? ['total_loaded' => 0, 'total_remaining' => 0, 'total_sold' => 0, 'total_damaged' => 0];

        $sosStats = Database::queryOne("SELECT COUNT(*) AS active_sos_count FROM sos_alerts WHERE status IN ('PENDING_RESCUE', 'RESCUE_DISPATCHED')");
        $fleetStats = Database::queryOne("
            SELECT 
                COUNT(*) AS total_vehicles,
                SUM(CASE WHEN status = 'IN_TRANSIT' THEN 1 ELSE 0 END) AS vehicles_on_road,
                SUM(CASE WHEN status = 'BREAKDOWN' THEN 1 ELSE 0 END) AS vehicles_in_breakdown,
                SUM(CASE WHEN status = 'AVAILABLE' THEN 1 ELSE 0 END) AS vehicles_standby
            FROM vehicles
        ");
        $creditStats = Database::queryOne("
            SELECT 
                COALESCE(SUM(outstanding_balance), 0) AS total_credit_exposure,
                COALESCE(SUM(credit_limit), 0) AS total_credit_limit,
                SUM(CASE WHEN overdue_days > 7 THEN 1 ELSE 0 END) AS high_risk_shops_count,
                COUNT(*) AS total_shops
            FROM shops
        ");

        return response()->json([
            'success' => true,
            'stats' => [
                'totalLoadedToday' => (int)($dispatchStats['total_loaded'] ?? 0),
                'totalRemainingToday' => (int)($dispatchStats['total_remaining'] ?? 0),
                'totalSoldToday' => (int)($dispatchStats['total_sold'] ?? 0),
                'totalDamagedToday' => (int)($dispatchStats['total_damaged'] ?? 0),
                'activeSosCount' => (int)($sosStats['active_sos_count'] ?? 0),
                'fleet' => [
                    'total' => (int)($fleetStats['total_vehicles'] ?? 0),
                    'onRoad' => (int)($fleetStats['vehicles_on_road'] ?? 0),
                    'breakdown' => (int)($fleetStats['vehicles_in_breakdown'] ?? 0),
                    'standby' => (int)($fleetStats['vehicles_standby'] ?? 0)
                ],
                'financials' => [
                    'totalCreditExposure' => (float)($creditStats['total_credit_exposure'] ?? 0),
                    'totalCreditLimit' => (float)($creditStats['total_credit_limit'] ?? 0),
                    'highRiskShopsCount' => (int)($creditStats['high_risk_shops_count'] ?? 0),
                    'totalShops' => (int)($creditStats['total_shops'] ?? 0)
                ]
            ]
        ]);
    }

    public function getAuditLogs(): JsonResponse
    {
        $logs = Database::query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 50");
        return response()->json(['success' => true, 'logs' => $logs]);
    }

    public function rotateFleet(Request $request): JsonResponse
    {
        $vehicleId = (int)$request->input('vehicleId', 0);
        $driverId = (int)$request->input('driverId', 0);
        $routeId = (int)$request->input('routeId', 0);

        if (!$vehicleId || !$driverId || !$routeId) {
            return response()->json(['success' => false, 'error' => 'Vehicle, Driver, and Route IDs are required.'], 400);
        }

        Database::execute("UPDATE vehicles SET current_driver_id = ?, current_route_id = ?, status = 'ASSIGNED' WHERE id = ?", [$driverId, $routeId, $vehicleId]);
        Database::execute("UPDATE drivers SET assigned_vehicle_id = ?, status = 'ON_DELIVERY' WHERE id = ?", [$vehicleId, $driverId]);
        Database::execute("UPDATE routes SET assigned_vehicle_id = ? WHERE id = ?", [$vehicleId, $routeId]);

        $this->cryptoService->logAuditEvent('FLEET_DYNAMIC_ROTATION_OOP', 'Admin Supervisor', 'admin', "Vehicle #{$vehicleId}", $request->all());

        return response()->json(['success' => true, 'message' => 'Dynamic fleet rotation assignment saved.']);
    }

    // ========================================================
    // 1. VEHICLE REGISTRATION & MANAGEMENT
    // ========================================================
    public function createVehicle(Request $request): JsonResponse
    {
        $vehicleNumber = strtoupper(trim((string)$request->input('vehicle_number', '')));
        $vehicleType = trim((string)$request->input('vehicle_type', 'Refrigerated Cold-Truck 3.5T'));
        $capacityCups = (int)$request->input('capacity_cups', 2000);
        $coolingStatus = trim((string)$request->input('cooling_system_status', 'NORMAL_COOLING'));
        $status = trim((string)$request->input('status', 'AVAILABLE'));

        if (empty($vehicleNumber)) {
            return response()->json(['success' => false, 'error' => 'Vehicle registration number is required (e.g. NC-GA-4521).'], 422);
        }

        $existing = Database::queryOne("SELECT id FROM vehicles WHERE vehicle_number = ?", [$vehicleNumber]);
        if ($existing) {
            return response()->json(['success' => false, 'error' => "Vehicle number '{$vehicleNumber}' is already registered."], 422);
        }

        $id = Database::execute("
            INSERT INTO vehicles (vehicle_number, vehicle_type, capacity_cups, cooling_system_status, status, last_service_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$vehicleNumber, $vehicleType, $capacityCups, $coolingStatus, $status, date('Y-m-d')]);

        $this->cryptoService->logAuditEvent('VEHICLE_REGISTERED', 'Admin Supervisor', 'admin', "Vehicle {$vehicleNumber}", [
            'id' => $id, 'vehicle_number' => $vehicleNumber, 'capacity_cups' => $capacityCups
        ]);

        return response()->json([
            'success' => true,
            'message' => "Vehicle {$vehicleNumber} registered successfully into fleet inventory.",
            'vehicleId' => $id
        ]);
    }

    public function updateVehicle(Request $request, int $id): JsonResponse
    {
        $vehicle = Database::queryOne("SELECT * FROM vehicles WHERE id = ?", [$id]);
        if (!$vehicle) {
            return response()->json(['success' => false, 'error' => 'Vehicle not found.'], 404);
        }

        $vehicleType = $request->input('vehicle_type', $vehicle['vehicle_type']);
        $capacityCups = (int)$request->input('capacity_cups', $vehicle['capacity_cups']);
        $coolingStatus = $request->input('cooling_system_status', $vehicle['cooling_system_status']);
        $status = $request->input('status', $vehicle['status']);

        Database::execute("
            UPDATE vehicles 
            SET vehicle_type = ?, capacity_cups = ?, cooling_system_status = ?, status = ?
            WHERE id = ?
        ", [$vehicleType, $capacityCups, $coolingStatus, $status, $id]);

        $this->cryptoService->logAuditEvent('VEHICLE_UPDATED', 'Admin Supervisor', 'admin', "Vehicle #{$id}", $request->all());

        return response()->json(['success' => true, 'message' => "Vehicle #{$vehicle['vehicle_number']} updated successfully."]);
    }

    public function deleteVehicle(int $id): JsonResponse
    {
        $vehicle = Database::queryOne("SELECT * FROM vehicles WHERE id = ?", [$id]);
        if (!$vehicle) {
            return response()->json(['success' => false, 'error' => 'Vehicle not found.'], 404);
        }

        Database::execute("DELETE FROM vehicles WHERE id = ?", [$id]);
        $this->cryptoService->logAuditEvent('VEHICLE_DELETED', 'Admin Supervisor', 'admin', "Vehicle #{$vehicle['vehicle_number']}", []);

        return response()->json(['success' => true, 'message' => "Vehicle {$vehicle['vehicle_number']} removed from fleet."]);
    }

    // ========================================================
    // 2. DRIVER REGISTRATION & MANAGEMENT
    // ========================================================
    public function createDriver(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));
        $nic = trim((string)$request->input('nic_number', ''));
        $license = trim((string)$request->input('license_number', ''));
        $phone = trim((string)$request->input('phone', ''));
        $vehicleId = $request->input('assigned_vehicle_id') ? (int)$request->input('assigned_vehicle_id') : null;
        $status = trim((string)$request->input('status', 'ACTIVE'));

        if (empty($name) || empty($phone)) {
            return response()->json(['success' => false, 'error' => 'Driver full name and phone number are required.'], 422);
        }

        $id = Database::execute("
            INSERT INTO drivers (name, nic_number, license_number, phone, assigned_vehicle_id, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ", [$name, $nic ?: null, $license ?: 'B-' . rand(100000, 999999), $phone, $vehicleId, $status]);

        // Auto-provision user account if email provided
        $email = trim((string)$request->input('email', ''));
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $existingUser = \App\Models\User::where('email', $email)->first();
            if (!$existingUser) {
                \App\Models\User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->input('password', 'password')),
                    'role' => \App\Models\User::ROLE_DRIVER,
                    'phone' => $phone
                ]);
            }
        }

        $this->cryptoService->logAuditEvent('DRIVER_REGISTERED', 'Admin Supervisor', 'admin', "Driver {$name}", [
            'id' => $id, 'name' => $name, 'phone' => $phone
        ]);

        return response()->json([
            'success' => true,
            'message' => "Driver {$name} registered successfully.",
            'driverId' => $id
        ]);
    }

    public function updateDriver(Request $request, int $id): JsonResponse
    {
        $driver = Database::queryOne("SELECT * FROM drivers WHERE id = ?", [$id]);
        if (!$driver) {
            return response()->json(['success' => false, 'error' => 'Driver not found.'], 404);
        }

        $name = trim((string)$request->input('name', $driver['name']));
        $nic = trim((string)$request->input('nic_number', $driver['nic_number']));
        $license = trim((string)$request->input('license_number', $driver['license_number']));
        $phone = trim((string)$request->input('phone', $driver['phone']));
        $vehicleId = $request->has('assigned_vehicle_id') ? (int)$request->input('assigned_vehicle_id') : $driver['assigned_vehicle_id'];
        $status = trim((string)$request->input('status', $driver['status']));

        Database::execute("
            UPDATE drivers 
            SET name = ?, nic_number = ?, license_number = ?, phone = ?, assigned_vehicle_id = ?, status = ?
            WHERE id = ?
        ", [$name, $nic, $license, $phone, $vehicleId ?: null, $status, $id]);

        $this->cryptoService->logAuditEvent('DRIVER_UPDATED', 'Admin Supervisor', 'admin', "Driver #{$id}", $request->all());

        return response()->json(['success' => true, 'message' => "Driver {$name} updated successfully."]);
    }

    public function deleteDriver(int $id): JsonResponse
    {
        $driver = Database::queryOne("SELECT * FROM drivers WHERE id = ?", [$id]);
        if (!$driver) {
            return response()->json(['success' => false, 'error' => 'Driver not found.'], 404);
        }

        Database::execute("DELETE FROM drivers WHERE id = ?", [$id]);
        $this->cryptoService->logAuditEvent('DRIVER_DELETED', 'Admin Supervisor', 'admin', "Driver #{$driver['name']}", []);

        return response()->json(['success' => true, 'message' => "Driver {$driver['name']} deleted."]);
    }

    // ========================================================
    // 3. SALES REPRESENTATIVE REGISTRATION & MANAGEMENT
    // ========================================================
    public function getSalesReps(): JsonResponse
    {
        $reps = Database::query("
            SELECT r.*, u.email 
            FROM sales_representatives r
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.id ASC
        ");
        return response()->json(['success' => true, 'reps' => $reps]);
    }

    public function createSalesRep(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));
        $code = strtoupper(trim((string)$request->input('employee_code', '')));
        $phone = trim((string)$request->input('phone', ''));
        $sector = trim((string)$request->input('assigned_route_sector', 'Polonnaruwa Sector'));
        $commission = (float)$request->input('commission_rate_percent', 5.00);
        $status = trim((string)$request->input('status', 'ACTIVE'));
        $email = trim((string)$request->input('email', ''));

        if (empty($name) || empty($phone)) {
            return response()->json(['success' => false, 'error' => 'Representative name and phone number are required.'], 422);
        }

        if (empty($code)) {
            $code = 'REP-' . rand(100, 999);
        }

        $existing = Database::queryOne("SELECT id FROM sales_representatives WHERE employee_code = ?", [$code]);
        if ($existing) {
            return response()->json(['success' => false, 'error' => "Employee code '{$code}' already exists."], 422);
        }

        $userId = null;
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $existingUser = \App\Models\User::where('email', $email)->first();
            if (!$existingUser) {
                $user = \App\Models\User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => \Illuminate\Support\Facades\Hash::make($request->input('password', 'password')),
                    'role' => \App\Models\User::ROLE_SALES_REP,
                    'phone' => $phone
                ]);
                $userId = $user->id;
            } else {
                $userId = $existingUser->id;
            }
        }

        $id = Database::execute("
            INSERT INTO sales_representatives (user_id, employee_code, name, phone, assigned_route_sector, commission_rate_percent, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ", [$userId, $code, $name, $phone, $sector, $commission, $status]);

        $this->cryptoService->logAuditEvent('SALES_REP_REGISTERED', 'Admin Supervisor', 'admin', "Representative {$name} ({$code})", [
            'id' => $id, 'name' => $name, 'employee_code' => $code
        ]);

        return response()->json([
            'success' => true,
            'message' => "Sales Representative {$name} ({$code}) registered successfully.",
            'repId' => $id
        ]);
    }

    public function updateSalesRep(Request $request, int $id): JsonResponse
    {
        $rep = Database::queryOne("SELECT * FROM sales_representatives WHERE id = ?", [$id]);
        if (!$rep) {
            return response()->json(['success' => false, 'error' => 'Sales representative not found.'], 404);
        }

        $name = trim((string)$request->input('name', $rep['name']));
        $phone = trim((string)$request->input('phone', $rep['phone']));
        $sector = trim((string)$request->input('assigned_route_sector', $rep['assigned_route_sector']));
        $commission = (float)$request->input('commission_rate_percent', $rep['commission_rate_percent']);
        $status = trim((string)$request->input('status', $rep['status']));

        Database::execute("
            UPDATE sales_representatives
            SET name = ?, phone = ?, assigned_route_sector = ?, commission_rate_percent = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ", [$name, $phone, $sector, $commission, $status, $id]);

        $this->cryptoService->logAuditEvent('SALES_REP_UPDATED', 'Admin Supervisor', 'admin', "Representative #{$id}", $request->all());

        return response()->json(['success' => true, 'message' => "Sales Representative {$name} updated successfully."]);
    }

    public function deleteSalesRep(int $id): JsonResponse
    {
        $rep = Database::queryOne("SELECT * FROM sales_representatives WHERE id = ?", [$id]);
        if (!$rep) {
            return response()->json(['success' => false, 'error' => 'Sales representative not found.'], 404);
        }

        Database::execute("DELETE FROM sales_representatives WHERE id = ?", [$id]);
        $this->cryptoService->logAuditEvent('SALES_REP_DELETED', 'Admin Supervisor', 'admin', "Representative {$rep['name']}", []);

        return response()->json(['success' => true, 'message' => "Sales Representative {$rep['name']} deleted."]);
    }

    // ========================================================
    // 4. SHOP CREDIT & TIER MANAGEMENT
    // ========================================================
    public function updateShopCredit(Request $request, int $id): JsonResponse
    {
        $shop = Database::queryOne("SELECT * FROM shops WHERE id = ?", [$id]);
        if (!$shop) {
            return response()->json(['success' => false, 'error' => 'Shop not found.'], 404);
        }

        $creditTier = trim((string)$request->input('credit_tier', $shop['credit_tier']));
        $creditLimit = (float)$request->input('credit_limit', $shop['credit_limit']);

        $allowedTiers = ['Tier A', 'Tier B', 'Tier C', 'Cash Only'];
        if (!in_array($creditTier, $allowedTiers, true)) {
            $creditTier = 'Tier C';
        }

        Database::execute("
            UPDATE shops 
            SET credit_tier = ?, credit_limit = ?
            WHERE id = ?
        ", [$creditTier, $creditLimit, $id]);

        $this->cryptoService->logAuditEvent(
            'SHOP_CREDIT_ADJUSTED_BY_ADMIN',
            'Front Office Admin',
            'admin',
            "Shop #{$id} ({$shop['name']})",
            [
                'shopId' => $id,
                'previousTier' => $shop['credit_tier'],
                'newTier' => $creditTier,
                'previousLimit' => $shop['credit_limit'],
                'newLimit' => $creditLimit
            ]
        );

        return response()->json([
            'success' => true,
            'message' => "Credit terms for '{$shop['name']}' updated to {$creditTier} (Limit: Rs. " . number_format($creditLimit, 2) . ")."
        ]);
    }
}

