<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\Shop;
use App\Models\Dispatch;
use App\Models\Order;
use App\Models\StockReturn;
use App\Models\AuditLog;
use App\Services\CryptoSealService;
use App\Services\SmartCreditService;

class ApprovalController extends Controller
{
    private CryptoSealService $cryptoService;
    private SmartCreditService $creditService;

    public function __construct()
    {
        $this->cryptoService = new CryptoSealService();
        $this->creditService = new SmartCreditService();
    }

    /**
     * Get all pending items requiring Front Office Admin confirmation
     */
    public function getPendingApprovals(): JsonResponse
    {
        try {
            // 1. Pending Payments (Cash collected by Reps)
            $pendingPayments = DB::table('payments')
                ->leftJoin('shops', 'payments.shop_id', '=', 'shops.id')
                ->select(
                    'payments.*',
                    'shops.name as shop_name',
                    'shops.owner_name',
                    'shops.phone as shop_phone',
                    'shops.outstanding_balance'
                )
                ->where('payments.status', 'PENDING')
                ->orderBy('payments.id', 'desc')
                ->get();

            // 2. Pending Stock Returns (Damages/Expiries needing physical verification)
            $pendingReturns = DB::table('stock_returns')
                ->leftJoin('shops', 'stock_returns.shop_id', '=', 'shops.id')
                ->select(
                    'stock_returns.*',
                    'shops.name as shop_name',
                    'shops.owner_name',
                    'shops.phone as shop_phone'
                )
                ->where('stock_returns.status', 'PENDING')
                ->orderBy('stock_returns.id', 'desc')
                ->get();

            // 3. Pending New Shop Registrations
            $pendingShops = DB::table('shops')
                ->leftJoin('routes', 'shops.route_id', '=', 'routes.id')
                ->select(
                    'shops.*',
                    'routes.route_name',
                    'routes.region_sector'
                )
                ->where('shops.approval_status', 'PENDING')
                ->orderBy('shops.id', 'desc')
                ->get();

            // 4. Pending Credit Invoices (Credit orders needing Front Office clearance)
            $pendingOrders = DB::table('orders')
                ->leftJoin('shops', 'orders.shop_id', '=', 'shops.id')
                ->select(
                    'orders.*',
                    'shops.name as shop_name',
                    'shops.owner_name',
                    'shops.phone as shop_phone',
                    'shops.credit_tier',
                    'shops.outstanding_balance'
                )
                ->where('orders.status', 'PENDING_APPROVAL')
                ->orderBy('orders.id', 'desc')
                ->get();

            $totalPending = count($pendingPayments) + count($pendingReturns) + count($pendingShops) + count($pendingOrders);

            return response()->json([
                'success' => true,
                'total_pending' => $totalPending,
                'pending_payments' => $pendingPayments,
                'pending_returns' => $pendingReturns,
                'pending_shops' => $pendingShops,
                'pending_orders' => $pendingOrders
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm or Reject a Rep Customer Payment
     */
    public function actionPayment(Request $request, int $id): JsonResponse
    {
        $action = strtoupper($request->input('action', 'APPROVE')); // APPROVE or REJECT
        $adminName = $request->input('admin_name', 'Front Office Admin');
        $reason = $request->input('reason', '');

        $payment = DB::table('payments')->where('id', $id)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'error' => 'Payment record not found.'], 404);
        }

        if ($payment->status !== 'PENDING') {
            return response()->json(['success' => false, 'error' => "Payment already processed ({$payment->status})."], 400);
        }

        DB::beginTransaction();
        try {
            if ($action === 'APPROVE') {
                // Apply debt reduction to the shop
                $creditResult = $this->creditService->recordCustomerPayment($payment->shop_id, (float)$payment->payment_amount);

                DB::table('payments')->where('id', $id)->update([
                    'status' => 'CONFIRMED',
                    'confirmed_by' => $adminName,
                    'confirmed_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'PAYMENT_CONFIRMED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Payment #{$payment->receipt_number}",
                    [
                        'paymentId' => $id,
                        'shopId' => $payment->shop_id,
                        'amount' => $payment->payment_amount,
                        'previousBalance' => $creditResult['previousBalance'],
                        'newBalance' => $creditResult['newBalance']
                    ]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "✅ රු. " . number_format($payment->payment_amount, 2) . " ක ගෙවීම Front Office මඟින් තහවුරු කරන ලදී. වෙළඳසැලේ නව ශේෂය: රු. " . number_format($creditResult['newBalance'], 2),
                    'newBalance' => $creditResult['newBalance']
                ]);
            } else {
                DB::table('payments')->where('id', $id)->update([
                    'status' => 'REJECTED',
                    'confirmed_by' => $adminName,
                    'rejection_reason' => $reason ?: 'Cash/Cheque was not physically handed over or mismatched.',
                    'confirmed_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'PAYMENT_REJECTED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Payment #{$payment->receipt_number}",
                    ['paymentId' => $id, 'amount' => $payment->payment_amount, 'reason' => $reason]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "❌ ගෙවීම ප්‍රතික්ෂේප කරන ලදී (Rejected). වෙළඳසැලේ ශේෂය වෙනස් නොවේ."
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Confirm or Reject a Stock Return (Damage / Expiry)
     */
    public function actionReturn(Request $request, int $id): JsonResponse
    {
        $action = strtoupper($request->input('action', 'APPROVE'));
        $adminName = $request->input('admin_name', 'Front Office Admin');
        $reason = $request->input('reason', '');

        $return = StockReturn::find($id);
        if (!$return) {
            return response()->json(['success' => false, 'error' => 'Return record not found.'], 404);
        }

        if ($return->status !== 'PENDING') {
            return response()->json(['success' => false, 'error' => "Stock return already processed ({$return->status})."], 400);
        }

        DB::beginTransaction();
        try {
            $shop = Shop::find($return->shop_id);

            if ($action === 'APPROVE') {
                $deduct = 0;
                // If CREDIT_NOTE, adjust the shop debt
                if ($return->settlement_action === 'CREDIT_NOTE' && $shop && $shop->outstanding_balance > 0) {
                    $deduct = min((float)$shop->outstanding_balance, (float)$return->total_value);
                    if ($deduct > 0) {
                        $shop->decrement('outstanding_balance', $deduct);
                    }
                }

                // If dispatch exists, update damaged cups
                if ($return->dispatch_id) {
                    $dispatch = Dispatch::find($return->dispatch_id);
                    if ($dispatch) {
                        $dispatch->increment('damaged_cups', (int)$return->quantity_cups);
                    }
                }

                $return->update([
                    'status' => 'CONFIRMED',
                    'confirmed_by' => $adminName,
                    'confirmed_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'STOCK_RETURN_CONFIRMED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Return #{$return->return_number}",
                    [
                        'returnId' => $id,
                        'shop' => $shop ? $shop->name : 'N/A',
                        'flavor' => $return->flavor_name,
                        'qty' => $return->quantity_cups,
                        'value' => $return->total_value,
                        'creditDeducted' => $deduct
                    ]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "✅ Return Note #{$return->return_number} සාර්ථකව තහවුරු කරන ලදී (Physical stock verified)." . ($deduct > 0 ? " රු. " . number_format($deduct, 2) . " ක Credit Note එකක් වෙළඳසැලට හිමිවිය." : ""),
                    'newShopBalance' => $shop ? $shop->fresh()->outstanding_balance : 0
                ]);
            } else {
                $return->update([
                    'status' => 'REJECTED',
                    'confirmed_by' => $adminName,
                    'rejection_reason' => $reason ?: 'Physical damaged stock not returned or invalid batch.',
                    'confirmed_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'STOCK_RETURN_REJECTED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Return #{$return->return_number}",
                    ['returnId' => $id, 'reason' => $reason]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "❌ Return Note #{$return->return_number} ප්‍රතික්ෂේප කරන ලදී. වෙළඳසැලට Credit adjustment එකක් සිදු නොවේ."
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Batch confirm or reject multiple Stock Returns
     */
    public function actionReturnBulk(Request $request): JsonResponse
    {
        $action = strtoupper($request->input('action', 'APPROVE'));
        $adminName = $request->input('admin_name', 'Front Office Admin');
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            $ids = StockReturn::where('status', 'PENDING')->pluck('id')->toArray();
        }

        if (empty($ids)) {
            return response()->json(['success' => false, 'error' => 'තහවුරු කිරීමට Pending Return සටහන් කිසිවක් හමු නොවීය.'], 400);
        }

        DB::beginTransaction();
        try {
            $count = 0;
            $totalCredit = 0;

            foreach ($ids as $id) {
                $return = StockReturn::find($id);
                if (!$return || $return->status !== 'PENDING') continue;

                $shop = Shop::find($return->shop_id);

                if ($action === 'APPROVE') {
                    if ($return->settlement_action === 'CREDIT_NOTE' && $shop && $shop->outstanding_balance > 0) {
                        $deduct = min((float)$shop->outstanding_balance, (float)$return->total_value);
                        if ($deduct > 0) {
                            $shop->decrement('outstanding_balance', $deduct);
                            $totalCredit += $deduct;
                        }
                    }

                    if ($return->dispatch_id) {
                        $dispatch = Dispatch::find($return->dispatch_id);
                        if ($dispatch) {
                            $dispatch->increment('damaged_cups', (int)$return->quantity_cups);
                        }
                    }

                    $return->update([
                        'status' => 'CONFIRMED',
                        'confirmed_by' => $adminName,
                        'confirmed_at' => now()
                    ]);
                    $count++;
                } else {
                    $return->update([
                        'status' => 'REJECTED',
                        'confirmed_by' => $adminName,
                        'rejection_reason' => 'Batch rejected during Front Office physical verification.',
                        'confirmed_at' => now()
                    ]);
                    $count++;
                }
            }

            $this->cryptoService->logAuditEvent(
                'STOCK_RETURNS_BULK_ACTION',
                $adminName,
                'admin',
                "Bulk returns: {$action}",
                ['processedCount' => $count, 'totalCreditIssued' => $totalCredit]
            );

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ Return සටහන් {$count} ක් සාර්ථකව {$action} කරන ලදී!" . ($totalCredit > 0 ? " නිකුත් කළ මුළු Credit Note වටිනාකම: රු. " . number_format($totalCredit, 2) : ""),
                'processedCount' => $count
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve or Reject a Newly Registered Retail Shop
     */
    public function actionShop(Request $request, int $id): JsonResponse
    {
        $action = strtoupper($request->input('action', 'APPROVE'));
        $adminName = $request->input('admin_name', 'Front Office Admin');
        $creditTier = $request->input('credit_tier');
        $creditLimit = $request->input('credit_limit');
        $reason = $request->input('reason', '');

        $shop = Shop::find($id);
        if (!$shop) {
            return response()->json(['success' => false, 'error' => 'Shop record not found.'], 404);
        }

        DB::beginTransaction();
        try {
            if ($action === 'APPROVE') {
                $updates = [
                    'approval_status' => 'APPROVED',
                    'is_active' => 1,
                    'approved_by' => $adminName,
                    'approved_at' => now()
                ];

                if ($creditTier) {
                    $updates['credit_tier'] = $creditTier;
                }
                if ($creditLimit !== null) {
                    $updates['credit_limit'] = (float)$creditLimit;
                }

                $shop->update($updates);

                $this->cryptoService->logAuditEvent(
                    'SHOP_APPROVED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Shop #{$shop->id} ({$shop->name})",
                    ['shopId' => $shop->id, 'creditTier' => $shop->credit_tier, 'creditLimit' => $shop->credit_limit]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "✅ '{$shop->name}' වෙළඳසැල සාර්ථකව අනුමත කරන ලදී (Approved). දැන් මෙම කඩයට ඇණවුම් නිකුත් කළ හැක.",
                    'shop' => $shop->fresh()
                ]);
            } else {
                $shop->update([
                    'approval_status' => 'REJECTED',
                    'is_active' => 0,
                    'approved_by' => $adminName,
                    'rejection_reason' => $reason ?: 'Shop registration details could not be verified.',
                    'approved_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'SHOP_REJECTED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Shop #{$shop->id} ({$shop->name})",
                    ['shopId' => $shop->id, 'reason' => $reason]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "❌ වෙළඳසැල් ලියාපදිංචිය ප්‍රතික්ෂේප කරන ලදී (Rejected)."
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Approve or Reject an Order (e.g. Credit Sale Review)
     */
    public function actionOrder(Request $request, int $id): JsonResponse
    {
        $action = strtoupper($request->input('action', 'APPROVE'));
        $adminName = $request->input('admin_name', 'Front Office Admin');
        $reason = $request->input('reason', '');

        $order = Order::find($id);
        if (!$order) {
            return response()->json(['success' => false, 'error' => 'Order not found.'], 404);
        }

        DB::beginTransaction();
        try {
            if ($action === 'APPROVE') {
                $order->update([
                    'status' => 'CONFIRMED',
                    'approved_by' => $adminName,
                    'approved_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'ORDER_CONFIRMED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Invoice {$order->invoice_number}",
                    ['orderId' => $id, 'amount' => $order->total_amount]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "✅ Invoice {$order->invoice_number} Front Office මඟින් අනුමත කරන ලදී."
                ]);
            } else {
                // If rejected, reverse credit debt if it was credit
                if ($order->payment_type === 'CREDIT') {
                    $shop = Shop::find($order->shop_id);
                    if ($shop) {
                        $shop->decrement('outstanding_balance', min((float)$shop->outstanding_balance, (float)$order->total_amount));
                    }
                }

                // Reverse truck stock
                $items = json_decode($order->items_json, true) ?: [];
                $totalCups = array_reduce($items, fn($s, $i) => $s + (int)($i['quantity'] ?? 0), 0);
                if ($order->dispatch_id && $totalCups > 0) {
                    $dispatch = Dispatch::find($order->dispatch_id);
                    if ($dispatch) {
                        $dispatch->increment('remaining_cups', $totalCups);
                        $dispatch->decrement('sold_cups', min((int)$dispatch->sold_cups, $totalCups));
                    }
                }

                $order->update([
                    'status' => 'REJECTED',
                    'approved_by' => $adminName,
                    'rejection_reason' => $reason ?: 'Order rejected during Front Office review.',
                    'approved_at' => now()
                ]);

                $this->cryptoService->logAuditEvent(
                    'ORDER_REJECTED_BY_ADMIN',
                    $adminName,
                    'admin',
                    "Invoice {$order->invoice_number}",
                    ['orderId' => $id, 'amount' => $order->total_amount, 'reason' => $reason]
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "❌ Invoice {$order->invoice_number} ප්‍රතික්ෂේප කර තොග හා ණය ආපසු හරවන ලදී (Order reversed)."
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
