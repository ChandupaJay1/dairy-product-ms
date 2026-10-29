<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2 text-sm">
            <span class="text-zinc-400 dark:text-zinc-500 font-mono">PulseERP</span>
            <span class="text-zinc-300 dark:text-zinc-600">/</span>
            <h2 class="text-sm font-bold text-zinc-800 dark:text-white tracking-tight transition-colors duration-300">
                {{ __('Products Directory') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6" x-data="{ 
        showAddModal: false,
        activeCategory: 'all',
        searchQuery: ''
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
                        Catalog Active &bull; 4 Dairy Items Listed
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                        Products Management
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
                        Configure dairy master products, category groups, packaging units, and standard consumer prices with zero complexity.
                    </p>
                </div>

                <!-- Primary CTA: Add New Product Button -->
                <div class="flex items-center gap-3 shrink-0">
                    <button type="button" 
                            @click="showAddModal = true"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-3.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 text-xs sm:text-sm font-bold shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30">
                        <svg class="w-4 h-4 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add New Product</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- SEARCH BAR & CATEGORY FILTER                              -->
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
                       placeholder="Search products..." 
                       class="w-full pl-9 pr-4 py-2 bg-white/80 dark:bg-zinc-800/40 border border-zinc-300/80 dark:border-zinc-700/50 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-xs rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-2 focus:ring-lime-400/20 backdrop-blur-md transition-all duration-300 shadow-sm" />
            </div>

            <!-- Category Filter Dropdown (Custom Alpine.js) -->
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
                        <span class="text-zinc-500 dark:text-zinc-400 font-normal">Category:</span>
                        <span x-text="activeCategory === 'all' ? 'All Products' : activeCategory"
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
                    
                    <!-- All Products -->
                    <button type="button" 
                            @click="activeCategory = 'all'; dropdownOpen = false"
                            :class="activeCategory === 'all' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            <span>All Products</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">4</span>
                            <svg x-show="activeCategory === 'all'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Yoghurt -->
                    <button type="button" 
                            @click="activeCategory = 'Yoghurt'; dropdownOpen = false"
                            :class="activeCategory === 'Yoghurt' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-pink-500 dark:bg-pink-400"></span>
                            <span>Yoghurt</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">1</span>
                            <svg x-show="activeCategory === 'Yoghurt'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Butter -->
                    <button type="button" 
                            @click="activeCategory = 'Butter'; dropdownOpen = false"
                            :class="activeCategory === 'Butter' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                            <span>Butter</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">1</span>
                            <svg x-show="activeCategory === 'Butter'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Curd -->
                    <button type="button" 
                            @click="activeCategory = 'Curd'; dropdownOpen = false"
                            :class="activeCategory === 'Curd' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-lime-600 dark:bg-lime-400"></span>
                            <span>Curd</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">1</span>
                            <svg x-show="activeCategory === 'Curd'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <!-- Drinks -->
                    <button type="button" 
                            @click="activeCategory = 'Drinks'; dropdownOpen = false"
                            :class="activeCategory === 'Drinks' 
                                ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                            class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                        <div class="flex items-center gap-2.5">
                            <span class="w-2 h-2 rounded-full bg-cyan-500 dark:bg-cyan-400"></span>
                            <span>Drinks</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-mono px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">1</span>
                            <svg x-show="activeCategory === 'Drinks'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MOBILE-FIRST PRODUCTS CARD GRID                           -->
        <!-- ========================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            <!-- ================= PRODUCT CARD 1 ================= -->
            <div x-show="(activeCategory === 'all' || activeCategory === 'Drinks') && (searchQuery === '' || 'vanilla fresh milk (bottle)'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                
                <div>
                    <!-- Card Top Meta: Category Badge & Status -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-cyan-700 dark:text-cyan-400">
                            DRINKS
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 text-[10px] font-bold text-lime-800 dark:text-lime-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            Active
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Vanilla Fresh Milk (Bottle)
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-MLK-01
                        </p>
                    </div>

                    <!-- Capacity Info Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400">Capacity</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">1 Liter</span>
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-baseline justify-between">
                        <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                            Unit Price
                        </span>
                        <div class="text-right">
                            <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                Rs. 420.00
                            </span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block font-medium">per bottle</span>
                        </div>
                    </div>
                </div>

                <!-- Touch-Friendly Card Actions (Edit & Delete) -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="showAddModal = true"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit</span>
                    </button>

                    <button type="button" 
                            title="Delete Product"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= PRODUCT CARD 2 ================= -->
            <div x-show="(activeCategory === 'all' || activeCategory === 'Curd') && (searchQuery === '' || 'natural set curd (clay pot)'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-100 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <div>
                    <!-- Card Top Meta -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-lime-700 dark:text-lime-400">
                            CURD
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 text-[10px] font-bold text-lime-800 dark:text-lime-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            Active
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Natural Set Curd (Clay Pot)
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-CRD-02
                        </p>
                    </div>

                    <!-- Capacity Info Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400">Capacity</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">450g</span>
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-baseline justify-between">
                        <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                            Unit Price
                        </span>
                        <div class="text-right">
                            <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                Rs. 360.00
                            </span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block font-medium">per pot</span>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="showAddModal = true"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit</span>
                    </button>

                    <button type="button" 
                            title="Delete Product"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= PRODUCT CARD 3 ================= -->
            <div x-show="(activeCategory === 'all' || activeCategory === 'Yoghurt') && (searchQuery === '' || 'strawberry yogurt (cup)'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-200 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <div>
                    <!-- Card Top Meta -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-pink-700 dark:text-pink-400">
                            YOGHURT
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 text-[10px] font-bold text-lime-800 dark:text-lime-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            Active
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Strawberry Yogurt (Cup)
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-YGT-03
                        </p>
                    </div>

                    <!-- Capacity Info Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400">Capacity</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">200ml</span>
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-baseline justify-between">
                        <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                            Unit Price
                        </span>
                        <div class="text-right">
                            <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                Rs. 140.00
                            </span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block font-medium">per bottle</span>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="showAddModal = true"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit</span>
                    </button>

                    <button type="button" 
                            title="Delete Product"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

            <!-- ================= PRODUCT CARD 4 ================= -->
            <div x-show="(activeCategory === 'all' || activeCategory === 'Butter') && (searchQuery === '' || 'salted pure butter (block)'.includes(searchQuery.toLowerCase()))"
                 class="animate-fade-slide-up animation-delay-300 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                
                <!-- Card Top Accent Sheen Line -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <div>
                    <!-- Card Top Meta -->
                    <div class="flex items-center justify-between gap-2 mb-4">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-amber-700 dark:text-amber-400">
                            BUTTER
                        </span>

                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 text-[10px] font-bold text-lime-800 dark:text-lime-400">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                            Active
                        </span>
                    </div>

                    <!-- Product Title -->
                    <div class="mb-3">
                        <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Salted Pure Butter (Block)
                        </h3>
                        <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                            SKU: DRY-BTR-04
                        </p>
                    </div>

                    <!-- Capacity Info Box -->
                    <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400">Capacity</span>
                            <span class="font-bold text-zinc-800 dark:text-zinc-200">200g</span>
                        </div>
                    </div>

                    <!-- Price Display -->
                    <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-baseline justify-between">
                        <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                            Unit Price
                        </span>
                        <div class="text-right">
                            <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                Rs. 750.00
                            </span>
                            <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block font-medium">per block</span>
                        </div>
                    </div>
                </div>

                <!-- Touch Actions -->
                <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                    <button type="button" 
                            @click="showAddModal = true"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                        <span>Edit</span>
                    </button>

                    <button type="button" 
                            title="Delete Product"
                            class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>

            </div>

        </div>

        <!-- ========================================================= -->
        <!-- ULTRA-SIMPLE "ADD PRODUCT" SLIDE-UP / MODAL (Alpine.js)   -->
        <!-- ========================================================= -->
        <div x-show="showAddModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showAddModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showAddModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showAddModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
                 class="relative w-full max-w-lg backdrop-blur-3xl bg-white/90 dark:bg-zinc-950/90 border border-zinc-200/80 dark:border-white/10 rounded-3xl p-6 sm:p-8 shadow-2xl overflow-hidden z-10 transition-colors">
                
                <!-- Modal Top Accent Sheen -->
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                <!-- Header with Close Button -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                            Create New Dairy Product
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Fill in simple product details to add it to your catalog.
                        </p>
                    </div>

                    <button @click="showAddModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Simple Mock Form -->
                <form @submit.prevent="showAddModal = false" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Product Name
                        </label>
                        <input type="text" 
                               placeholder="e.g. Vanilla Fresh Milk (Bottle)"
                               class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Category
                            </label>
                            <select class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm">
                                <option>Yoghurt</option>
                                <option>Butter</option>
                                <option>Curd</option>
                                <option>Drinks</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Unit Price (Rs.)
                            </label>
                            <input type="number" 
                                   placeholder="e.g. 450.00"
                                   step="0.01"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Capacity
                            </label>
                            <input type="text" 
                                   placeholder="e.g. 1 Liter, 500ml, 80g"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Reorder Threshold
                            </label>
                            <input type="number" 
                                   placeholder="e.g. 15 units"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:outline-none transition-all shadow-sm" />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="showAddModal = false"
                                class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            Cancel
                        </button>

                        <button type="submit" 
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md hover:scale-[1.02] active:scale-[0.98] transition-all">
                            Save Product
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</x-app-layout>
