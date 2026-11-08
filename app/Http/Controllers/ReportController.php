<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Export dynamic PDF inventory report.
     */
    public function exportInventoryPdf(Request $request): Response
    {
        $allowedTypes = ['all', 'products', 'stocks', 'low_stock', 'expiring_soon'];
        $type = $request->query('type', 'all');

        if (!in_array($type, $allowedTypes, true)) {
            $type = 'all';
        }

        // Low stock threshold
        $lowStockThreshold = 50;

        $now = Carbon::now()->startOfDay();
        $sevenDaysLater = Carbon::now()->addDays(7)->endOfDay();

        // 1. Products Summary
        $products = Product::with(['category', 'stocks'])
            ->orderBy('name')
            ->get();

        // 2. All Stock Batches
        $stocks = Stock::with(['product.category'])
            ->orderBy('expiry_date', 'asc')
            ->get();

        // 3. Low Stock Batches (at or below threshold)
        $lowStockBatches = $stocks->filter(function (Stock $stock) use ($lowStockThreshold) {
            return $stock->quantity <= $lowStockThreshold;
        });

        // 4. Expiring Soon Watchlist (within next 7 days or already expired)
        $expiringBatches = $stocks->filter(function (Stock $stock) use ($sevenDaysLater) {
            if (!$stock->expiry_date) {
                return false;
            }
            $exp = Carbon::parse($stock->expiry_date);
            return $exp->lessThanOrEqualTo($sevenDaysLater);
        });

        // KPI summary statistics
        $totalProducts = $products->count();
        $totalUnitsInStock = (int) $stocks->sum('quantity');
        $lowStockBatchesCount = $lowStockBatches->count();
        $expiringBatchesCount = $expiringBatches->count();

        // Human-friendly title and badges
        $reportTitles = [
            'all' => 'Comprehensive Inventory & Stocks Audit',
            'products' => 'Registered Products Directory & Stock Balances',
            'stocks' => 'Current Active Stock Batches & Receipts',
            'low_stock' => 'Low Stock & Safety Buffer Alerts',
            'expiring_soon' => 'Expiring Batches Watchlist (Next 7 Days)',
        ];

        $reportTitle = $reportTitles[$type] ?? 'Dairy Inventory Report';
        $userName = Auth::user()?->name ?? 'Authorized Personnel';
        $generatedAt = Carbon::now()->format('d M Y, h:i A');

        $pdf = Pdf::loadView('reports.inventory-pdf', compact(
            'type',
            'reportTitle',
            'products',
            'stocks',
            'lowStockBatches',
            'expiringBatches',
            'lowStockThreshold',
            'totalProducts',
            'totalUnitsInStock',
            'lowStockBatchesCount',
            'expiringBatchesCount',
            'userName',
            'generatedAt'
        ));

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isPhpEnabled' => true,
            'defaultFont' => 'Helvetica',
        ]);

        $filename = 'pulse_erp_dairy_report_' . $type . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->stream($filename);
    }
}
