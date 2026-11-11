<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-zinc-400 dark:text-zinc-500">PulseERP</span>
            <span class="text-zinc-300 dark:text-zinc-600">/</span>
            <h2 class="text-sm font-bold text-zinc-800 dark:text-white tracking-tight transition-colors duration-300">
                {{ __('Stocks Management') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{ 
        showReceiveModal: false,
        showAdjustModal: false,
        showHistoryModal: false,
        showDeleteModal: false,
        showExportModal: false,
        exportType: 'all',
        openExportModal() {
            this.exportType = 'all';
            this.showExportModal = true;
        },
        downloadExportPdf() {
            const url = '{{ route('reports.inventory-pdf') }}?type=' + encodeURIComponent(this.exportType);
            window.open(url, '_blank');
            this.showExportModal = false;
        },
        activeFilter: 'all',
        searchQuery: '',
        isReceiving: false,
        receiveErrors: {},
        isAdjusting: false,
        adjustErrors: {},
        adjustForm: {
            action_type: 'add',
            quantity: '',
            authorized_by: {{ json_encode(Auth::user()->name) }},
            reason: ''
        },
        selectedStock: {
            id: null,
            name: '',
            batch: '',
            qty: 0,
            unit: 'Units',
            packaging: '',
            maxCapacity: 100,
            adjustmentsCount: 0
        },
        historyStock: null,
        stocksState: {
            @foreach ($stocks as $s)
                {{ $s->id }}: {
                    id: {{ $s->id }},
                    name: {{ json_encode($s->product ? ($s->product->product_name ?: $s->product->name) : 'Product #' . $s->product_id) }},
                    batch: {{ json_encode($s->batch_number) }},
                    packaging: {{ json_encode($s->product && $s->product->capacity ? $s->product->capacity : 'Units') }},
                    unit: 'Units',
                    qty: {{ (int) $s->quantity }},
                    maxCapacity: {{ max((int) ($s->received_quantity ?? $s->quantity), (int) $s->quantity, 1) }},
                    adjustments: [
                        @foreach ($s->adjustments as $adj)
                            {
                                id: {{ $adj->id }},
                                action_type: {{ json_encode($adj->action_type) }},
                                quantity: {{ (int) $adj->quantity }},
                                previous_quantity: {{ (int) $adj->previous_quantity }},
                                new_quantity: {{ (int) $adj->new_quantity }},
                                authorized_by: {{ json_encode($adj->authorized_by) }},
                                reason: {{ json_encode($adj->reason) }},
                                created_at_formatted: {{ json_encode($adj->created_at ? $adj->created_at->format('d M Y, g:i A') : '') }}
                            },
                        @endforeach
                    ]
                },
            @endforeach
        },

        openAdjust(stock) {
            this.selectedStock = stock;
            this.adjustErrors = {};
            this.adjustForm.action_type = 'add';
            this.adjustForm.quantity = '';
            this.adjustForm.reason = '';
            this.showAdjustModal = true;
        },

        openHistory(stockId) {
            this.historyStock = this.stocksState[stockId] || null;
            this.showHistoryModal = true;
        },

        async submitAdjustForm() {
            if (!this.selectedStock || !this.selectedStock.id) return;
            this.adjustErrors = {};
            this.isAdjusting = true;

            try {
                const response = await fetch('/stocks/' + this.selectedStock.id + '/adjust', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        action_type: this.adjustForm.action_type,
                        quantity: this.adjustForm.quantity,
                        authorized_by: this.adjustForm.authorized_by,
                        reason: this.adjustForm.reason,
                    })
                });

                const data = await response.json();

                if (response.status === 422 && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) {
                        this.adjustErrors = {
                            [firstKey]: data.errors[firstKey][0]
                        };
                    }
                    return;
                }

                if (response.ok) {
                    if (window.showSuccessToast) {
                        window.showSuccessToast(data.message || 'Stock inventory adjusted successfully.');
                    }

                    const updatedStock = data.stock;
                    const updatedAdjustment = data.adjustment;

                    if (updatedStock && this.stocksState[updatedStock.id]) {
                        const newCapacity = Math.max(Number(updatedStock.received_quantity) || Number(updatedStock.quantity), Number(updatedStock.quantity), 1);
                        this.stocksState[updatedStock.id].qty = Number(updatedStock.quantity);
                        this.stocksState[updatedStock.id].maxCapacity = newCapacity;

                        if (updatedAdjustment) {
                            const nowFormatted = new Date().toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true });
                            this.stocksState[updatedStock.id].adjustments.unshift({
                                id: updatedAdjustment.id,
                                action_type: updatedAdjustment.action_type,
                                quantity: Number(updatedAdjustment.quantity),
                                previous_quantity: Number(updatedAdjustment.previous_quantity),
                                new_quantity: Number(updatedAdjustment.new_quantity),
                                authorized_by: updatedAdjustment.authorized_by,
                                reason: updatedAdjustment.reason,
                                created_at_formatted: nowFormatted
                            });
                        }

                        this.selectedStock.qty = Number(updatedStock.quantity);
                        this.selectedStock.maxCapacity = newCapacity;
                        this.selectedStock.adjustmentsCount = this.stocksState[updatedStock.id].adjustments.length;
                    }

                    this.showAdjustModal = false;
                    this.adjustForm.quantity = '';
                    this.adjustForm.reason = '';
                    this.adjustErrors = {};
                } else {
                    this.adjustErrors = {
                        reason: data.message || 'An error occurred while adjusting stock.'
                    };
                }
            } catch (err) {
                console.error('Adjust error:', err);
                this.adjustErrors = {
                    reason: 'An unexpected network error occurred.'
                };
            } finally {
                this.isAdjusting = false;
            }
        },
        showDeleteModal: false,
        deleteStockId: null,
        deleteStockProductName: '',
        deleteStockBatchNumber: '',
        isDeleting: false,
        deletedStockIds: [],

        confirmDelete(id, productName, batchNumber) {
            this.deleteStockId = id;
            this.deleteStockProductName = productName;
            this.deleteStockBatchNumber = batchNumber;
            this.showDeleteModal = true;
        },

        async submitDeleteStock() {
            if (!this.deleteStockId) return;
            this.isDeleting = true;

            try {
                const response = await fetch('/stocks/' + this.deleteStockId, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        '_token': '{{ csrf_token() }}',
                        '_method': 'DELETE'
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    this.deletedStockIds.push(this.deleteStockId);
                    this.showDeleteModal = false;
                    if (window.showSuccessToast) {
                        window.showSuccessToast(data.message || 'Stock deleted successfully');
                    }
                } else {
                    this.showDeleteModal = false;
                    if (window.showErrorToast) {
                        window.showErrorToast(data.message || 'Error deleting stock batch.');
                    }
                }
            } catch (err) {
                console.error('Delete stock error:', err);
                this.showDeleteModal = false;
                if (window.showErrorToast) {
                    window.showErrorToast('An unexpected network error occurred.');
                }
            } finally {
                this.isDeleting = false;
            }
        },
        async submitReceiveForm(event) {
            this.receiveErrors = {};
            this.isReceiving = true;
            const form = event.target;
            const formData = new FormData(form);

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData
                });

                const data = await response.json();

                if (response.status === 422 && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) {
                        this.receiveErrors = {
                            [firstKey]: data.errors[firstKey][0]
                        };
                        if (window.showErrorToast) {
                            window.showErrorToast(data.errors[firstKey][0]);
                        }
                    } else {
                        this.receiveErrors = {};
                    }
                } else if (response.ok) {
                    if (window.showSuccessToast) {
                        window.showSuccessToast(data.message || 'Stock received successfully');
                    }
                    this.receiveErrors = {};
                    this.showReceiveModal = false;
                    this.step = 1;
                    this.selectedProductId = null;
                    this.searchQuery = '';
                    form.reset();

                    setTimeout(() => {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 1000);
                } else {
                    if (window.showErrorToast) {
                        window.showErrorToast(data.message || 'An error occurred while receiving stock.');
                    }
                }
            } catch (err) {
                console.error(err);
                form.submit();
            } finally {
                this.isReceiving = false;
            }
        }
    }">

        <!-- ========================================================= -->
        <!-- PAGE HEADER & PRIMARY ACTION BANNER                       -->
        <!-- ========================================================= -->
        <div class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-6 sm:p-8 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300">
            <!-- Top Accent Gradient Sheen Line -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
            
            <!-- Soft Corner Ambient Glow -->
            <div class="absolute -right-20 -bottom-20 w-64 h-64 rounded-full bg-gradient-to-tl from-lime-400/10 via-amber-400/5 to-transparent blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-lime-50 dark:bg-zinc-900/60 border border-lime-200/80 dark:border-white/5 text-[11px] font-semibold text-lime-800 dark:text-lime-400 mb-3 shadow-sm transition-colors duration-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400 animate-pulse"></span>
                        Inventory Active &bull; {{ $totalBatches ?? 0 }} Production {{ Str::plural('Batch', $totalBatches ?? 0) }} Tracked
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                        Stocks Management
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
                        Track real-time inventory balances, inspect batch expiration deadlines, record inbound supply receipts, and adjust safety buffers with full traceability.
                    </p>
                </div>

                <!-- Primary CTAs: Export PDF Report & Add/Receive Stock -->
                <div class="flex items-center gap-3 shrink-0 flex-wrap">
                    <button type="button" 
                            @click="openExportModal()"
                            title="Export dynamic PDF audit report"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border border-zinc-300 dark:border-white/10 bg-white/70 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:text-red-600 dark:hover:text-red-400 hover:border-red-400/40 text-xs sm:text-sm font-bold shadow-sm backdrop-blur-md transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-red-400/20">
                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Export PDF Report</span>
                    </button>

                    <button type="button" 
                            @disabled(empty($hasProducts))
                            @click="{{ !empty($hasProducts) ? 'showReceiveModal = true' : '' }}"
                            title="{{ empty($hasProducts) ? 'Please register at least one product before receiving stock' : 'Add / Receive Stock' }}"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 text-xs sm:text-sm font-bold {{ !empty($hasProducts) ? 'shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30' : 'opacity-50 cursor-not-allowed shadow-none select-none' }}">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add / Receive Stock</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- QUICK TELEMETRY STATS CARDS                               -->
        <!-- ========================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <!-- Total Stock Available -->
            <div class="backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-4 sm:p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Total Units In Stock</span>
                    <span class="p-1.5 rounded-lg bg-emerald-100 dark:bg-emerald-500/10 text-emerald-700 dark:text-emerald-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">{{ number_format($totalUnits ?? 0) }}</span>
                    <span class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">Across {{ $totalBatches ?? 0 }} {{ Str::plural('Batch', $totalBatches ?? 0) }}</span>
                </div>
            </div>

            <!-- Low Stock Warnings -->
            <div class="backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-4 sm:p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Low Stock Threshold</span>
                    <span class="p-1.5 rounded-lg bg-amber-100 dark:bg-amber-500/10 text-amber-700 dark:text-amber-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">{{ $lowStockCount ?? 0 }} {{ Str::plural('Batch', $lowStockCount ?? 0) }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Require Reorder</span>
                </div>
            </div>

            <!-- Expiring Alerts -->
            <div class="backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-4 sm:p-5 shadow-sm transition-all duration-300">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-zinc-500 dark:text-zinc-400">Expiring Watchlist</span>
                    <span class="p-1.5 rounded-lg bg-rose-100 dark:bg-rose-500/10 text-rose-700 dark:text-rose-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 tracking-tight">{{ $expiringCount ?? 0 }} {{ Str::plural('Batch', $expiringCount ?? 0) }}</span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 font-medium">Next 7 Days</span>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- FILTERS & SEARCH BAR                                      -->
        <!-- ========================================================= -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            
            <!-- Live Search Bar -->
            <div class="relative flex-1">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text" 
                       x-model="searchQuery"
                       placeholder="Search products, batch, or SKU..." 
                       class="w-full pl-9 pr-4 py-2 bg-white/80 dark:bg-zinc-800/40 border border-zinc-300/80 dark:border-zinc-700/50 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-2 focus:ring-lime-400/20 backdrop-blur-md transition-all duration-300 shadow-sm" />
            </div>

            <!-- Filter Dropdown (Custom Alpine.js) -->
            <div x-data="{ dropdownOpen: false }" 
                 @keydown.escape.window="dropdownOpen = false" 
                 class="relative w-full sm:w-60 shrink-0">
                <button type="button" 
                        @click="dropdownOpen = !dropdownOpen" 
                        @click.outside="dropdownOpen = false"
                        class="w-full flex items-center justify-between pl-3.5 pr-3 py-2 bg-white/80 dark:bg-zinc-800/40 border border-zinc-300/80 dark:border-zinc-700/50 text-zinc-900 dark:text-zinc-100 text-xs rounded-xl hover:bg-white dark:hover:bg-zinc-800/70 focus:outline-none focus:border-lime-500 focus:ring-2 focus:ring-lime-400/20 backdrop-blur-md transition-all duration-200 shadow-sm cursor-pointer font-medium">
                    <div class="flex items-center gap-2 truncate">
                        <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                        </svg>
                        <span class="text-zinc-500 dark:text-zinc-400 font-normal">Filter:</span>
                        <span x-text="activeFilter === 'all' ? 'All Stocks ({{ $totalBatches ?? 0 }})' :
                                     activeFilter === 'in-stock' ? 'In Stock ({{ $inStockCount ?? 0 }})' :
                                     activeFilter === 'low-stock' ? 'Low Stock ({{ $lowStockCount ?? 0 }})' :
                                     activeFilter === 'expiring' ? 'Expiring Soon ({{ $expiringCount ?? 0 }})' :
                                     activeFilter === 'out-of-stock' ? 'Out of Stock ({{ $outOfStockCount ?? 0 }})' : 'All Stocks'"
                              class="font-semibold truncate"></span>
                    </div>
                    <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500 transition-transform duration-200 shrink-0 ml-1.5" 
                         :class="dropdownOpen ? 'rotate-180' : ''" 
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <!-- Dropdown Menu -->
                <div x-show="dropdownOpen" 
                     x-cloak
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-95"
                     class="absolute right-0 left-0 sm:left-auto sm:right-0 mt-1.5 w-full sm:w-60 backdrop-blur-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 rounded-2xl shadow-2xl p-1.5 z-30 space-y-0.5">
                    
                    <!-- All Stocks -->
                    <button type="button" 
                            @click="activeFilter = 'all'; dropdownOpen = false"
                            :class="activeFilter === 'all' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            <span>All Stocks</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $totalBatches ?? 0 }}</span>
                            <svg x-show="activeFilter === 'all'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- In Stock -->
                    <button type="button" 
                            @click="activeFilter = 'in-stock'; dropdownOpen = false"
                            :class="activeFilter === 'in-stock' 
                                ? 'bg-emerald-500/10 dark:bg-emerald-400/15 text-emerald-800 dark:text-emerald-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            <span>In Stock</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $inStockCount ?? 0 }}</span>
                            <svg x-show="activeFilter === 'in-stock'" class="w-3.5 h-3.5 text-emerald-600 dark:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Low Stock -->
                    <button type="button" 
                            @click="activeFilter = 'low-stock'; dropdownOpen = false"
                            :class="activeFilter === 'low-stock' 
                                ? 'bg-amber-500/10 dark:bg-amber-400/15 text-amber-800 dark:text-amber-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                            <span>Low Stock</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $lowStockCount ?? 0 }}</span>
                            <svg x-show="activeFilter === 'low-stock'" class="w-3.5 h-3.5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Expiring Soon -->
                    <button type="button" 
                            @click="activeFilter = 'expiring'; dropdownOpen = false"
                            :class="activeFilter === 'expiring' 
                                ? 'bg-rose-500/10 dark:bg-rose-400/15 text-rose-800 dark:text-rose-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-rose-500 dark:bg-rose-400"></span>
                            <span>Expiring Soon</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $expiringCount ?? 0 }}</span>
                            <svg x-show="activeFilter === 'expiring'" class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Out of Stock -->
                    <button type="button" 
                            @click="activeFilter = 'out-of-stock'; dropdownOpen = false"
                            :class="activeFilter === 'out-of-stock' 
                                ? 'bg-red-500/10 dark:bg-red-400/15 text-red-800 dark:text-red-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-zinc-400 dark:bg-zinc-600"></span>
                            <span>Out of Stock</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $outOfStockCount ?? 0 }}</span>
                            <svg x-show="activeFilter === 'out-of-stock'" class="w-3.5 h-3.5 text-red-600 dark:text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- RESPONSIVE GRID OF STOCK CARDS (1 Col Mobile, 2 Col Tablet, 3 Col Desktop) -->
        <!-- ========================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($stocks as $stock)
                @php
                    $product = $stock->product;
                    $productName = $product ? ($product->product_name ?: $product->name) : 'Product #' . $stock->product_id;
                    $sku = $product ? ($product->sku_number ?: ($product->sku ?: 'N/A')) : 'N/A';
                    $categoryName = $product && $product->category ? $product->category->name : 'Dairy Product';
                    $packaging = $product && $product->capacity ? $product->capacity : 'Standard Units';

                    $now = now()->startOfDay();
                    $sevenDaysLater = now()->addDays(7)->endOfDay();
                    $isExpiring = false;
                    $daysUntilExpiry = null;

                    if ($stock->expiry_date) {
                        $exp = \Carbon\Carbon::parse($stock->expiry_date);
                        $isExpiring = $exp->greaterThanOrEqualTo($now) && $exp->lessThanOrEqualTo($sevenDaysLater);
                        $daysUntilExpiry = (int) $now->diffInDays($exp, false);
                    }

                    if ($isExpiring) {
                        $statusSlug = 'expiring';
                        $statusLabel = 'Expiring Soon';
                        $statusBadgeClass = 'bg-rose-500/10 border-rose-500/20 text-rose-700 dark:text-rose-400';
                        $statusDotClass = 'bg-rose-500 dark:bg-rose-400 animate-ping';
                        $progressBarClass = 'bg-gradient-to-r from-rose-500 to-pink-500';
                        $quantityColorClass = 'text-zinc-900 dark:text-white';
                        $statusTextClass = 'font-semibold text-rose-600 dark:text-rose-400';
                        if ($daysUntilExpiry === 0) {
                            $statusNote = 'Expires today';
                        } elseif ($daysUntilExpiry < 0) {
                            $statusNote = 'Expired';
                        } else {
                            $statusNote = 'Expires in ' . $daysUntilExpiry . ' ' . \Illuminate\Support\Str::plural('day', $daysUntilExpiry);
                        }
                    } elseif ($stock->quantity <= 0) {
                        $statusSlug = 'out-of-stock';
                        $statusLabel = 'Out of Stock';
                        $statusBadgeClass = 'bg-red-500/10 border-red-500/20 text-red-700 dark:text-red-400';
                        $statusDotClass = 'bg-red-500 dark:bg-red-400';
                        $progressBarClass = 'bg-gradient-to-r from-red-500 to-zinc-500';
                        $quantityColorClass = 'text-red-600 dark:text-red-400';
                        $statusTextClass = 'font-semibold text-red-600 dark:text-red-400';
                        $statusNote = 'Out of Stock &bull; 0%';
                    } elseif ($stock->quantity <= $lowStockThreshold) {
                        $statusSlug = 'low-stock';
                        $statusLabel = 'Low Stock';
                        $statusBadgeClass = 'bg-amber-500/10 border-amber-500/20 text-amber-700 dark:text-amber-400';
                        $statusDotClass = 'bg-amber-500 dark:bg-amber-400 animate-pulse';
                        $progressBarClass = 'bg-gradient-to-r from-amber-500 to-yellow-400';
                        $quantityColorClass = 'text-amber-600 dark:text-amber-400';
                        $statusTextClass = 'font-semibold text-amber-600 dark:text-amber-400';
                        $percentageRaw = round(($stock->quantity / max((int) ($stock->received_quantity ?? $stock->quantity), (int) $stock->quantity, 1)) * 100);
                        $statusNote = 'Low Stock &bull; ' . $percentageRaw . '%';
                    } else {
                        $statusSlug = 'in-stock';
                        $statusLabel = 'In Stock';
                        $statusBadgeClass = 'bg-emerald-500/10 border-emerald-500/20 text-emerald-700 dark:text-emerald-400';
                        $statusDotClass = 'bg-emerald-500 dark:bg-emerald-400';
                        $progressBarClass = 'bg-gradient-to-r from-emerald-500 to-lime-400';
                        $quantityColorClass = 'text-zinc-900 dark:text-white';
                        $statusTextClass = 'font-medium text-emerald-700 dark:text-emerald-400';
                        $percentageRaw = round(($stock->quantity / max((int) ($stock->received_quantity ?? $stock->quantity), (int) $stock->quantity, 1)) * 100);
                        $statusNote = $percentageRaw . '% In Stock';
                    }

                    $maxCapacity = max((int) ($stock->received_quantity ?? $stock->quantity), (int) $stock->quantity, 1);
                    $percentage = min(100, max(0, round(($stock->quantity / $maxCapacity) * 100)));
                    $searchTerms = strtolower($productName . ' ' . $stock->batch_number . ' ' . $sku . ' ' . $categoryName . ' ' . $packaging);
                @endphp

                <div x-show="!deletedStockIds.includes({{ $stock->id }}) && (activeFilter === 'all' || activeFilter === '{{ $statusSlug }}') && (searchQuery === '' || '{{ addslashes($searchTerms) }}'.includes(searchQuery.toLowerCase()))"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                    
                    <!-- Card Top Accent Sheen Line -->
                    <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                    
                    <div>
                        <!-- Packaging Type & Status Badge -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                                {{ $packaging }}
                            </span>

                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full {{ $statusBadgeClass }} text-[10px] font-bold">
                                <span class="w-1.5 h-1.5 rounded-full {{ $statusDotClass }}"></span>
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <!-- Product Title -->
                        <div class="mb-3">
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                                {{ $productName }}
                            </h3>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                                SKU: {{ $sku }} &bull; {{ $categoryName }}
                            </p>
                        </div>

                        <!-- Batch Number & Expiry Date Box -->
                        <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                    <span class="font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">{{ $stock->batch_number }}</span>
                                </div>
                                <div class="flex items-center gap-1 text-[11px]">
                                    <svg class="w-3.5 h-3.5 {{ $isExpiring ? 'text-rose-500 dark:text-rose-400' : 'text-zinc-400 dark:text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $isExpiring ? 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' : 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' }}" />
                                    </svg>
                                    <span class="{{ $isExpiring ? 'text-rose-600 dark:text-rose-400 font-semibold' : 'text-zinc-500 dark:text-zinc-400 font-medium' }}">Exp:</span>
                                    <span class="{{ $isExpiring ? 'font-bold text-rose-700 dark:text-rose-400' : 'font-semibold text-zinc-800 dark:text-zinc-200' }}">
                                        {{ $stock->expiry_date ? \Carbon\Carbon::parse($stock->expiry_date)->format('d M Y') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Available Quantity & Progress Indicator -->
                        <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 space-y-2">
                            <div class="flex items-baseline justify-between">
                                <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                                    Available Quantity
                                </span>
                                <div class="text-right">
                                    <span class="text-xl font-extrabold {{ $quantityColorClass }} tracking-tight"
                                          x-text="stocksState[{{ $stock->id }}]?.qty ?? {{ (int) $stock->quantity }}">
                                        {{ number_format($stock->quantity) }}
                                    </span>
                                    <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Units</span>
                                </div>
                            </div>

                            <!-- Progress Bar / Capacity Indicator -->
                            <div class="space-y-1">
                                <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                    <div class="h-full rounded-full {{ $progressBarClass }}" 
                                         :style="'width: ' + Math.min(100, Math.round(((stocksState[{{ $stock->id }}]?.qty ?? {{ (int) $stock->quantity }}) / Math.max((stocksState[{{ $stock->id }}]?.maxCapacity ?? {{ $maxCapacity }}), 1)) * 100)) + '%'"
                                         style="width: {{ $percentage }}%"></div>
                                </div>
                                <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500">
                                    <span>Capacity: <span x-text="stocksState[{{ $stock->id }}]?.qty ?? {{ (int) $stock->quantity }}">{{ number_format($stock->quantity) }}</span> / <span x-text="stocksState[{{ $stock->id }}]?.maxCapacity ?? {{ $maxCapacity }}">{{ number_format($maxCapacity) }}</span></span>
                                    <span class="{{ $statusTextClass }}">{!! $statusNote !!}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Touch Actions -->
                    <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                        <button type="button" 
                                @click="openAdjust(stocksState[{{ $stock->id }}])"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span>Adjust Stock</span>
                        </button>

                        <!-- History / Logs Button -->
                        <button type="button" 
                                @click="openHistory({{ $stock->id }})"
                                title="View Adjustment History / Audit Logs"
                                class="relative p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-lime-600 dark:hover:text-lime-400 hover:border-lime-300 dark:hover:border-lime-400/30 hover:bg-lime-50 dark:hover:bg-lime-400/10 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-show="(stocksState[{{ $stock->id }}]?.adjustments?.length ?? {{ $stock->adjustments->count() }}) > 0"
                                  x-text="stocksState[{{ $stock->id }}]?.adjustments?.length ?? {{ $stock->adjustments->count() }}"
                                  class="absolute -top-1.5 -right-1.5 min-w-[18px] h-[18px] px-1 rounded-full bg-lime-500 text-zinc-950 font-bold text-[10px] flex items-center justify-center shadow-sm">
                                {{ $stock->adjustments->count() }}
                            </span>
                        </button>

                        <button type="button" 
                                @click="confirmDelete({{ $stock->id }}, '{{ addslashes($productName) }}', '{{ addslashes($stock->batch_number) }}')"
                                title="Delete Stock Batch"
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                </div>
            @empty
                <div class="col-span-full py-16 px-6 text-center backdrop-blur-3xl bg-white/40 dark:bg-zinc-950/40 border border-dashed border-zinc-300 dark:border-zinc-800 rounded-3xl">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">No Stock Batches Found</h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                        There are currently no inbound stock batches registered in the system. Click "Add / Receive Stock" to register your first shipment.
                    </p>
                </div>
            @endforelse











            @if ($stocks->isNotEmpty())
                <div x-show="deletedStockIds.length === {{ $stocks->count() }}"
                     x-cloak
                     class="col-span-full py-16 px-6 text-center backdrop-blur-3xl bg-white/40 dark:bg-zinc-950/40 border border-dashed border-zinc-300 dark:border-zinc-800 rounded-3xl">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-400">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">No Stock Batches Remaining</h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                        All stock batches have been removed from the current inventory.
                    </p>
                </div>
            @endif

            <!-- Empty State for Out of Stock -->
            <div x-show="activeFilter === 'out-of-stock' && {{ $outOfStockCount }} === 0"
                 x-cloak
                 class="col-span-full py-16 px-6 text-center backdrop-blur-3xl bg-white/40 dark:bg-zinc-950/40 border border-dashed border-zinc-300 dark:border-zinc-800 rounded-3xl">
                <div class="w-12 h-12 mx-auto mb-3 rounded-2xl bg-zinc-100 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 flex items-center justify-center text-zinc-400">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">No Out of Stock Items</h4>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                    All dairy product batches currently have available inventory.
                </p>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- 'ADD / RECEIVE STOCK' MODAL (Alpine.js)                   -->
        <!-- ========================================================= -->
        <div x-show="showReceiveModal" 
             x-cloak
             x-data="{ step: 1, selectedProductId: null, searchQuery: '' }"
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showReceiveModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showReceiveModal = false; step = 1; selectedProductId = null; searchQuery = ''"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showReceiveModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative w-full max-w-lg backdrop-blur-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden z-10 transition-colors">
                
                <!-- Modal Top Accent Sheen -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <!-- Header with Close Button and Step Indicator -->
                <div class="mb-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                                Receive New Stock Shipment
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                Register inbound dairy batches, assign serials, and log expiration dates.
                            </p>
                        </div>

                        <button @click="showReceiveModal = false; step = 1; selectedProductId = null; searchQuery = ''" 
                                type="button"
                                class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Step Progress Indicator -->
                    <div class="flex items-center gap-2 mt-4 pt-4 border-t border-zinc-200/80 dark:border-white/5">
                        <div class="flex items-center gap-2 flex-1">
                            <div :class="step >= 1 ? 'bg-lime-400 text-zinc-950 font-bold shadow-[0_0_12px_rgba(163,230,53,0.4)]' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-500'" 
                                 class="w-5 h-5 rounded-full text-[10px] flex items-center justify-center font-bold transition-all">
                                1
                            </div>
                            <span :class="step === 1 ? 'text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 dark:text-zinc-400'" 
                                  class="text-xs transition-colors">
                                Select Product
                            </span>
                        </div>
                        <div class="w-12 h-1 bg-zinc-200 dark:bg-zinc-800 rounded-full overflow-hidden">
                            <div class="h-full bg-lime-400 transition-all duration-300" :class="step === 2 ? 'w-full' : 'w-0'"></div>
                        </div>
                        <div class="flex items-center gap-2 flex-1 justify-end">
                            <div :class="step === 2 ? 'bg-lime-400 text-zinc-950 font-bold shadow-[0_0_12px_rgba(163,230,53,0.4)]' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-500'" 
                                 class="w-5 h-5 rounded-full text-[10px] flex items-center justify-center font-bold transition-all">
                                2
                            </div>
                            <span :class="step === 2 ? 'text-zinc-900 dark:text-white font-semibold' : 'text-zinc-500 dark:text-zinc-400'" 
                                  class="text-xs transition-colors">
                                Stock Details
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Receive Stock Form -->
                <form @submit.prevent="submitReceiveForm" action="{{ route('stocks.store') }}" method="POST" novalidate class="space-y-4">
                    @csrf

                    <!-- ===================================================== -->
                    <!-- STEP 1: PRODUCT SELECTION                             -->
                    <!-- ===================================================== -->
                    <div x-show="step === 1" class="space-y-4">
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                    Target Dairy Product <span class="text-red-500">*</span>
                                </label>
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                    Click a product card to select
                                </span>
                            </div>

                            <!-- Real-time Search Filter Input -->
                            <div class="relative mb-3">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input type="text" 
                                       x-model="searchQuery"
                                       placeholder="Search products by name or SKU..."
                                       class="w-full pl-9 pr-8 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-xs rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                                <button type="button" 
                                        x-show="searchQuery && searchQuery.length > 0" 
                                        @click="searchQuery = ''"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Clickable Product Cards List -->
                            <div class="space-y-2.5 max-h-72 sm:max-h-80 overflow-y-auto pr-1 -mr-1">
                                @if(isset($products) && $products->isNotEmpty())
                                    @foreach($products as $product)
                                        <div x-show="searchQuery === '' || '{{ strtolower(addslashes($product->product_name)) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(addslashes($product->sku_number)) }}'.includes(searchQuery.toLowerCase())"
                                             @click="selectedProductId = {{ $product->id }}; receiveErrors.product_id = null"
                                             :class="selectedProductId === {{ $product->id }} 
                                                ? 'border-lime-500 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 ring-2 ring-lime-400/50 shadow-[0_0_20px_-3px_rgba(163,230,53,0.3)]' 
                                                : 'border-zinc-200/80 dark:border-white/5 bg-zinc-50/70 dark:bg-zinc-900/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-100/70 dark:hover:bg-zinc-800/40'"
                                             class="group relative flex items-center justify-between p-3.5 rounded-2xl border cursor-pointer transition-all duration-200">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <div :class="selectedProductId === {{ $product->id }} ? 'bg-lime-400 text-zinc-950 font-bold' : 'bg-zinc-200 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-400'"
                                                     class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 transition-colors">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="flex items-center gap-2">
                                                        <h4 class="text-sm font-semibold text-zinc-900 dark:text-white truncate">
                                                            {{ $product->name }}
                                                        </h4>
                                                        @if(!empty($product->capacity))
                                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-zinc-200/80 dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300">
                                                                {{ $product->capacity }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                                        <span class="text-[11px]">{{ $product->sku }}</span>
                                                        @if($product->category)
                                                            <span>&bull;</span>
                                                            <span>{{ $product->category->name }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="shrink-0 ml-3">
                                                <div :class="selectedProductId === {{ $product->id }} ? 'border-lime-500 bg-lime-500 text-zinc-950' : 'border-zinc-300 dark:border-zinc-700 bg-transparent'"
                                                     class="w-5 h-5 rounded-full border-2 flex items-center justify-center transition-all">
                                                    <svg x-show="selectedProductId === {{ $product->id }}" class="w-3 h-3 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Empty State for Live Search -->
                                    <div x-show="searchQuery !== '' && ![
                                        @foreach($products as $product)
                                            '{{ strtolower(addslashes($product->product_name)) }}', '{{ strtolower(addslashes($product->sku_number)) }}',
                                        @endforeach
                                    ].some(term => term.includes(searchQuery.toLowerCase().trim()))" 
                                         class="text-center py-8 px-4 rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400">
                                        <svg class="w-6 h-6 mx-auto mb-2 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                        </svg>
                                        <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">No products found matching your search</p>
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1">Try searching with a different product name or SKU</p>
                                    </div>
                                @else
                                    <div class="text-center py-8 px-4 rounded-2xl border border-dashed border-zinc-300 dark:border-zinc-800 text-zinc-500 dark:text-zinc-400">
                                        <p class="text-xs font-semibold">No registered products found.</p>
                                        <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mt-1">Please register a dairy product first.</p>
                                    </div>
                                @endif
                            </div>
                            <p x-show="receiveErrors.product_id" x-text="Array.isArray(receiveErrors.product_id) ? receiveErrors.product_id[0] : receiveErrors.product_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <!-- Step 1 Footer Actions -->
                        <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                            <button type="button" 
                                    @click="showReceiveModal = false; step = 1; selectedProductId = null; searchQuery = ''"
                                    class="px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                                Cancel
                            </button>

                            <button type="button" 
                                    @click="step = 2"
                                    :disabled="!selectedProductId"
                                    :class="!selectedProductId ? 'opacity-40 cursor-not-allowed pointer-events-none shadow-none' : 'hover:scale-[1.02] active:scale-[0.98] shadow-[0_4px_15px_-3px_rgba(163,230,53,0.3)]'"
                                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs transition-all inline-flex items-center gap-1.5">
                                <span>Next</span>
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- ===================================================== -->
                    <!-- STEP 2: STOCK DETAILS                                 -->
                    <!-- ===================================================== -->
                    <div x-show="step === 2" x-cloak class="space-y-4">
                        <!-- Hidden input for product_id bound to selectedProductId -->
                        <input type="hidden" name="product_id" :value="selectedProductId" />

                        <!-- Selected Product Summary Pill -->
                        @if(isset($products) && $products->isNotEmpty())
                            @foreach($products as $product)
                                <div x-show="selectedProductId === {{ $product->id }}" class="p-3 rounded-xl bg-lime-400/10 border border-lime-400/20 flex items-center justify-between text-xs">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <span class="w-2 h-2 rounded-full bg-lime-400 animate-pulse shrink-0"></span>
                                        <span class="font-semibold text-zinc-900 dark:text-zinc-100 truncate">{{ $product->name }}</span>
                                        <span class="text-zinc-500 text-[11px] shrink-0">({{ $product->sku }})</span>
                                    </div>
                                    <button type="button" @click="step = 1" class="text-lime-600 dark:text-lime-400 hover:underline font-semibold text-[11px] shrink-0 ml-2">
                                        Change
                                    </button>
                                </div>
                            @endforeach
                        @endif

                        <p x-show="receiveErrors.product_id" x-text="Array.isArray(receiveErrors.product_id) ? receiveErrors.product_id[0] : receiveErrors.product_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Batch Number <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       name="batch_number"
                                       placeholder="e.g. BATCH-1052"
                                       required
                                       :class="receiveErrors.batch_number ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                                <p x-show="receiveErrors.batch_number" x-text="Array.isArray(receiveErrors.batch_number) ? receiveErrors.batch_number[0] : receiveErrors.batch_number" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Received Quantity <span class="text-red-500">*</span>
                                </label>
                                <input type="number" 
                                       name="received_quantity"
                                       placeholder="e.g. 150"
                                       required
                                       min="1"
                                       :class="(receiveErrors.received_quantity || receiveErrors.quantity) ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                                <p x-show="receiveErrors.received_quantity || receiveErrors.quantity" x-text="Array.isArray(receiveErrors.received_quantity) ? receiveErrors.received_quantity[0] : (receiveErrors.received_quantity || (Array.isArray(receiveErrors.quantity) ? receiveErrors.quantity[0] : receiveErrors.quantity))" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                            </div>
                        </div>

                        <!-- Manufacture Date and Expiry Date Row -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Manufacture Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="manufacture_date"
                                       required
                                       :class="receiveErrors.manufacture_date ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                                <p x-show="receiveErrors.manufacture_date" x-text="Array.isArray(receiveErrors.manufacture_date) ? receiveErrors.manufacture_date[0] : receiveErrors.manufacture_date" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                    Expiry Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="expiry_date"
                                       required
                                       :class="receiveErrors.expiry_date ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                                <p x-show="receiveErrors.expiry_date" x-text="Array.isArray(receiveErrors.expiry_date) ? receiveErrors.expiry_date[0] : receiveErrors.expiry_date" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                            </div>
                        </div>

                        <!-- Issued Date Row (Full Width) -->
                        <div class="col-span-full">
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Issued Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="issued_date"
                                   required
                                   :class="receiveErrors.issued_date ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                            <p x-show="receiveErrors.issued_date" x-text="Array.isArray(receiveErrors.issued_date) ? receiveErrors.issued_date[0] : receiveErrors.issued_date" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <!-- Step 2 Footer Actions -->
                        <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-between gap-3">
                            <button type="button" 
                                    @click="step = 1"
                                    class="px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors inline-flex items-center gap-1.5">
                                <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                                <span>Back</span>
                            </button>

                            <button type="submit" 
                                    @click="receiveErrors = {}"
                                    :disabled="isReceiving"
                                    :class="isReceiving ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                                    class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
                                <span x-text="isReceiving ? 'Receiving...' : 'Confirm Inbound Stock'">Confirm Inbound Stock</span>
                            </button>
                        </div>
                    </div>
                </form>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 'ADJUST STOCK' MODAL (Alpine.js)                          -->
        <!-- ========================================================= -->
        <div x-show="showAdjustModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showAdjustModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showAdjustModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showAdjustModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative w-full max-w-lg backdrop-blur-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden z-10 transition-colors">
                
                <!-- Modal Top Accent Sheen -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <!-- Header with Close Button -->
                <div class="flex items-center justify-between mb-5">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                            Adjust Stock Inventory
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Manual count correction or spoilage write-off.
                        </p>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <!-- History / Logs Button with Badge -->
                        <button type="button" 
                                @click="openHistory(selectedStock?.id)"
                                title="View Adjustment History / Audit Logs"
                                class="relative p-2 rounded-xl text-zinc-400 hover:text-lime-600 dark:hover:text-lime-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-show="(stocksState[selectedStock?.id]?.adjustments?.length ?? 0) > 0"
                                  x-text="stocksState[selectedStock?.id]?.adjustments?.length ?? 0"
                                  class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-lime-500 text-zinc-950 font-bold text-[10px] flex items-center justify-center shadow-sm">
                            </span>
                        </button>

                        <button @click="showAdjustModal = false" 
                                type="button"
                                class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Stock Summary Highlight Box -->
                <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 mb-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-white block" x-text="selectedStock.name"></span>
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400" x-text="selectedStock.batch + (selectedStock.packaging ? ' &bull; ' + selectedStock.packaging : '')"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-zinc-400 block font-medium">Current Stock</span>
                        <span class="text-base font-extrabold text-lime-700 dark:text-lime-400" x-text="selectedStock.qty + ' ' + (selectedStock.unit || 'Units')"></span>
                    </div>
                </div>

                <!-- Adjust Form -->
                <form @submit.prevent="submitAdjustForm()" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Action Type <span class="text-red-500">*</span>
                        </label>
                        <select x-model="adjustForm.action_type"
                                :class="adjustErrors.action_type ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm">
                            <option value="add">Audit Correction (+ Add Units)</option>
                            <option value="deduct">Audit Correction (- Deduct Units)</option>
                            <option value="spoilage">Spoilage / Damaged Goods</option>
                        </select>
                        <p x-show="adjustErrors.action_type" x-text="adjustErrors.action_type" class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Units Quantity <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   x-model="adjustForm.quantity"
                                   placeholder="e.g. 10"
                                   required
                                   min="1"
                                   :class="adjustErrors.quantity ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                            <p x-show="adjustErrors.quantity" x-text="adjustErrors.quantity" class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Authorized By <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   x-model="adjustForm.authorized_by"
                                   readonly
                                   :class="adjustErrors.authorized_by ? 'border-red-500 dark:border-red-500' : 'border-zinc-300 dark:border-zinc-800'"
                                   class="w-full px-4 py-2.5 bg-zinc-100/80 dark:bg-zinc-800/60 border text-zinc-600 dark:text-zinc-400 text-sm rounded-xl focus:outline-none" />
                            <p x-show="adjustErrors.authorized_by" x-text="adjustErrors.authorized_by" class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium"></p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Reason / Memo Notes <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="adjustForm.reason"
                               placeholder="e.g. Periodic cycle count discrepancy verified"
                               required
                               :class="adjustErrors.reason ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                               class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                        <p x-show="adjustErrors.reason" x-text="adjustErrors.reason" class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium"></p>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="showAdjustModal = false"
                                class="px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            Cancel
                        </button>

                        <button type="submit" 
                                :disabled="isAdjusting"
                                :class="isAdjusting ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
                            <span x-text="isAdjusting ? 'Saving...' : 'Save Adjustment'">Save Adjustment</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 'STOCK ADJUSTMENT HISTORY' MODAL (Alpine.js)               -->
        <!-- ========================================================= -->
        <div x-show="showHistoryModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showHistoryModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showHistoryModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showHistoryModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative w-full max-w-xl backdrop-blur-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden z-10 transition-colors">
                
                <!-- Modal Top Accent Sheen -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-lime-400 via-amber-400 to-transparent opacity-80"></div>

                <!-- Header with Close Button -->
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-lime-100 dark:bg-lime-400/10 border border-lime-300 dark:border-lime-400/20 flex items-center justify-center text-lime-600 dark:text-lime-400">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                                Stock Adjustment History
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Audit logs and inventory correction records.
                            </p>
                        </div>
                    </div>

                    <button @click="showHistoryModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Stock Summary Info Box -->
                <template x-if="historyStock">
                    <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 mb-5 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-zinc-900 dark:text-white block" x-text="historyStock.name"></span>
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400" x-text="historyStock.batch + (historyStock.packaging ? ' &bull; ' + historyStock.packaging : '')"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-xs text-zinc-400 block font-medium">Available Units</span>
                            <span class="text-base font-extrabold text-lime-600 dark:text-lime-400" x-text="historyStock.qty + ' Units'"></span>
                        </div>
                    </div>
                </template>

                <!-- Adjustment Timeline List -->
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    <template x-if="historyStock && historyStock.adjustments && historyStock.adjustments.length > 0">
                        <div class="space-y-3">
                            <template x-for="log in historyStock.adjustments" :key="log.id">
                                <div class="p-4 rounded-2xl bg-white/70 dark:bg-zinc-900/50 border border-zinc-200/80 dark:border-white/5 hover:border-zinc-300 dark:hover:border-white/10 transition-all space-y-2.5">
                                    <div class="flex items-center justify-between gap-2">
                                        <!-- Color-coded Action Badge -->
                                        <div class="flex items-center gap-2">
                                            <span :class="{
                                                'bg-lime-100 text-lime-800 dark:bg-lime-400/10 dark:text-lime-400 border-lime-300 dark:border-lime-400/20': log.action_type === 'add' || log.action_type === 'return',
                                                'bg-red-100 text-red-800 dark:bg-red-500/10 dark:text-red-400 border-red-300 dark:border-red-500/20': log.action_type === 'spoilage' || log.action_type === 'deduct',
                                                'bg-amber-100 text-amber-800 dark:bg-amber-400/10 dark:text-amber-400 border-amber-300 dark:border-amber-400/20': log.action_type !== 'add' && log.action_type !== 'return' && log.action_type !== 'spoilage' && log.action_type !== 'deduct'
                                            }" class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
                                                <span x-text="log.action_type === 'add' ? '+ Add' : (log.action_type === 'deduct' ? '- Deduct' : (log.action_type === 'spoilage' ? '- Spoilage' : (log.action_type === 'return' ? '+ Return' : log.action_type)))"></span>
                                            </span>
                                            <span class="text-xs font-bold"
                                                  :class="(log.action_type === 'add' || log.action_type === 'return') ? 'text-lime-600 dark:text-lime-400' : 'text-red-500 dark:text-red-400'"
                                                  x-text="((log.action_type === 'add' || log.action_type === 'return') ? '+' : '-') + log.quantity + ' Units'">
                                            </span>
                                        </div>

                                        <!-- Quantity Delta (220 -> 230) -->
                                        <div class="text-[11px] font-medium text-zinc-500 dark:text-zinc-400">
                                            <span x-text="log.previous_quantity"></span>
                                            <span class="text-zinc-400 dark:text-zinc-600 mx-1">&rarr;</span>
                                            <span class="font-bold text-zinc-800 dark:text-zinc-200" x-text="log.new_quantity"></span>
                                        </div>
                                    </div>

                                    <!-- Reason / Memo Notes in clean sans-serif -->
                                    <p class="text-xs text-zinc-800 dark:text-zinc-200 font-sans leading-relaxed" x-text="log.reason"></p>

                                    <!-- Subtle Footer: Authorized By & Formatted Timestamp -->
                                    <div class="flex items-center justify-between text-[11px] text-zinc-400 dark:text-zinc-500 pt-2 border-t border-zinc-100 dark:border-white/5">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5 text-zinc-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            <span>Authorized by: <strong class="text-zinc-600 dark:text-zinc-300 font-semibold" x-text="log.authorized_by"></strong></span>
                                        </div>
                                        <span class="text-[10px]" x-text="log.created_at_formatted"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- Empty State -->
                    <template x-if="!historyStock || !historyStock.adjustments || historyStock.adjustments.length === 0">
                        <div class="py-12 px-6 text-center rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/30 border border-dashed border-zinc-200 dark:border-zinc-800">
                            <div class="w-10 h-10 mx-auto mb-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h4 class="text-xs font-bold text-zinc-700 dark:text-zinc-300">No Adjustments Found</h4>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-1">
                                No adjustments recorded for this batch yet.
                            </p>
                        </div>
                    </template>
                </div>

                <!-- Footer Actions -->
                <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end">
                    <button type="button" 
                            @click="showHistoryModal = false"
                            class="px-5 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Close
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 'DELETE STOCK BATCH' CONFIRMATION MODAL (Alpine.js)       -->
        <!-- ========================================================= -->
        <div x-show="showDeleteModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showDeleteModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showDeleteModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showDeleteModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative w-full max-w-md backdrop-blur-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 rounded-3xl p-6 sm:p-7 shadow-2xl overflow-hidden z-10 transition-colors">
                
                <!-- Modal Top Accent Sheen (Rose/Red) -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-red-500 to-transparent opacity-80"></div>

                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-2xl bg-red-100 dark:bg-red-500/15 border border-red-300/80 dark:border-red-500/30 flex items-center justify-center shrink-0 text-red-600 dark:text-red-400 shadow-sm">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </div>

                    <div class="flex-1">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight">
                            Delete Stock Batch
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Are you sure you want to delete this stock batch?
                        </p>
                    </div>
                </div>

                <!-- Batch Info Box -->
                <div class="p-3.5 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/60 border border-zinc-200/60 dark:border-white/5 my-4">
                    <span class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500 block mb-0.5">PRODUCT & BATCH</span>
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200" x-text="deleteStockProductName + ' — ' + deleteStockBatchNumber"></span>
                </div>

                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mb-6">
                    This action cannot be undone and will permanently remove this stock batch from your inventory.
                </p>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-200/80 dark:border-white/5">
                    <button type="button" 
                            @click="showDeleteModal = false"
                            class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Cancel
                    </button>

                    <button type="button" 
                            @click="submitDeleteStock()"
                            :disabled="isDeleting"
                            :class="isDeleting ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold text-xs shadow-md shadow-red-500/20 hover:from-red-500 hover:to-rose-500 transition-all inline-flex items-center gap-2">
                        <span x-text="isDeleting ? 'Deleting...' : 'Delete Batch'">Delete Batch</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- 'EXPORT PDF REPORT' MODAL (Alpine.js)                     -->
        <!-- ========================================================= -->
        <div x-show="showExportModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showExportModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showExportModal = false"
                 class="fixed inset-0 bg-zinc-950/80 backdrop-blur-md transition-opacity"></div>

            <!-- Modal Panel -->
            <div x-show="showExportModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative w-full max-w-lg rounded-3xl bg-white/95 dark:bg-zinc-950/95 border border-zinc-200/80 dark:border-white/10 p-6 sm:p-7 shadow-2xl backdrop-blur-xl transition-all">
                
                <!-- Modal Header -->
                <div class="flex items-start justify-between pb-4 border-b border-zinc-200/80 dark:border-white/5">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-red-100 dark:bg-red-500/15 border border-red-300/80 dark:border-red-500/30 flex items-center justify-center shrink-0 text-red-600 dark:text-red-400 shadow-sm">
                            <svg class="w-5 h-5 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                                Export PDF Report
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Select an inventory scope to generate a print-ready audit document.
                            </p>
                        </div>
                    </div>

                    <button @click="showExportModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Scope Selection Form -->
                <div class="my-5 space-y-2.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-2">
                        Select Report Scope
                    </label>

                    <!-- Option 1: All -->
                    <label @click="exportType = 'all'"
                           :class="exportType === 'all' ? 'border-lime-500/80 bg-lime-500/10 dark:bg-lime-400/10 ring-1 ring-lime-400/30' : 'border-zinc-200 dark:border-white/5 bg-zinc-50/60 dark:bg-zinc-900/40 hover:bg-zinc-100/70 dark:hover:bg-zinc-900/80'"
                           class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all">
                        <input type="radio" name="exportScope" value="all" x-model="exportType" class="mt-1 text-lime-500 focus:ring-lime-400/30" />
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Full Inventory Audit Report</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-lime-500/20 text-lime-700 dark:text-lime-300">Recommended</span>
                            </div>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Complete summary with all KPIs, products overview, active lots, low stock alerts, and expiry watchlist.
                            </p>
                        </div>
                    </label>

                    <!-- Option 2: Products -->
                    <label @click="exportType = 'products'"
                           :class="exportType === 'products' ? 'border-lime-500/80 bg-lime-500/10 dark:bg-lime-400/10 ring-1 ring-lime-400/30' : 'border-zinc-200 dark:border-white/5 bg-zinc-50/60 dark:bg-zinc-900/40 hover:bg-zinc-100/70 dark:hover:bg-zinc-900/80'"
                           class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all">
                        <input type="radio" name="exportScope" value="products" x-model="exportType" class="mt-1 text-lime-500 focus:ring-lime-400/30" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Products Directory Only</span>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Complete list of registered products, categories, SKU identifiers, unit prices, and aggregated stock.
                            </p>
                        </div>
                    </label>

                    <!-- Option 3: Stocks -->
                    <label @click="exportType = 'stocks'"
                           :class="exportType === 'stocks' ? 'border-lime-500/80 bg-lime-500/10 dark:bg-lime-400/10 ring-1 ring-lime-400/30' : 'border-zinc-200 dark:border-white/5 bg-zinc-50/60 dark:bg-zinc-900/40 hover:bg-zinc-100/70 dark:hover:bg-zinc-900/80'"
                           class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all">
                        <input type="radio" name="exportScope" value="stocks" x-model="exportType" class="mt-1 text-lime-500 focus:ring-lime-400/30" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Active Stocks Only</span>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Detailed breakdown of current batches, batch numbers, initial receipts, available units, and dates.
                            </p>
                        </div>
                    </label>

                    <!-- Option 4: Low Stock -->
                    <label @click="exportType = 'low_stock'"
                           :class="exportType === 'low_stock' ? 'border-amber-500/80 bg-amber-500/10 dark:bg-amber-400/10 ring-1 ring-amber-400/30' : 'border-zinc-200 dark:border-white/5 bg-zinc-50/60 dark:bg-zinc-900/40 hover:bg-zinc-100/70 dark:hover:bg-zinc-900/80'"
                           class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all">
                        <input type="radio" name="exportScope" value="low_stock" x-model="exportType" class="mt-1 text-amber-500 focus:ring-amber-400/30" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Low Stock Alerts Only</span>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Focus report displaying critical batches operating at or below the 50 units safety threshold.
                            </p>
                        </div>
                    </label>

                    <!-- Option 5: Expiring Soon -->
                    <label @click="exportType = 'expiring_soon'"
                           :class="exportType === 'expiring_soon' ? 'border-red-500/80 bg-red-500/10 dark:bg-red-400/10 ring-1 ring-red-400/30' : 'border-zinc-200 dark:border-white/5 bg-zinc-50/60 dark:bg-zinc-900/40 hover:bg-zinc-100/70 dark:hover:bg-zinc-900/80'"
                           class="flex items-start gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all">
                        <input type="radio" name="exportScope" value="expiring_soon" x-model="exportType" class="mt-1 text-red-500 focus:ring-red-400/30" />
                        <div class="flex-1">
                            <span class="text-xs font-bold text-zinc-900 dark:text-zinc-100">Expiring Soon Batches Only</span>
                            <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Urgent inspection report on batches expiring within the next 7 days or already past expiration.
                            </p>
                        </div>
                    </label>
                </div>

                <!-- Modal Actions -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200/80 dark:border-white/5">
                    <button type="button" 
                            @click="showExportModal = false"
                            class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Cancel
                    </button>

                    <button type="button" 
                            @click="downloadExportPdf()"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md shadow-lime-500/20 hover:scale-[1.02] active:scale-[0.98] transition-all inline-flex items-center gap-2">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span>Download PDF</span>
                    </button>
                </div>

            </div>
        </div>

    </div>
</x-app-layout>
