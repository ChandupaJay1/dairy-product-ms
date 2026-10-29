<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Shop;
use App\Models\Dispatch;
use App\Models\Order;
use App\Core\Database;
use App\Services\CryptoSealService;
use App\Services\SmartCreditService;

class SalesOrderController extends Controller
{
    private CryptoSealService $cryptoService;
    private SmartCreditService $creditService;

    public function __construct()
    {
        $this->cryptoService = new CryptoSealService();
        $this->creditService = new SmartCreditService();
    }

    public function getShops(): JsonResponse
    {
        $shops = Shop::allShops();
        return response()->json(['success' => true, 'shops' => $shops]);
    }

    public function createShop(Request $request): JsonResponse
    {
        $name = trim((string)$request->input('name', ''));
        $ownerName = trim((string)$request->input('owner_name', ''));
        $phone = trim((string)$request->input('phone', ''));
        $address = trim((string)$request->input('address', ''));
        $routeId = (int)($request->input('route_id', 1));
        $creditTier = $request->input('credit_tier', 'Tier C');
        $creditLimit = (float)($request->input('credit_limit', $creditTier === 'Cash Only' ? 0 : 15000));

        if (!$name || !$ownerName || !$phone) {
            return response()->json(['success' => false, 'error' => 'කඩේ නම, හිමිකරුගේ නම සහ දුරකථන අංකය ඇතුළත් කිරීම අනිවාර්යයි.'], 422);
        }

        try {
            $shopId = Database::execute("
                INSERT INTO shops (
                    name, owner_name, phone, route_id, address,
                    credit_tier, credit_limit, outstanding_balance, payment_rating_percent, overdue_days, is_active,
                    approval_status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 0.00, 100, 0, 1, 'PENDING')
            ", [
                $name, $ownerName, $phone, $routeId, $address ?: 'Polonnaruwa Sector',
                $creditTier, $creditLimit
            ]);

            $newShop = Database::queryOne("
                SELECT s.*, r.route_name, r.region_sector,
                       (s.credit_limit - s.outstanding_balance) AS available_credit
                FROM shops s
                LEFT JOIN routes r ON s.route_id = r.id
                WHERE s.id = ?
            ", [$shopId]);

            $this->cryptoService->logAuditEvent(
                'NEW_SHOP_SUBMITTED_FOR_APPROVAL',
                'Field Sales Rep',
                'sales_rep',
                "Shop #{$shopId} ({$name})",
                ['shopId' => $shopId, 'name' => $name, 'owner' => $ownerName, 'phone' => $phone, 'status' => 'PENDING']
            );

            return response()->json([
                'success' => true,
                'message' => "🏪 '{$name}' වෙළඳසැල Front Office අනුමැතිය සඳහා යොමු කරන ලදී (Pending Approval). Admin විසින් තහවුරු කළ පසු ක්‍රියාත්මක වේ.",
                'shop' => $newShop
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function checkCredit(Request $request, int $id): JsonResponse
    {
        $amount = (float)$request->input('amount', 0);
        $result = $this->creditService->evaluateCreditEligibility($id, $amount);
        return response()->json(array_merge(['success' => true], $result));
    }

    public function recordPayment(Request $request, int $id): JsonResponse
    {
        $paymentAmount = (float)$request->input('paymentAmount', 0);
        $notes = $request->input('notes', '');
        $salesRepName = $request->input('salesRepName', 'Field Sales Rep');
        $adminDirect = (bool)$request->input('admin_direct', false);

        if ($paymentAmount <= 0) {
            return response()->json(['success' => false, 'error' => 'වලංගු මුදල් ප්‍රමාණයක් ඇතුළත් කරන්න.'], 422);
        }

        try {
            $shop = Database::queryOne("SELECT * FROM shops WHERE id = ?", [$id]);
            if (!$shop) {
                return response()->json(['success' => false, 'error' => 'Shop not found.'], 404);
            }

            $receiptNo = 'PAY-' . date('Ymd') . '-' . rand(1000, 9999);

            if ($adminDirect) {
                // If Front Office Admin directly records the payment, apply immediately
                $result = $this->creditService->recordCustomerPayment($id, $paymentAmount);
                Database::execute("
                    INSERT INTO payments (receipt_number, shop_id, sales_rep_name, payment_amount, payment_method, notes, status, confirmed_by, confirmed_at)
                    VALUES (?, ?, ?, ?, 'CASH', ?, 'CONFIRMED', 'Front Office Admin', NOW())
                ", [$receiptNo, $id, $salesRepName, $paymentAmount, $notes]);

                $this->cryptoService->logAuditEvent(
                    'PAYMENT_RECORDED_DIRECT_ADMIN',
                    'Front Office Admin',
                    'admin',
                    "Shop #{$id} ({$shop['name']})",
                    array_merge($result, ['receiptNumber' => $receiptNo, 'notes' => $notes])
                );

                return response()->json([
                    'success' => true,
                    'is_pending' => false,
                    'message' => "Payment of Rs. " . number_format($paymentAmount, 2) . " applied immediately. New balance: Rs. " . number_format($result['newBalance'], 2),
                    'receipt' => [
                        'receiptNumber' => $receiptNo,
                        'shopId' => $id,
                        'shopName' => $shop['name'] ?? 'Retail Shop',
                        'paymentAmount' => $paymentAmount,
                        'previousBalance' => $result['previousBalance'],
                        'newBalance' => $result['newBalance'],
                        'status' => 'CONFIRMED',
                        'date' => date('Y-m-d H:i:s'),
                        'salesRep' => $salesRepName
                    ]
                ]);
            }

            // By Field Sales Rep: Maker-Checker Pattern (Requires Front Office Admin Approval)
            Database::execute("
                INSERT INTO payments (receipt_number, shop_id, sales_rep_name, payment_amount, payment_method, notes, status)
                VALUES (?, ?, ?, ?, 'CASH', ?, 'PENDING')
            ", [$receiptNo, $id, $salesRepName, $paymentAmount, $notes]);

            $this->cryptoService->logAuditEvent(
                'PAYMENT_SUBMITTED_PENDING_APPROVAL',
                $salesRepName,
                'sales_rep',
                "Shop #{$id} ({$shop['name']})",
                ['receiptNumber' => $receiptNo, 'amount' => $paymentAmount, 'status' => 'PENDING', 'notes' => $notes]
            );

            return response()->json([
                'success' => true,
                'is_pending' => true,
                'message' => "⏳ රු. " . number_format($paymentAmount, 2) . " ක ගෙවීම් රිසිට්පත (#{$receiptNo}) Front Office Admin අනුමැතිය සඳහා යොමු කරන ලදී.",
                'receipt' => [
                    'receiptNumber' => $receiptNo,
                    'shopId' => $id,
                    'shopName' => $shop['name'] ?? 'Retail Shop',
                    'ownerName' => $shop['owner_name'] ?? '',
                    'phone' => $shop['phone'] ?? '',
                    'paymentAmount' => $paymentAmount,
                    'previousBalance' => (float)$shop['outstanding_balance'],
                    'newBalance' => (float)$shop['outstanding_balance'], // Stays unchanged until admin confirms
                    'status' => 'PENDING_CONFIRMATION',
                    'date' => date('Y-m-d H:i:s'),
                    'salesRep' => $salesRepName,
                    'notes' => $notes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function createOrder(Request $request): JsonResponse
    {
        $input = $request->all();
        $dispatchId = (int)($input['dispatchId'] ?? 0);
        $shopId = (int)($input['shopId'] ?? 0);
        $paymentType = strtoupper($input['paymentType'] ?? 'CASH');
        $totalAmount = (float)($input['totalAmount'] ?? 0);
        $items = $input['items'] ?? [];

        if (!$dispatchId || !$shopId || !$totalAmount || empty($items)) {
            return response()->json(['success' => false, 'error' => 'Missing required order fields.'], 400);
        }

        $dispatch = Dispatch::findRecord($dispatchId);
        if (!$dispatch) {
            return response()->json(['success' => false, 'error' => 'Dispatch manifest not found.'], 404);
        }

        $totalCups = array_reduce($items, fn($sum, $i) => $sum + (int)($i['quantity'] ?? 0), 0);
        if (!Dispatch::hasSufficientStock($dispatch, $totalCups)) {
            return response()->json(['success' => false, 'error' => 'Insufficient stock remaining on vehicle!'], 400);
        }

        // Credit check
        if ($paymentType === 'CREDIT') {
            $creditCheck = $this->creditService->evaluateCreditEligibility($shopId, $totalAmount);
            if (!$creditCheck['allowed']) {
                return response()->json(['success' => false, 'error' => $creditCheck['reason']], 403);
            }
        }

        $signatureHash = $this->cryptoService->hashDigitalSignature($input['signatureImage'] ?? null);
        $invoiceNo = 'INV-' . date('Ymd') . '-' . rand(1000, 9999);

        // Deterministic SHA-256 HMAC Tamper Seal
        $tamperSeal = $this->cryptoService->generateTransactionSeal([
            'invoiceNumber' => $invoiceNo,
            'dispatchId' => $dispatchId,
            'shopId' => $shopId,
            'paymentType' => $paymentType,
            'totalAmount' => number_format($totalAmount, 2, '.', ''),
            'totalCups' => $totalCups,
            'signatureHash' => $signatureHash ?? 'NONE_CASH',
            'timestamp' => time()
        ]);

        try {
            $orderId = Order::createRecord([
                'invoiceNumber' => $invoiceNo,
                'dispatchId' => $dispatchId,
                'shopId' => $shopId,
                'paymentType' => $paymentType,
                'totalAmount' => $totalAmount,
                'items' => $items,
                'signatureHash' => $signatureHash,
                'signatureImage' => $input['signatureImage'] ?? null,
                'otpVerified' => $input['otpVerified'] ?? 0,
                'otpReferenceCode' => $input['otpReferenceCode'] ?? null,
                'securityTamperSeal' => $tamperSeal
            ]);

            $stockResult = Dispatch::decrementStock($dispatchId, $totalCups);

            if ($paymentType === 'CREDIT') {
                Shop::addCreditDebt($shopId, $totalAmount);
            }

            $this->cryptoService->logAuditEvent(
                $paymentType === 'CREDIT' ? 'CREDIT_SALE_ISSUED_MVC' : 'CASH_SALE_ISSUED_MVC',
                'Field Sales Rep',
                'sales_rep',
                "Invoice {$invoiceNo}",
                ['orderId' => $orderId, 'totalAmount' => $totalAmount, 'tamperSeal' => $tamperSeal]
            );

            return response()->json([
                'success' => true,
                'message' => "Order processed successfully. Invoice {$invoiceNo} issued.",
                'order' => [
                    'id' => $orderId,
                    'invoiceNumber' => $invoiceNo,
                    'totalAmount' => $totalAmount,
                    'paymentType' => $paymentType,
                    'remainingTruckStock' => $stockResult['remainingStock'],
                    'securityTamperSeal' => $tamperSeal,
                    'signatureHash' => $signatureHash
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
