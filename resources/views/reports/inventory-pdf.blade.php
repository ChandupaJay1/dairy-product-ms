<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $reportTitle }}</title>
    <style>
        @page {
            margin: 28px 30px 42px 30px;
        }

        * {
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif !important;
        }

        body {
            font-size: 9.5px;
            line-height: 1.4;
            color: #18181b;
            background-color: #ffffff;
            margin: 0;
            padding: 0;
        }

        /* Header block */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 2px solid #18181b;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }

        .header-table td {
            vertical-align: top;
        }

        .brand-badge {
            display: inline-block;
            background-color: #18181b;
            color: #ffffff;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 4px;
        }

        .brand-title {
            font-size: 16px;
            font-weight: bold;
            color: #09090b;
            margin: 0 0 2px 0;
            letter-spacing: -0.3px;
        }

        .brand-subtitle {
            font-size: 9px;
            color: #71717a;
            margin: 0;
        }

        .meta-box {
            text-align: right;
            font-size: 8.5px;
            color: #52525b;
            line-height: 1.45;
        }

        .meta-box strong {
            color: #18181b;
        }

        .report-badge {
            display: inline-block;
            background-color: #f4f4f5;
            border: 1px solid #d4d4d8;
            color: #27272a;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 3px 7px;
            border-radius: 4px;
            margin-top: 4px;
        }

        /* KPI Cards */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 6px;
            margin-bottom: 16px;
        }

        .kpi-card {
            background-color: #fafafa;
            border: 1px solid #e4e4e7;
            border-radius: 6px;
            padding: 8px 10px;
            vertical-align: top;
            width: 25%;
        }

        .kpi-card.alert-amber {
            background-color: #fffbeb;
            border-color: #fef08a;
        }

        .kpi-card.alert-red {
            background-color: #fff1f2;
            border-color: #fecdd3;
        }

        .kpi-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #71717a;
            margin-bottom: 4px;
        }

        .kpi-card.alert-amber .kpi-label {
            color: #92400e;
        }

        .kpi-card.alert-red .kpi-label {
            color: #9f1239;
        }

        .kpi-value {
            font-size: 15px;
            font-weight: bold;
            color: #09090b;
            line-height: 1.1;
        }

        .kpi-card.alert-amber .kpi-value {
            color: #b45309;
        }

        .kpi-card.alert-red .kpi-value {
            color: #be123c;
        }

        .kpi-desc {
            font-size: 7.5px;
            color: #a1a1aa;
            margin-top: 2px;
        }

        /* Section Headings */
        .section-header {
            margin-top: 14px;
            margin-bottom: 6px;
            padding-bottom: 4px;
            border-bottom: 1px solid #e4e4e7;
        }

        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #09090b;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            display: inline-block;
        }

        .section-count {
            font-size: 8.5px;
            color: #71717a;
            font-weight: normal;
            margin-left: 6px;
        }

        /* Data Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            table-layout: fixed;
            page-break-inside: auto;
        }

        .data-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .data-table th {
            background-color: #f4f4f5;
            color: #3f3f46;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 5px 6px;
            border-top: 1px solid #d4d4d8;
            border-bottom: 1.5px solid #a1a1aa;
            text-align: left;
        }

        .data-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #f0f0f2;
            font-size: 8.5px;
            color: #27272a;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .font-bold {
            font-weight: bold;
        }

        /* Status Pills */
        .pill {
            display: inline-block;
            font-size: 7px;
            font-weight: bold;
            padding: 2px 5px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            white-space: nowrap;
        }

        .pill-green {
            background-color: #ecfccb;
            color: #365314;
            border: 1px solid #bef264;
        }

        .pill-amber {
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fde047;
        }

        .pill-red {
            background-color: #ffe4e6;
            color: #9f1239;
            border: 1px solid #fecdd3;
        }

        .pill-gray {
            background-color: #f4f4f5;
            color: #52525b;
            border: 1px solid #e4e4e7;
        }

        .empty-row {
            text-align: center;
            color: #a1a1aa;
            font-style: italic;
            padding: 12px 6px;
        }

        /* Footer */
        .footer-table {
            position: fixed;
            bottom: -22px;
            left: 0;
            right: 0;
            width: 100%;
            border-collapse: collapse;
            border-top: 1px solid #e4e4e7;
            padding-top: 4px;
        }

        .footer-table td {
            font-size: 7.5px;
            color: #71717a;
            line-height: 1.3;
        }
    </style>
