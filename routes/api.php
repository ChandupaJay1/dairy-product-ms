<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SosRescueController;
use App\Http\Controllers\OperationsController;
use App\Http\Controllers\ApprovalController;

/*
|--------------------------------------------------------------------------
| PulseERP Dairy Distribution API Routes
|--------------------------------------------------------------------------
*/

// 1. Health & System Status
Route::get('/health', function () {
    return response()->json([
        'status' => 'ONLINE',
        'system' => 'PulseERP Polonnaruwa Dairy Fleet Operations',
        'timestamp' => date('c')
    ]);
});

// 2. Admin & Dashboard Stats
Route::get('/audit/dashboard-stats', [AdminController::class, 'getDashboardStats']);
Route::get('/admin/dashboard-stats', [AdminController::class, 'getDashboardStats']);
Route::get('/audit/logs', [AdminController::class, 'getAuditLogs']);
Route::post('/fleet/rotate', [AdminController::class, 'rotateFleet']);

// 3. Fleet & Staff Registration Management (Drivers, Reps, Vehicles)
Route::get('/admin/sales-reps', [AdminController::class, 'getSalesReps']);
Route::post('/admin/sales-reps', [AdminController::class, 'createSalesRep']);
Route::put('/admin/sales-reps/{id}', [AdminController::class, 'updateSalesRep']);
Route::delete('/admin/sales-reps/{id}', [AdminController::class, 'deleteSalesRep']);

Route::post('/admin/vehicles', [AdminController::class, 'createVehicle']);
Route::put('/admin/vehicles/{id}', [AdminController::class, 'updateVehicle']);
Route::delete('/admin/vehicles/{id}', [AdminController::class, 'deleteVehicle']);

Route::post('/admin/drivers', [AdminController::class, 'createDriver']);
Route::put('/admin/drivers/{id}', [AdminController::class, 'updateDriver']);
Route::delete('/admin/drivers/{id}', [AdminController::class, 'deleteDriver']);

Route::get('/fleet/reps', [AdminController::class, 'getSalesReps']);
Route::post('/fleet/reps/create', [AdminController::class, 'createSalesRep']);
Route::post('/fleet/reps/{id}/update', [AdminController::class, 'updateSalesRep']);
Route::post('/fleet/reps/{id}/delete', [AdminController::class, 'deleteSalesRep']);

Route::post('/fleet/vehicles/create', [AdminController::class, 'createVehicle']);
Route::post('/fleet/vehicles/{id}/update', [AdminController::class, 'updateVehicle']);
Route::post('/fleet/vehicles/{id}/delete', [AdminController::class, 'deleteVehicle']);

Route::post('/fleet/drivers/create', [AdminController::class, 'createDriver']);
Route::post('/fleet/drivers/{id}/update', [AdminController::class, 'updateDriver']);
Route::post('/fleet/drivers/{id}/delete', [AdminController::class, 'deleteDriver']);

// 4. Driver & Fleet Manifests
Route::get('/dispatches/active', [DriverController::class, 'getActiveDispatches']);
Route::get('/dispatches/{id}', [DriverController::class, 'getDispatchDetails']);
Route::post('/dispatches/create', [DriverController::class, 'createDispatch']);
Route::get('/fleet/vehicles', [DriverController::class, 'getVehicles']);
Route::get('/fleet/drivers', [DriverController::class, 'getDrivers']);
Route::get('/fleet/routes', [DriverController::class, 'getRoutes']);
Route::get('/fleet/products', [DriverController::class, 'getProducts']);

// 5. Sales Orders & Smart Credit Tiering
Route::get('/shops', [SalesOrderController::class, 'getShops']);
Route::post('/shops/create', [SalesOrderController::class, 'createShop']);
Route::post('/shops/{id}/check-credit', [SalesOrderController::class, 'checkCredit']);
Route::post('/shops/{id}/record-payment', [SalesOrderController::class, 'recordPayment']);
Route::put('/admin/shops/{id}/credit', [AdminController::class, 'updateShopCredit']);
Route::post('/fleet/shops/{id}/credit', [AdminController::class, 'updateShopCredit']);
Route::post('/orders/create', [SalesOrderController::class, 'createOrder']);

// 6. Emergency SOS & Stock Rescue Protocol
Route::get('/sos/active', [SosRescueController::class, 'getActiveAlerts']);
Route::post('/sos/trigger', [SosRescueController::class, 'triggerSos']);
Route::post('/sos/{id}/dispatch-rescue', [SosRescueController::class, 'dispatchRescue']);
Route::post('/sos/{id}/transfer-stock', [SosRescueController::class, 'transferStock']);

// 7. SMS OTP Generator & Verifier
Route::post('/auth/otp/generate', function () {
    $otp = (string)random_int(100000, 999999);
    $ref = 'OTP-' . strtoupper(bin2hex(random_bytes(3)));
    $filePath = storage_path('framework/latest_otp.json');
    file_put_contents($filePath, json_encode(['otp' => $otp, 'ref' => $ref, 'expires' => time() + 180]));

    return response()->json([
        'success' => true,
        'message' => 'OTP generated successfully.',
        'referenceCode' => $ref,
        'expiresInSeconds' => 180,
        'devSimulationCode' => $otp
    ]);
});

Route::post('/auth/otp/verify', function (Request $request) {
    $submittedOtp = trim((string)$request->input('otp', ''));
    $filePath = storage_path('framework/latest_otp.json');
    $stored = file_exists($filePath) ? json_decode(@file_get_contents($filePath), true) : null;

    if ($stored && $stored['expires'] > time() && hash_equals((string)$stored['otp'], $submittedOtp)) {
        return response()->json(['success' => true, 'message' => 'OTP verified successfully.', 'referenceCode' => $stored['ref']]);
    }

    return response()->json(['success' => false, 'error' => 'Invalid or expired OTP code.'], 400);
});

// 8. Returns & Expiry Management
Route::get('/returns', [OperationsController::class, 'getReturns']);
Route::post('/returns/record', [OperationsController::class, 'recordReturn']);

// 9. Crate & Container Tracking
Route::get('/crates/summary', [OperationsController::class, 'getCrateSummary']);
Route::post('/crates/record', [OperationsController::class, 'recordCrateTransaction']);

// 10. Day-End Settlement & Reconciliation
Route::get('/settlement/preview', [OperationsController::class, 'getSettlementPreview']);
Route::post('/settlement/submit', [OperationsController::class, 'submitSettlement']);
Route::get('/settlement/history', [OperationsController::class, 'getSettlementHistory']);

// 11. Advanced Financial Analytics & Reports
Route::get('/reports/route-profitability', [OperationsController::class, 'getRouteProfitability']);
Route::get('/reports/flavor-analytics', [OperationsController::class, 'getFlavorAnalytics']);
Route::get('/reports/flavor-trends', [OperationsController::class, 'getFlavorAnalytics']);
Route::get('/reports/shop-statement/{shopId}', [OperationsController::class, 'getShopStatement']);
Route::get('/reports/rep-commissions', [OperationsController::class, 'getRepCommissions']);

// 12. Front Office Maker-Checker Approvals Protocol
Route::get('/approvals/pending', [ApprovalController::class, 'getPendingApprovals']);
Route::post('/approvals/payments/{id}/action', [ApprovalController::class, 'actionPayment']);
Route::post('/approvals/returns/{id}/action', [ApprovalController::class, 'actionReturn']);
Route::post('/approvals/shops/{id}/action', [ApprovalController::class, 'actionShop']);
Route::post('/approvals/orders/{id}/action', [ApprovalController::class, 'actionOrder']);
