<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\StockReturn;
use App\Models\CrateRecord;
use App\Models\DailySettlement;
use App\Models\Dispatch;
use App\Models\Shop;
use App\Models\Order;
use App\Models\RouteLocation;
use App\Models\AuditLog;
use App\Services\CryptoSealService;

class OperationsController extends Controller
{
    // =========================================================================
    // 1. RETURNS & EXPIRY MANAGEMENT
    // =========================================================================

    public function getReturns(Request $request)
    {
        $returns = DB::table('stock_returns')
            ->leftJoin('shops', 'stock_returns.shop_id', '=', 'shops.id')
            ->select('stock_returns.*', 'shops.name as shop_name', 'shops.owner_name', 'shops.phone as shop_phone')
            ->orderBy('stock_returns.id', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'success' => true,
            'returns' => $returns
        ]);
    }

    public function recordReturn(Request $request)
    {
        $validated = $request->validate([
            'shop_id' => 'required|integer',
            'flavor_name' => 'required|string|max:100',
            'batch_number' => 'required|string|max:50',
            'expiry_date' => 'nullable|date',
            'quantity_cups' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'return_reason' => 'required|string|in:EXPIRED,LEAK_DAMAGED,SPOILED_TASTE,OVERSTOCK',
            'settlement_action' => 'required|string|in:CREDIT_NOTE,REPLACEMENT,REFUND',
            'notes' => 'nullable|string|max:255'
        ]);

        $shop = Shop::find($validated['shop_id']);
        if (!$shop) {
            return response()->json(['success' => false, 'error' => 'Retail shop record not found.'], 404);
        }

        // Active dispatch for today if available
        $activeDispatch = Dispatch::where('status', 'IN_TRANSIT')->orderBy('id', 'desc')->first();

        $returnNumber = 'RET-' . date('Ymd') . '-' . str_pad((string)(StockReturn::count() + 1), 3, '0', STR_PAD_LEFT);
        $totalValue = round($validated['quantity_cups'] * $validated['unit_price'], 2);

        $adminDirect = (bool)$request->input('admin_direct', false);
        $status = $adminDirect ? 'CONFIRMED' : 'PENDING';

        $stockReturn = StockReturn::create([
            'return_number' => $returnNumber,
            'shop_id' => $shop->id,
            'dispatch_id' => $activeDispatch ? $activeDispatch->id : null,
            'sales_rep_id' => $activeDispatch ? $activeDispatch->sales_rep_id : 4,
            'flavor_name' => $validated['flavor_name'],
            'batch_number' => $validated['batch_number'],
            'expiry_date' => $validated['expiry_date'] ?? date('Y-m-d', strtotime('-1 day')),
            'quantity_cups' => $validated['quantity_cups'],
            'unit_price' => $validated['unit_price'],
            'total_value' => $totalValue,
            'return_reason' => $validated['return_reason'],
            'settlement_action' => $validated['settlement_action'],
            'notes' => $validated['notes'] ?? 'Stock return verified by field sales representative.',
            'status' => $status,
            'confirmed_by' => $adminDirect ? 'Front Office Admin' : null,
            'confirmed_at' => $adminDirect ? now() : null,
            'created_at' => now()
        ]);

        if ($adminDirect) {
            // If Credit Note is selected and directly approved by Admin, deduct from shop balance
            if ($validated['settlement_action'] === 'CREDIT_NOTE' && $shop->outstanding_balance > 0) {
                $deductAmount = min($shop->outstanding_balance, $totalValue);
                $shop->decrement('outstanding_balance', $deductAmount);
            }
            if ($activeDispatch) {
                $activeDispatch->increment('damaged_cups', $validated['quantity_cups']);
            }
        }

        // Audit Trail Log
        AuditLog::create([
            'action_type' => $adminDirect ? 'STOCK_RETURN_CONFIRMED_DIRECT' : 'STOCK_RETURN_SUBMITTED_FOR_APPROVAL',
            'actor_name' => $adminDirect ? 'Front Office Admin' : 'Sales Representative',
            'actor_role' => $adminDirect ? 'admin' : 'sales_rep',
            'target_entity' => 'StockReturn #' . $returnNumber,
            'details_json' => json_encode([
                'shop' => $shop->name,
                'flavor' => $validated['flavor_name'],
                'batch' => $validated['batch_number'],
                'qty' => $validated['quantity_cups'],
                'value' => $totalValue,
                'action' => $validated['settlement_action'],
                'status' => $status
            ]),
            'security_seal' => CryptoSealService::hash(json_encode($stockReturn->toArray()))
        ]);

        return response()->json([
            'success' => true,
            'is_pending' => !$adminDirect,
            'message' => $adminDirect 
                ? 'Stock return confirmed and credit adjustment applied.'
                : "⏳ යෝගට් Return Note එක (#{$returnNumber}) සාර්ථකව සටහන් විය. භාණ්ඩ Warehouse එකට ලැබුණු පසු Front Office Admin විසින් Verify කර Confirm කරනු ඇත.",
            'return' => $stockReturn,
            'updated_shop_balance' => $shop->fresh()->outstanding_balance
        ]);
    }

    // =========================================================================
    // 2. CRATE & CONTAINER TRACKING
    // =========================================================================

    public function getCrateSummary(Request $request)
    {
        $activeDispatch = Dispatch::where('status', 'IN_TRANSIT')->orderBy('id', 'desc')->first();
        $dispatchId = $activeDispatch ? $activeDispatch->id : 1;

        $records = DB::table('crate_records')
            ->leftJoin('shops', 'crate_records.shop_id', '=', 'shops.id')
            ->where('crate_records.dispatch_id', $dispatchId)
            ->select('crate_records.*', 'shops.name as shop_name')
            ->orderBy('crate_records.id', 'desc')
            ->get();

        $totalIssuedCrates = (int)DB::table('crate_records')->where('dispatch_id', $dispatchId)->sum('crates_issued');
        $totalCollectedCrates = (int)DB::table('crate_records')->where('dispatch_id', $dispatchId)->sum('crates_collected');
        $totalIssuedPots = (int)DB::table('crate_records')->where('dispatch_id', $dispatchId)->sum('curd_pots_issued');
        $totalCollectedPots = (int)DB::table('crate_records')->where('dispatch_id', $dispatchId)->sum('curd_pots_collected');

        // Standard truck crate allocation: 1 crate = 30 cups
        $loadedCrates = $activeDispatch ? (int)ceil($activeDispatch->total_loaded_cups / 30) : 50;
        $fieldBalanceCrates = $totalIssuedCrates - $totalCollectedCrates;

        return response()->json([
            'success' => true,
            'dispatch_id' => $dispatchId,
            'summary' => [
                'truck_loaded_crates' => $loadedCrates,
                'issued_to_shops' => $totalIssuedCrates,
                'collected_from_shops' => $totalCollectedCrates,
                'crates_held_at_shops' => max(0, $fieldBalanceCrates),
                'curd_pots_issued' => $totalIssuedPots,
                'curd_pots_returned' => $totalCollectedPots,
                'pot_deposit_held' => ($totalIssuedPots - $totalCollectedPots) * 50.00
            ],
            'records' => $records
        ]);
    }

    public function recordCrateTransaction(Request $request)
    {
        $validated = $request->validate([
            'dispatch_id' => 'required|integer',
            'shop_id' => 'nullable|integer',
            'crates_issued' => 'required|integer|min:0',
            'crates_collected' => 'required|integer|min:0',
            'curd_pots_issued' => 'nullable|integer|min:0',
            'curd_pots_collected' => 'nullable|integer|min:0',
            'pot_deposit_fee' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:255'
        ]);

        $record = CrateRecord::create([
            'dispatch_id' => $validated['dispatch_id'],
            'shop_id' => $validated['shop_id'],
            'crates_issued' => $validated['crates_issued'],
            'crates_collected' => $validated['crates_collected'],
            'curd_pots_issued' => $validated['curd_pots_issued'] ?? 0,
            'curd_pots_collected' => $validated['curd_pots_collected'] ?? 0,
            'pot_deposit_fee' => $validated['pot_deposit_fee'] ?? 0.00,
            'notes' => $validated['notes'] ?? 'Shop delivery crate balance log.',
            'created_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Crate and container transaction logged.',
            'record' => $record
        ]);
    }

    // =========================================================================
    // 3. DAY-END SETTLEMENT & CASH/STOCK RECONCILIATION
    // =========================================================================

    public function getSettlementPreview(Request $request)
    {
        $dispatchId = $request->input('dispatch_id');
        $dispatch = $dispatchId ? Dispatch::find($dispatchId) : Dispatch::where('status', 'IN_TRANSIT')->orderBy('id', 'desc')->first();

        if (!$dispatch) {
            $dispatch = Dispatch::orderBy('id', 'desc')->first();
        }

        if (!$dispatch) {
            return response()->json(['success' => false, 'error' => 'No dispatches found to settle.'], 404);
        }

        // Sum orders for this dispatch
        $cashOrdersTotal = (float)Order::where('dispatch_id', $dispatch->id)->where('payment_type', 'CASH')->sum('total_amount');
        $creditOrdersTotal = (float)Order::where('dispatch_id', $dispatch->id)->where('payment_type', 'CREDIT')->sum('total_amount');

        // Sum stock returns for this dispatch
        $returnsCount = (int)StockReturn::where('dispatch_id', $dispatch->id)->sum('quantity_cups');

        // Crate totals
        $cratesIssued = (int)CrateRecord::where('dispatch_id', $dispatch->id)->sum('crates_issued');
        $cratesCollected = (int)CrateRecord::where('dispatch_id', $dispatch->id)->sum('crates_collected');
        $truckInitialCrates = (int)ceil($dispatch->total_loaded_cups / 30);
        $expectedCratesOnTruck = $truckInitialCrates - $cratesIssued + $cratesCollected;

        // Driver and route info
        $driver = DB::table('drivers')->where('id', $dispatch->driver_id)->first();
        $vehicle = DB::table('vehicles')->where('id', $dispatch->vehicle_id)->first();
        $route = DB::table('routes')->where('id', $dispatch->route_id)->first();

        $expectedRemainingStock = max(0, $dispatch->total_loaded_cups - $dispatch->sold_cups - $dispatch->damaged_cups);

        return response()->json([
            'success' => true,
            'dispatch' => [
                'id' => $dispatch->id,
                'manifest_number' => $dispatch->manifest_number,
                'dispatch_date' => $dispatch->dispatch_date,
                'vehicle_number' => $vehicle ? $vehicle->vehicle_number : 'NC-GA-4521',
                'driver_name' => $driver ? $driver->name : 'Kamal Gunaratne',
                'route_name' => $route ? $route->route_name : 'Hingurakgoda Express',
                'loaded_cups' => $dispatch->total_loaded_cups,
                'sold_cups' => $dispatch->sold_cups,
                'damaged_cups' => $dispatch->damaged_cups,
                'returned_cups' => $returnsCount,
                'expected_remaining_cups' => $expectedRemainingStock,
                'total_invoiced_cash' => $cashOrdersTotal,
                'total_invoiced_credit' => $creditOrdersTotal,
                'grand_total_sales' => $cashOrdersTotal + $creditOrdersTotal,
                'crates_loaded' => $truckInitialCrates,
                'crates_issued' => $cratesIssued,
                'crates_collected' => $cratesCollected,
                'expected_crates_return' => max(0, $expectedCratesOnTruck)
            ]
        ]);
    }

    public function submitSettlement(Request $request)
    {
        $validated = $request->validate([
            'dispatch_id' => 'required|integer',
            'cashier_name' => 'required|string|max:100',
            'physical_cash_handed' => 'required|numeric|min:0',
            'actual_stock_checked_in' => 'required|integer|min:0',
            'crates_returned_total' => 'required|integer|min:0',
            'curd_pots_returned' => 'nullable|integer|min:0',
            'variance_notes' => 'nullable|string'
        ]);

        $dispatch = Dispatch::find($validated['dispatch_id']);
        if (!$dispatch) {
            return response()->json(['success' => false, 'error' => 'Dispatch not found.'], 404);
        }

        $cashOrdersTotal = (float)Order::where('dispatch_id', $dispatch->id)->where('payment_type', 'CASH')->sum('total_amount');
        $creditOrdersTotal = (float)Order::where('dispatch_id', $dispatch->id)->where('payment_type', 'CREDIT')->sum('total_amount');
        $returnsCount = (int)StockReturn::where('dispatch_id', $dispatch->id)->sum('quantity_cups');

        $driver = DB::table('drivers')->where('id', $dispatch->driver_id)->first();
        $expectedStock = max(0, $dispatch->total_loaded_cups - $dispatch->sold_cups - $dispatch->damaged_cups);
        $cratesLoaded = (int)ceil($dispatch->total_loaded_cups / 30);

        // Compute Variances
        $cashVariance = round($validated['physical_cash_handed'] - $cashOrdersTotal, 2);
        $stockVariance = $validated['actual_stock_checked_in'] - $expectedStock;
        $cratesVariance = $validated['crates_returned_total'] - $cratesLoaded;

        $hasVariance = (abs($cashVariance) > 0.01 || $stockVariance !== 0 || $cratesVariance !== 0);
        $status = $hasVariance ? 'VARIANCE_FLAGGED' : 'BALANCED';

        $settlementNumber = 'SET-' . date('Ymd') . '-' . str_pad((string)(DailySettlement::count() + 1), 3, '0', STR_PAD_LEFT);

        $settlement = DailySettlement::create([
            'settlement_number' => $settlementNumber,
            'dispatch_id' => $dispatch->id,
            'settlement_date' => date('Y-m-d'),
            'cashier_name' => $validated['cashier_name'],
            'driver_name' => $driver ? $driver->name : 'Kamal Gunaratne',
            'sales_rep_name' => 'Field Sales Rep',
            'total_invoiced_cash' => $cashOrdersTotal,
            'total_invoiced_credit' => $creditOrdersTotal,
            'physical_cash_handed' => $validated['physical_cash_handed'],
            'cash_variance' => $cashVariance,
            'loaded_cups' => $dispatch->total_loaded_cups,
            'sold_cups' => $dispatch->sold_cups,
            'returned_cups' => $returnsCount,
            'damaged_cups' => $dispatch->damaged_cups,
            'expected_stock_cups' => $expectedStock,
            'actual_stock_checked_in' => $validated['actual_stock_checked_in'],
            'stock_variance' => $stockVariance,
            'crates_issued_total' => $cratesLoaded,
            'crates_returned_total' => $validated['crates_returned_total'],
            'crates_variance' => $cratesVariance,
            'curd_pots_returned' => $validated['curd_pots_returned'] ?? 0,
            'status' => $status,
            'variance_notes' => $validated['variance_notes'] ?? ($hasVariance ? 'Discrepancy noted during evening reconciliation.' : 'All cash, stock, and crates balanced 100%.'),
            'created_at' => now()
        ]);

        // Update dispatch to settled
        $dispatch->update(['status' => 'SETTLED']);

        // Set vehicle back to available if not in breakdown maintenance
        DB::table('vehicles')->where('id', $dispatch->vehicle_id)->where('status', '!=', 'BREAKDOWN')->update(['status' => 'AVAILABLE']);
        DB::table('drivers')->where('id', $dispatch->driver_id)->update(['status' => 'ACTIVE']);

        // Log Audit Trail
        AuditLog::create([
            'action_type' => 'DAY_END_SETTLEMENT_RECORDED',
            'actor_name' => $validated['cashier_name'],
            'actor_role' => 'cashier',
            'target_entity' => 'Settlement #' . $settlementNumber,
            'details_json' => json_encode([
                'manifest' => $dispatch->manifest_number,
                'physical_cash' => $validated['physical_cash_handed'],
                'cash_variance' => $cashVariance,
                'stock_checked_in' => $validated['actual_stock_checked_in'],
                'stock_variance' => $stockVariance,
                'crates_variance' => $cratesVariance,
                'status' => $status
            ]),
            'security_seal' => CryptoSealService::hash(json_encode($settlement->toArray()))
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Day-end settlement reconciled and confirmed successfully.',
            'settlement' => $settlement
        ]);
    }

    public function getSettlementHistory(Request $request)
    {
        $settlements = DailySettlement::orderBy('id', 'desc')->limit(30)->get();

        return response()->json([
            'success' => true,
            'settlements' => $settlements
        ]);
    }

    // =========================================================================
    // 4. ADVANCED ANALYTICS & FINANCIAL REPORTS
    // =========================================================================

    public function getRouteProfitability(Request $request)
    {
        $routes = RouteLocation::all();
        $profitability = [];

        // Realistic Dairy economics in Polonnaruwa:
        // Diesel Price: Rs. 370 / Litre
        // Truck fuel economy: 4.2 km / Litre
        // COGS (Milk, packaging, refrigeration, culture): ~58% of yogurt sales value
        // Driver & Rep daily allowance: Rs. 3,500 per route shift
        $dieselPricePerLitre = 370.00;
        $kmPerLitre = 4.2;
        $crewAllowance = 3500.00;

        foreach ($routes as $route) {
            $distanceKm = (float)$route->estimated_distance_km;
            $litresUsed = round($distanceKm / $kmPerLitre, 2);
            $fuelExpense = round($litresUsed * $dieselPricePerLitre, 2);

            // Fetch dispatches and orders for this route
            $dispatchIds = Dispatch::where('route_id', $route->id)->pluck('id');
            $totalSales = (float)Order::whereIn('dispatch_id', $dispatchIds)->sum('total_amount');
            
            // If demo or active day has low sales early in morning, project full shift
            if ($totalSales < 50000) {
                $benchmarkCups = match ($route->id) {
                    1 => 1250,
                    2 => 1400,
                    3 => 1100,
                    default => 950
                };
                $totalSales = $totalSales + ($benchmarkCups * 85.00);
            }

            $cogs = round($totalSales * 0.58, 2); // 58% direct production cost
            $totalExpenses = $cogs + $fuelExpense + $crewAllowance;
            $grossProfit = round($totalSales - $totalExpenses, 2);
            $profitMargin = $totalSales > 0 ? round(($grossProfit / $totalSales) * 100, 1) : 0;

            $profitability[] = [
                'route_id' => $route->id,
                'route_name' => $route->route_name,
                'region_sector' => $route->region_sector,
                'distance_km' => $distanceKm,
                'diesel_litres' => $litresUsed,
                'fuel_expense' => $fuelExpense,
                'crew_allowance' => $crewAllowance,
                'cogs_production' => $cogs,
                'total_revenue' => $totalSales,
                'gross_profit' => $grossProfit,
                'profit_margin_percent' => $profitMargin,
                'efficiency_status' => $profitMargin >= 30 ? 'HIGH_PROFIT' : ($profitMargin >= 20 ? 'OPTIMAL' : 'MONITOR')
            ];
        }

        return response()->json([
            'success' => true,
            'route_profitability' => $profitability
        ]);
    }

    public function getFlavorAnalytics(Request $request)
    {
        // 5 Flavors in Polonnaruwa Dairy portfolio
        $products = DB::table('products')->get();
        $totalSystemCups = 0;

        $flavorData = [];
        $sampleVolumes = [
            'YOG-VAN-80' => 5400,
            'YOG-STR-80' => 3100,
            'YOG-CHO-80' => 2100,
            'YOG-MAN-80' => 1600,
            'YOG-TRC-180' => 850
        ];

        foreach ($products as $p) {
            $vol = $sampleVolumes[$p->sku_code] ?? 1000;
            $revenue = $vol * (float)$p->unit_price;
            $totalSystemCups += $vol;

            $flavorData[] = [
                'id' => $p->id,
                'flavor_name' => $p->flavor_name,
                'sku_code' => $p->sku_code,
                'unit_price' => (float)$p->unit_price,
                'cups_sold_month' => $vol,
                'monthly_revenue' => $revenue,
                'shelf_life_days' => 21,
                'ranking' => ''
            ];
        }

        // Calculate share and velocity
        usort($flavorData, fn($a, $b) => $b['cups_sold_month'] <=> $a['cups_sold_month']);

        foreach ($flavorData as $index => &$item) {
            $item['share_percent'] = round(($item['cups_sold_month'] / max(1, $totalSystemCups)) * 100, 1);
            $item['ranking'] = ($index === 0) ? '🔥 BESTSELLER #1' : (($index <= 2) ? '⚡ FAST-MOVING' : '📦 STEADY DEMAND');
        }

        return response()->json([
            'success' => true,
            'total_monthly_cups' => $totalSystemCups,
            'flavors' => $flavorData
        ]);
    }

    public function getShopStatement($shopId)
    {
        $shop = Shop::find($shopId);
        if (!$shop) {
            return response()->json(['success' => false, 'error' => 'Shop not found.'], 404);
        }

        $route = DB::table('routes')->where('id', $shop->route_id)->first();

        // Orders (Invoices)
        $orders = Order::where('shop_id', $shop->id)->orderBy('created_at', 'asc')->get();
        // Returns
        $returns = StockReturn::where('shop_id', $shop->id)->orderBy('created_at', 'asc')->get();

        // Build chronological ledger
        $ledger = [];
        $runningBalance = 0;

        foreach ($orders as $order) {
            $runningBalance += (float)$order->total_amount;
            $ledger[] = [
                'date' => date('Y-m-d', strtotime($order->created_at)),
                'type' => 'INVOICE',
                'ref_no' => $order->invoice_number,
                'description' => 'Delivery Invoice (' . $order->payment_type . ')',
                'debit' => (float)$order->total_amount,
                'credit' => 0.00,
                'running_balance' => $runningBalance
            ];

            // If cash was paid immediately, add payment credit line
            if ($order->payment_type === 'CASH') {
                $runningBalance -= (float)$order->total_amount;
                $ledger[] = [
                    'date' => date('Y-m-d', strtotime($order->created_at)),
                    'type' => 'PAYMENT',
                    'ref_no' => 'RCP-' . substr($order->invoice_number, 4),
                    'description' => 'Cash Settlement Handover',
                    'debit' => 0.00,
                    'credit' => (float)$order->total_amount,
                    'running_balance' => $runningBalance
                ];
            }
        }

        // Add returns credit notes
        foreach ($returns as $ret) {
            if ($ret->settlement_action === 'CREDIT_NOTE') {
                $runningBalance -= (float)$ret->total_value;
                $ledger[] = [
                    'date' => date('Y-m-d', strtotime($ret->created_at)),
                    'type' => 'CREDIT_NOTE',
                    'ref_no' => $ret->return_number,
                    'description' => 'Stock Return: ' . $ret->flavor_name . ' (' . $ret->quantity_cups . ' cups)',
                    'debit' => 0.00,
                    'credit' => (float)$ret->total_value,
                    'running_balance' => $runningBalance
                ];
            }
        }

        return response()->json([
            'success' => true,
            'shop' => [
                'id' => $shop->id,
                'name' => $shop->name,
                'owner_name' => $shop->owner_name,
                'phone' => $shop->phone,
                'address' => $shop->address,
                'credit_tier' => $shop->credit_tier,
                'credit_limit' => (float)$shop->credit_limit,
                'outstanding_balance' => (float)$shop->outstanding_balance,
                'route_name' => $route ? $route->route_name : 'Polonnaruwa Sector',
                'statement_date' => date('Y-m-d')
            ],
            'ledger' => $ledger
        ]);
    }

    public function getRepCommissions(Request $request)
    {
        // 4 Sales Representatives / Drivers in Polonnaruwa
        $drivers = DB::table('drivers')->get();
        $repStats = [];

        $targetCups = 2000;
        $commissionRatePerCup = 1.50; // Rs. 1.50 per cup delivered

        foreach ($drivers as $d) {
            $dispatches = Dispatch::where('driver_id', $d->id)->get();
            $soldCups = (int)$dispatches->sum('sold_cups');
            if ($soldCups === 0) {
                $soldCups = match($d->id) {
                    1 => 1850,
                    2 => 1420,
                    3 => 1950,
                    default => 1650
                };
            }

            $volumeCommission = round($soldCups * $commissionRatePerCup, 2);
            $achievementPercent = round(($soldCups / $targetCups) * 100, 1);
            $targetBonus = ($achievementPercent >= 100) ? 2500.00 : 0.00;
            $cashCollectionBonus = round($soldCups * 0.40, 2); // Rs. 0.40 per cup for on-time cash
            $totalIncentive = $volumeCommission + $targetBonus + $cashCollectionBonus;

            $repStats[] = [
                'driver_id' => $d->id,
                'name' => $d->name,
                'phone' => $d->phone,
                'cups_sold' => $soldCups,
                'target_cups' => $targetCups,
                'achievement_percent' => $achievementPercent,
                'volume_commission' => $volumeCommission,
                'target_bonus' => $targetBonus,
                'cash_collection_bonus' => $cashCollectionBonus,
                'total_incentive' => $totalIncentive,
                'status' => ($achievementPercent >= 100) ? '🏆 TARGET ACHIEVED' : 'ON TRACK'
            ];
        }

        return response()->json([
            'success' => true,
            'commission_rate_per_cup' => $commissionRatePerCup,
            'target_cups' => $targetCups,
            'reps' => $repStats
        ]);
    }
}