</head>
<body>

    <!-- Header Block -->
    <table class="header-table">
        <tr>
            <td style="width: 60%;">
                <div class="brand-badge">PulseERP Inventory Suite</div>
                <h1 class="brand-title">PulseERP — Dairy Inventory & Stock Audit Report</h1>
                <p class="brand-subtitle">
                    Official stock validation, lot balances, safety buffer telemetry, and batch expiration tracking.
                </p>
            </td>
            <td style="width: 40%;" class="meta-box">
                <div><strong>Generated:</strong> {{ $generatedAt }}</div>
                <div><strong>Authorized Officer:</strong> {{ $userName }}</div>
                <div><strong>System Host:</strong> PulseERP Dairy OS (v2.4)</div>
                <div>
                    <span class="report-badge">{{ $reportTitle }}</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- KPI Summary Row -->
    <table class="kpi-table">
        <tr>
            <td class="kpi-card">
                <div class="kpi-label">Registered Products</div>
                <div class="kpi-value">{{ number_format($totalProducts) }}</div>
                <div class="kpi-desc">Catalog classifications</div>
            </td>
            <td class="kpi-card">
                <div class="kpi-label">Total Units in Stock</div>
                <div class="kpi-value">{{ number_format($totalUnitsInStock) }}</div>
                <div class="kpi-desc">Across all active batches</div>
            </td>
            <td class="kpi-card alert-amber">
                <div class="kpi-label">Low Stock Batches</div>
                <div class="kpi-value">{{ number_format($lowStockBatchesCount) }}</div>
                <div class="kpi-desc">&le; {{ $lowStockThreshold }} units safety buffer</div>
            </td>
            <td class="kpi-card alert-red">
                <div class="kpi-label">Expiring Watchlist</div>
                <div class="kpi-value">{{ number_format($expiringBatchesCount) }}</div>
                <div class="kpi-desc">&le; 7 days to expiry or expired</div>
            </td>
        </tr>
    </table>

    <!-- TABLE 1: REGISTERED PRODUCTS OVERVIEW -->
    @if ($type === 'all' || $type === 'products')
        <div class="section-header">
            <span class="section-title">1. Registered Products Overview</span>
            <span class="section-count">({{ $products->count() }} Products Registered)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 25%;">Product Name</th>
                    <th style="width: 14%;">SKU</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 12%;">Unit Size</th>
                    <th style="width: 12%;" class="text-right">Unit Price</th>
                    <th style="width: 10%;" class="text-right">Stock</th>
                    <th style="width: 8%;" class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $index => $product)
                    @php
                        $stockSum = (int) $product->stocks->sum('quantity');
                    @endphp
                    <tr>
                        <td class="text-center" style="color: #71717a;">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $product->product_name ?: $product->name }}</td>
                        <td>{{ $product->sku_number ?: $product->sku }}</td>
                        <td>{{ $product->category ? $product->category->name : 'Unassigned' }}</td>
                        <td>{{ $product->capacity ?: 'N/A' }}</td>
                        <td class="text-right font-bold">LKR {{ number_format((float) $product->unit_price, 2) }}</td>
                        <td class="text-right font-bold">{{ number_format($stockSum) }}</td>
                        <td class="text-center">
                            @if ($product->status === 'Inactive')
                                <span class="pill pill-gray">Inactive</span>
                            @elseif ($stockSum <= 0)
                                <span class="pill pill-red">No Stock</span>
                            @elseif ($stockSum <= $lowStockThreshold)
                                <span class="pill pill-amber">Low Buffer</span>
                            @else
                                <span class="pill pill-green">Active</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-row">No registered dairy products found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- TABLE 2: CURRENT ACTIVE STOCK BATCHES -->
    @if ($type === 'all' || $type === 'stocks')
        <div class="section-header">
            <span class="section-title">2. Active Stock Batches & Inbound Receipts</span>
            <span class="section-count">({{ $stocks->count() }} Batches Logged)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 16%;">Batch Code</th>
                    <th style="width: 24%;">Product</th>
                    <th style="width: 14%;">Category</th>
                    <th style="width: 11%;" class="text-right">Available</th>
                    <th style="width: 11%;" class="text-right">Received</th>
                    <th style="width: 10%;" class="text-center">Mfg Date</th>
                    <th style="width: 10%;" class="text-center">Expiry Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($stocks as $index => $stock)
                    @php
                        $nowDate = \Carbon\Carbon::now()->startOfDay();
                        $isExpiring = $stock->expiry_date && \Carbon\Carbon::parse($stock->expiry_date)->lessThanOrEqualTo(\Carbon\Carbon::now()->addDays(7)->endOfDay());
                        $isLow = $stock->quantity <= $lowStockThreshold;
                    @endphp
                    <tr>
                        <td class="text-center" style="color: #71717a;">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $stock->batch_number }}</td>
                        <td>{{ $stock->product ? ($stock->product->product_name ?: $stock->product->name) : 'Product #' . $stock->product_id }}</td>
                        <td>{{ $stock->product && $stock->product->category ? $stock->product->category->name : 'Unassigned' }}</td>
                        <td class="text-right font-bold">
                            @if ($stock->quantity <= 0)
                                <span style="color: #be123c;">0 Units</span>
                            @elseif ($isLow)
                                <span style="color: #b45309;">{{ number_format($stock->quantity) }} Units</span>
                            @else
                                <span>{{ number_format($stock->quantity) }} Units</span>
                            @endif
                        </td>
                        <td class="text-right">{{ number_format($stock->received_quantity ?? $stock->quantity) }} Units</td>
                        <td class="text-center" style="color: #52525b;">
                            {{ $stock->manufacture_date ? \Carbon\Carbon::parse($stock->manufacture_date)->format('d M Y') : '—' }}
                        </td>
                        <td class="text-center font-bold">
                            @if ($stock->expiry_date)
                                @if (\Carbon\Carbon::parse($stock->expiry_date)->lessThan($nowDate))
                                    <span style="color: #be123c;">{{ \Carbon\Carbon::parse($stock->expiry_date)->format('d M Y') }} (Exp)</span>
                                @elseif ($isExpiring)
                                    <span style="color: #be123c;">{{ \Carbon\Carbon::parse($stock->expiry_date)->format('d M Y') }}</span>
                                @else
                                    <span style="color: #27272a;">{{ \Carbon\Carbon::parse($stock->expiry_date)->format('d M Y') }}</span>
                                @endif
                            @else
                                <span style="color: #a1a1aa;">N/A</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="empty-row">No active stock shipments recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- TABLE 3: LOW STOCK ALERTS -->
    @if ($type === 'all' || $type === 'low_stock')
        <div class="section-header">
            <span class="section-title" style="color: #92400e;">3. Low Stock & Safety Buffer Alerts (&le; {{ $lowStockThreshold }} Units)</span>
            <span class="section-count">({{ $lowStockBatches->count() }} Batches Flagged)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 18%;">Batch Code</th>
                    <th style="width: 32%;">Product Name</th>
                    <th style="width: 16%;" class="text-right">Available Qty</th>
                    <th style="width: 16%;" class="text-right">Threshold</th>
                    <th style="width: 14%;" class="text-center">Stock Alert Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($lowStockBatches as $index => $batch)
                    <tr>
                        <td class="text-center" style="color: #71717a;">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $batch->batch_number }}</td>
                        <td>{{ $batch->product ? ($batch->product->product_name ?: $batch->product->name) : 'Product #' . $batch->product_id }}</td>
                        <td class="text-right font-bold" style="color: {{ $batch->quantity <= 0 ? '#be123c' : '#b45309' }};">
                            {{ number_format($batch->quantity) }} Units
                        </td>
                        <td class="text-right" style="color: #71717a;">{{ $lowStockThreshold }} Units</td>
                        <td class="text-center">
                            @if ($batch->quantity <= 0)
                                <span class="pill pill-red">Depleted / 0 Stock</span>
                            @else
                                <span class="pill pill-amber">Safety Threshold Low</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row" style="color: #166534; background-color: #f0fdf4;">
                            All active stock batches exceed the minimum safety threshold ({{ $lowStockThreshold }} units).
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- TABLE 4: EXPIRING SOON WATCHLIST -->
    @if ($type === 'all' || $type === 'expiring_soon')
        <div class="section-header">
            <span class="section-title" style="color: #9f1239;">4. Expiring Soon Watchlist (Next 7 Days & Expired)</span>
            <span class="section-count">({{ $expiringBatches->count() }} Batches Under Watch)</span>
        </div>
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 18%;">Batch Code</th>
                    <th style="width: 28%;">Product Name</th>
                    <th style="width: 16%;" class="text-center">Expiry Date</th>
                    <th style="width: 18%;" class="text-center">Time Remaining</th>
                    <th style="width: 16%;" class="text-right">Units at Risk</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($expiringBatches as $index => $batch)
                    @php
                        $nowDate = \Carbon\Carbon::now()->startOfDay();
                        $expDate = \Carbon\Carbon::parse($batch->expiry_date)->startOfDay();
                        $diffInDays = $nowDate->diffInDays($expDate, false);
                    @endphp
                    <tr>
                        <td class="text-center" style="color: #71717a;">{{ $index + 1 }}</td>
                        <td class="font-bold">{{ $batch->batch_number }}</td>
                        <td>{{ $batch->product ? ($batch->product->product_name ?: $batch->product->name) : 'Product #' . $batch->product_id }}</td>
                        <td class="text-center font-bold" style="color: #9f1239;">
                            {{ $expDate->format('d M Y') }}
                        </td>
                        <td class="text-center">
                            @if ($diffInDays < 0)
                                <span class="pill pill-red">Expired ({{ abs($diffInDays) }}d ago)</span>
                            @elseif ($diffInDays === 0)
                                <span class="pill pill-red">Expires Today</span>
                            @elseif ($diffInDays === 1)
                                <span class="pill pill-amber">Expires Tomorrow</span>
                            @else
                                <span class="pill pill-amber">{{ $diffInDays }} days remaining</span>
                            @endif
                        </td>
                        <td class="text-right font-bold" style="color: #9f1239;">
                            {{ number_format($batch->quantity) }} Units
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="empty-row" style="color: #166534; background-color: #f0fdf4;">
                            No stock batches expiring within the next 7 days.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <!-- Footer with Timestamp & Page Numbering -->
    <table class="footer-table">
        <tr>
            <td style="width: 50%;">
                PulseERP Dairy Supply Chain • Generated: {{ $generatedAt }} • Confidential
            </td>
            <td style="width: 50%; text-align: right;">
                Dairy Inventory & Stock Audit Report • Page 1
            </td>
        </tr>
    </table>

    <script type="text/php">
    if (isset($pdf)) {
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}  •  PulseERP Dairy Operations  •  CONFIDENTIAL";
        $font = $fontMetrics->get_font("Helvetica", "normal");
        $size = 7.5;
        $color = array(0.45, 0.45, 0.45);
        $pdf->page_text(220, 816, $text, $font, $size, $color);
    }
    </script>
</body>
</html>

