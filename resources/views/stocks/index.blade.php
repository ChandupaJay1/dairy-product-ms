<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-zinc-400 dark:text-zinc-500 font-mono">PulseERP</span>
            <span class="text-zinc-300 dark:text-zinc-600">/</span>
            <h2 class="text-sm font-bold text-zinc-800 dark:text-white tracking-tight transition-colors duration-300">
                {{ __('Stocks Management') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{ 
        showReceiveModal: false,
        showAdjustModal: false,
        activeFilter: 'all',
        searchQuery: '',
        selectedStock: {
            name: 'Vanilla Fresh Milk',
            batch: 'BATCH-1042',
            qty: 320,
            unit: 'Bottles',
            packaging: 'Glass Bottle (1L)',
            maxCapacity: 400
        },
        openAdjust(stock) {
            this.selectedStock = stock;
            this.showAdjustModal = true;
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
                        Inventory Active &bull; 6 Production Batches Tracked
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                        Stocks Management
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
                        Track real-time inventory balances, inspect batch expiration deadlines, record inbound supply receipts, and adjust safety buffers with full traceability.
                    </p>
                </div>

                <!-- Primary CTA: Add/Receive Stock Button -->
                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" 
                            @click="showReceiveModal = true"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 text-xs sm:text-sm font-bold shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30">
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
                    <span class="text-2xl font-extrabold text-zinc-900 dark:text-white tracking-tight">671</span>
                    <span class="text-xs text-emerald-700 dark:text-emerald-400 font-medium">Across 6 Batches</span>
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
                    <span class="text-2xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">2 Batches</span>
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
                    <span class="text-2xl font-extrabold text-rose-600 dark:text-rose-400 tracking-tight">2 Batches</span>
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
                        <span x-text="activeFilter === 'all' ? 'All Stocks (6)' :
                                     activeFilter === 'in-stock' ? 'In Stock (2)' :
                                     activeFilter === 'low-stock' ? 'Low Stock (2)' :
                                     activeFilter === 'expiring' ? 'Expiring Soon (2)' :
                                     activeFilter === 'out-of-stock' ? 'Out of Stock (0)' : 'All Stocks'"
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
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">6</span>
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
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">2</span>
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
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">2</span>
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
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">2</span>
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
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">0</span>
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

            <!-- ================= STOCK CARD 1: IN STOCK ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'in-stock') && (searchQuery === '' || 'vanilla fresh milk batch-1042 dry-mlk-01 glass bottle'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Glass Bottle (1L)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            In Stock
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Vanilla Fresh Milk
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-MLK-01 &bull; Drinks
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-1042</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Exp:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">12 Nov 2026</span>
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
                                <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    320
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Bottles</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-lime-400" style="width: 80%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 320 / 400</span>
                                <span class="font-medium text-emerald-700 dark:text-emerald-400">80% In Stock</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Vanilla Fresh Milk', batch: 'BATCH-1042', qty: 320, unit: 'Bottles', packaging: 'Glass Bottle (1L)', maxCapacity: 400 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= STOCK CARD 2: LOW STOCK ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'low-stock') && (searchQuery === '' || 'natural set curd batch-0988 dry-crd-02 clay pot'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-100 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Clay Pot (450g)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                            Low Stock
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Natural Set Curd
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-CRD-02 &bull; Curd
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-0988</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Exp:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">04 Dec 2026</span>
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
                                <span class="text-xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">
                                    24
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Pots</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-yellow-400" style="width: 16%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 24 / 150</span>
                                <span class="font-semibold text-amber-600 dark:text-amber-400">Low Stock &bull; 16%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Natural Set Curd', batch: 'BATCH-0988', qty: 24, unit: 'Pots', packaging: 'Clay Pot (450g)', maxCapacity: 150 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= STOCK CARD 3: EXPIRING SOON ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'expiring') && (searchQuery === '' || 'strawberry yogurt batch-1015 dry-ygt-03 cup'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-200 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Plastic Cup (200ml)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-[10px] font-bold text-rose-700 dark:text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400 animate-ping"></span>
                            Expiring Soon
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Strawberry Yogurt
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-YGT-03 &bull; Yoghurt
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-1015</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-rose-600 dark:text-rose-400 font-semibold">Exp:</span>
                                <span class="font-bold text-rose-700 dark:text-rose-400">02 Nov 2026</span>
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
                                <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    85
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Cups</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-rose-500 to-pink-500" style="width: 42%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 85 / 200</span>
                                <span class="font-semibold text-rose-600 dark:text-rose-400">Expires in 4 days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Strawberry Yogurt', batch: 'BATCH-1015', qty: 85, unit: 'Cups', packaging: 'Plastic Cup (200ml)', maxCapacity: 200 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= STOCK CARD 4: IN STOCK ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'in-stock') && (searchQuery === '' || 'salted pure butter batch-1033 dry-btr-04 foil wrapped block'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-300 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Foil Wrapped Block (200g)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-500/10 border border-emerald-500/20 text-[10px] font-bold text-emerald-700 dark:text-emerald-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                            In Stock
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Salted Pure Butter
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-BTR-04 &bull; Butter
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-1033</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Exp:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">18 Jan 2027</span>
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
                                <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    190
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Blocks</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-lime-400" style="width: 76%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 190 / 250</span>
                                <span class="font-medium text-emerald-700 dark:text-emerald-400">76% In Stock</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Salted Pure Butter', batch: 'BATCH-1033', qty: 190, unit: 'Blocks', packaging: 'Foil Wrapped Block (200g)', maxCapacity: 250 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= STOCK CARD 5: LOW STOCK ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'low-stock') && (searchQuery === '' || 'pasteurized cow milk batch-1049 dry-mlk-05 tetra pak'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Tetra Pak (1L)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-500/10 border border-amber-500/20 text-[10px] font-bold text-amber-700 dark:text-amber-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                            Low Stock
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Pasteurized Cow Milk
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-MLK-05 &bull; Drinks
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-1049</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Exp:</span>
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">15 Dec 2026</span>
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
                                <span class="text-xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">
                                    12
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Packs</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-yellow-400" style="width: 8%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 12 / 150</span>
                                <span class="font-semibold text-amber-600 dark:text-amber-400">Critical &bull; 8%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Pasteurized Cow Milk', batch: 'BATCH-1049', qty: 12, unit: 'Packs', packaging: 'Tetra Pak (1L)', maxCapacity: 150 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= STOCK CARD 6: EXPIRING SOON ================= -->
            <div x-show="(activeFilter === 'all' || activeFilter === 'expiring') && (searchQuery === '' || 'greek style yogurt batch-1002 dry-ygt-06 tub'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-100 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Packaging Type & Status Badge -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-zinc-100 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-[11px] font-semibold text-zinc-700 dark:text-zinc-300">
                            Tub (500g)
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-rose-500/10 border border-rose-500/20 text-[10px] font-bold text-rose-700 dark:text-rose-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500 dark:bg-rose-400 animate-ping"></span>
                            Expiring Soon
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Greek Style Yogurt
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-YGT-06 &bull; Yoghurt
                        </p>
                    </div>

                    <!-- Batch Number & Expiry Date Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-1.5">
                                <span class="text-zinc-500 dark:text-zinc-400 font-medium">Batch:</span>
                                <span class="font-mono font-bold text-zinc-800 dark:text-zinc-200 bg-white/80 dark:bg-zinc-800/80 px-1.5 py-0.5 rounded border border-zinc-200/80 dark:border-zinc-700/50 text-[11px]">BATCH-1002</span>
                            </div>
                            <div class="flex items-center gap-1 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-rose-500 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span class="text-rose-600 dark:text-rose-400 font-semibold">Exp:</span>
                                <span class="font-bold text-rose-700 dark:text-rose-400">01 Nov 2026</span>
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
                                <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    40
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 font-medium ml-1">Tubs</span>
                            </div>
                        </div>

                        <!-- Progress Bar / Capacity Indicator -->
                        <div class="space-y-1">
                            <div class="w-full h-1.5 rounded-full bg-zinc-200/80 dark:bg-zinc-800/80 overflow-hidden">
                                <div class="h-full rounded-full bg-gradient-to-r from-rose-500 to-pink-500" style="width: 33%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[10px] text-zinc-400 dark:text-zinc-500 font-mono">
                                <span>Capacity: 40 / 120</span>
                                <span class="font-semibold text-rose-600 dark:text-rose-400">Expires in 3 days</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="openAdjust({ name: 'Greek Style Yogurt', batch: 'BATCH-1002', qty: 40, unit: 'Tubs', packaging: 'Tub (500g)', maxCapacity: 120 })"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Adjust Stock</span>
                    </button>

                    <button type="button" 
                            title="Delete Stock Batch"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- Empty State for Out of Stock -->
            <div x-show="activeFilter === 'out-of-stock'"
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
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showReceiveModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showReceiveModal = false"
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

                <!-- Header with Close Button -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                            Receive New Stock Shipment
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Register inbound dairy batches, assign serials, and log expiration dates.
                        </p>
                    </div>

                    <button @click="showReceiveModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Receive Stock Form -->
                <form @submit.prevent="showReceiveModal = false" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Target Dairy Product
                        </label>
                        <select class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm">
                            <option>Vanilla Fresh Milk (Bottle 1L)</option>
                            <option>Natural Set Curd (Clay Pot 450g)</option>
                            <option>Strawberry Yogurt (Cup 200ml)</option>
                            <option>Salted Pure Butter (Block 200g)</option>
                            <option>Pasteurized Cow Milk (Tetra Pak 1L)</option>
                            <option>Greek Style Yogurt (Tub 500g)</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Batch Number
                            </label>
                            <input type="text" 
                                   placeholder="e.g. BATCH-1052"
                                   required
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm font-mono" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Received Quantity
                            </label>
                            <input type="number" 
                                   placeholder="e.g. 150"
                                   required
                                   min="1"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Expiry Date
                            </label>
                            <input type="date" 
                                   required
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Storage Facility / Bin
                            </label>
                            <input type="text" 
                                   placeholder="e.g. Cold Room A-2"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="showReceiveModal = false"
                                class="px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            Cancel
                        </button>

                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Confirm Inbound Stock
                        </button>
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
                            Manual count correction, spoilage write-off, or return log.
                        </p>
                    </div>

                    <button @click="showAdjustModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Stock Summary Highlight Box -->
                <div class="p-3.5 rounded-2xl bg-zinc-100/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 mb-5 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-zinc-900 dark:text-white block" x-text="selectedStock.name"></span>
                        <span class="text-[11px] font-mono text-zinc-500 dark:text-zinc-400" x-text="selectedStock.batch + ' &bull; ' + selectedStock.packaging"></span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs text-zinc-400 block font-medium">Current Stock</span>
                        <span class="text-base font-extrabold text-lime-700 dark:text-lime-400 font-mono" x-text="selectedStock.qty + ' ' + selectedStock.unit"></span>
                    </div>
                </div>

                <!-- Adjust Form -->
                <form @submit.prevent="showAdjustModal = false" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Action Type
                        </label>
                        <select class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm">
                            <option>Audit Correction (+ Add Units)</option>
                            <option>Audit Correction (- Deduct Units)</option>
                            <option>Spoilage / Damaged Goods Write-Off</option>
                            <option>Customer Return / Quality Restock</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Units Quantity
                            </label>
                            <input type="number" 
                                   placeholder="e.g. 10"
                                   required
                                   min="1"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Authorized By
                            </label>
                            <input type="text" 
                                   value="{{ Auth::user()->name }}"
                                   readonly
                                   class="w-full px-4 py-2.5 bg-zinc-100/80 dark:bg-zinc-800/60 border border-zinc-300 dark:border-zinc-800 text-zinc-600 dark:text-zinc-400 text-sm rounded-xl focus:outline-none" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Reason / Memo Notes
                        </label>
                        <input type="text" 
                               placeholder="e.g. Periodic cycle count discrepancy verified"
                               required
                               class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="showAdjustModal = false"
                                class="px-4 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            Cancel
                        </button>

                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Save Adjustment
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</x-app-layout>
