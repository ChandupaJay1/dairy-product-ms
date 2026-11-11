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
        showAddModal: {{ $errors->any() ? 'true' : 'false' }},
        showEditModal: false,
        showDeleteModal: false,
        showCategoryModal: false,
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
        activeCategory: 'all',
        searchQuery: '',
        nextSku: '{{ $nextSku ?? 'DRY-0001' }}',
        skuNumber: '{{ $nextSku ?? 'DRY-0001' }}',
        errors: {},
        isSubmitting: false,
        editErrors: {},
        isUpdating: false,
        deleteProductId: null,
        deleteProductName: '',
        isDeleting: false,
        categoryForm: {
            name: ''
        },
        categoryErrors: {},
        isAddingCategory: false,
        isDeletingCategory: null,
        categoriesList: [
            @foreach ($categories as $cat)
                {
                    id: {{ $cat->id }},
                    name: {{ json_encode($cat->name) }},
                    slug: {{ json_encode($cat->slug) }},
                    products_count: {{ (int) ($cat->products_count ?? $cat->products->count()) }}
                },
            @endforeach
        ],
        editForm: {
            id: null,
            name: '',
            category_id: '',
            unit_price: '',
            capacity_value: '',
            capacity_unit: 'g',
            sku: ''
        },
        parseCapacity(capacity) {
            if (!capacity) return { value: '', unit: 'g' };
            const str = String(capacity).trim();
            const match = str.match(/^([\d.]+)\s*([a-zA-Z]+)?$/);
            if (match) {
                let val = match[1];
                let unit = (match[2] || 'g').toLowerCase();
                if (unit === 'liter' || unit === 'liters') unit = 'l';
                if (!['g', 'kg', 'ml', 'l'].includes(unit)) unit = 'g';
                return { value: val, unit: unit };
            }
            return { value: '', unit: 'g' };
        },
        openAddModal() {
            this.errors = {};
            this.skuNumber = this.nextSku;
            this.showAddModal = true;
            fetch('{{ route('products.generate-sku') }}')
                .then(res => res.json())
                .then(data => {
                    if (data && data.sku) {
                        this.nextSku = data.sku;
                        this.skuNumber = data.sku;
                    }
                })
                .catch(() => {});
        },
        openEditModal(product) {
            this.editErrors = {};
            const parsed = this.parseCapacity(product.capacity);
            this.editForm = {
                id: product.id,
                name: product.name,
                category_id: product.category_id,
                unit_price: product.unit_price,
                capacity_value: parsed.value,
                capacity_unit: parsed.unit,
                sku: product.sku
            };
            this.showEditModal = true;
        },
        confirmDelete(id, name) {
            this.deleteProductId = id;
            this.deleteProductName = name;
            this.showDeleteModal = true;
        },
        async submitForm(event) {
            this.errors = {};
            this.isSubmitting = true;

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
                        this.errors = {
                            [firstKey]: data.errors[firstKey][0]
                        };
                    }
                } else if (response.ok) {
                    this.showAddModal = false;
                    window.location.href = data.redirect || '{{ route('products.index') }}';
                } else {
                    window.showErrorToast(data.message || 'An unexpected error occurred.');
                }
            } catch (err) {
                console.error('Submission error:', err);
                window.showErrorToast('An unexpected network error occurred.');
            } finally {
                this.isSubmitting = false;
            }
        },
        async submitEditForm(event) {
            this.editErrors = {};
            this.isUpdating = true;

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
                        this.editErrors = {
                            [firstKey]: data.errors[firstKey][0]
                        };
                    }
                } else if (response.ok) {
                    this.showEditModal = false;
                    window.location.href = data.redirect || '{{ route('products.index') }}';
                } else {
                    window.showErrorToast(data.message || 'An unexpected error occurred.');
                }
            } catch (err) {
                console.error('Update error:', err);
                window.showErrorToast('An unexpected network error occurred.');
            } finally {
                this.isUpdating = false;
            }
        },
        async submitDelete() {
            if (!this.deleteProductId) return;
            this.isDeleting = true;

            try {
                const response = await fetch('/products/' + this.deleteProductId, {
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
                    this.showDeleteModal = false;
                    window.location.href = data.redirect || '{{ route('products.index') }}';
                } else {
                    this.showDeleteModal = false;
                    if (window.showErrorToast) {
                        window.showErrorToast(data.message || 'Cannot delete this product because it has active stock records in the system.');
                    }
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showDeleteModal = false;
                if (window.showErrorToast) {
                    window.showErrorToast('An unexpected network error occurred.');
                }
            } finally {
                this.isDeleting = false;
            }
        },
        openCategoryModal() {
            this.categoryErrors = {};
            this.categoryForm.name = '';
            this.showCategoryModal = true;
        },
        async submitAddCategory() {
            this.categoryErrors = {};
            if (!this.categoryForm.name || !this.categoryForm.name.trim()) {
                this.categoryErrors = { name: 'The category name field is required.' };
                return;
            }
            this.isAddingCategory = true;

            try {
                const response = await fetch('{{ route('categories.store') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        name: this.categoryForm.name.trim()
                    })
                });

                const data = await response.json();

                if (response.status === 422 && data.errors) {
                    const firstKey = Object.keys(data.errors)[0];
                    if (firstKey) {
                        this.categoryErrors = {
                            [firstKey]: data.errors[firstKey][0]
                        };
                    }
                    return;
                }

                if (response.ok && data.success) {
                    const newCategory = {
                        id: data.category.id,
                        name: data.category.name,
                        slug: data.category.slug,
                        products_count: data.category.products_count ?? 0
                    };
                    this.categoriesList.push(newCategory);
                    this.categoriesList.sort((a, b) => a.name.localeCompare(b.name));
                    this.categoryForm.name = '';
                    this.categoryErrors = {};
                    if (window.showSuccessToast) {
                        window.showSuccessToast(data.message || 'Category added successfully.');
                    }
                } else {
                    this.categoryErrors = {
                        name: data.message || 'An error occurred while adding the category.'
                    };
                }
            } catch (err) {
                console.error('Category add error:', err);
                this.categoryErrors = {
                    name: 'An unexpected network error occurred.'
                };
            } finally {
                this.isAddingCategory = false;
            }
        },
        async deleteCategory(id, name) {
            this.isDeletingCategory = id;

            try {
                const response = await fetch('/categories/' + id, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        '_method': 'DELETE'
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.categoriesList = this.categoriesList.filter(c => c.id !== id);
                    if (this.activeCategory === name) {
                        this.activeCategory = 'all';
                    }
                    if (window.showSuccessToast) {
                        window.showSuccessToast(data.message || 'Category deleted successfully.');
                    }
                } else {
                    if (window.showErrorToast) {
                        window.showErrorToast(data.message || 'Cannot delete this category because products are linked to it.');
                    }
                }
            } catch (err) {
                console.error('Category delete error:', err);
                if (window.showErrorToast) {
                    window.showErrorToast('An unexpected network error occurred.');
                }
            } finally {
                this.isDeletingCategory = null;
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
                        Catalog Active &bull; {{ $products->count() }} Dairy {{ Str::plural('Item', $products->count()) }} Listed
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                        Products Management
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
                        Configure dairy master products, category groups, packaging units, and standard consumer prices with zero complexity.
                    </p>
                </div>

                <!-- Primary Actions: Export PDF, Manage Categories & Add Product -->
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
                            @click="openCategoryModal()"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border border-zinc-300 dark:border-white/10 bg-white/70 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-800 text-zinc-700 dark:text-zinc-200 hover:text-lime-600 dark:hover:text-lime-400 hover:border-lime-400/40 text-xs sm:text-sm font-bold shadow-sm backdrop-blur-md transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/20">
                        <svg class="w-4 h-4 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Manage Categories</span>
                    </button>

                    <button type="button" 
                            @click="openAddModal()"
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
                            <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400">{{ $products->count() }}</span>
                            <svg x-show="activeCategory === 'all'" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </button>

                    <template x-for="cat in categoriesList" :key="cat.id">
                        <button type="button" 
                                @click="activeCategory = cat.name; dropdownOpen = false"
                                :class="activeCategory === cat.name 
                                    ? 'bg-lime-500/10 dark:bg-lime-400/15 text-lime-800 dark:text-lime-300 font-bold' 
                                    : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900/70 font-medium'"
                                class="w-full flex items-center justify-between px-3 py-2 rounded-xl text-xs transition-colors text-left group">
                            <div class="flex items-center gap-2.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 dark:bg-emerald-400"></span>
                                <span x-text="cat.name"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-[11px] px-1.5 py-0.5 rounded-md bg-zinc-100 dark:bg-zinc-900 border border-zinc-200/60 dark:border-white/5 text-zinc-500 dark:text-zinc-400" x-text="cat.products_count ?? 0"></span>
                                <svg x-show="activeCategory === cat.name" class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        </button>
                    </template>

                </div>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- MOBILE-FIRST PRODUCTS CARD GRID                           -->
        <!-- ========================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse ($products as $product)
                @php
                    $catName = $product->category->name ?? 'General';
                    $catColor = match($catName) {
                        'Drinks' => 'text-cyan-700 dark:text-cyan-400',
                        'Curd' => 'text-lime-700 dark:text-lime-400',
                        'Yoghurt' => 'text-pink-700 dark:text-pink-400',
                        'Butter' => 'text-amber-700 dark:text-amber-400',
                        default => 'text-emerald-700 dark:text-emerald-400',
                    };
                @endphp
                <div x-show="(activeCategory === 'all' || activeCategory === '{{ $catName }}') && (searchQuery === '' || '{{ strtolower(addslashes($product->name)) }}'.includes(searchQuery.toLowerCase()) || '{{ strtolower(addslashes($product->sku)) }}'.includes(searchQuery.toLowerCase()))"
                     class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-5 sm:p-6 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 hover:scale-[1.01] flex flex-col justify-between group">
                    
                    <!-- Card Top Accent Sheen Line -->
                    <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                    
                    <div>
                        <!-- Card Top Meta: Category Badge & Status -->
                        <div class="flex items-center justify-between gap-2 mb-4">
                            <span class="text-[11px] font-bold uppercase tracking-wider {{ $catColor }}">
                                {{ $catName }}
                            </span>

                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 text-[10px] font-bold text-lime-800 dark:text-lime-400">
                                <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                                {{ $product->status ?? 'Active' }}
                            </span>
                        </div>

                        <!-- Product Title -->
                        <div class="mb-3">
                            <h3 class="text-base font-bold text-zinc-900 dark:text-white tracking-tight truncate group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                                {{ $product->name }}
                            </h3>
                            <p class="text-xs font-mono text-zinc-400 dark:text-zinc-500 mt-0.5 truncate">
                                SKU: {{ $product->sku }}
                            </p>
                        </div>

                        <!-- Capacity Info Box -->
                        <div class="p-3 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/5 my-3">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">Capacity</span>
                                <span class="font-bold text-zinc-800 dark:text-zinc-200">{{ $product->capacity }}</span>
                            </div>
                        </div>

                        <!-- Price Display -->
                        <div class="mt-3 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-baseline justify-between">
                            <span class="text-[11px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 font-semibold">
                                Unit Price
                            </span>
                            <div class="text-right">
                                <span class="text-xl font-extrabold text-zinc-900 dark:text-white tracking-tight">
                                    Rs. {{ number_format($product->unit_price, 2) }}
                                </span>
                                <span class="text-[10px] text-zinc-500 dark:text-zinc-400 block font-medium">per unit</span>
                            </div>
                        </div>
                    </div>

                    <!-- Touch Actions -->
                    <div class="mt-5 pt-3 border-t border-zinc-200/70 dark:border-white/5 flex items-center gap-2">
                        <button type="button" 
                                @click="openEditModal({ id: {{ $product->id }}, name: '{{ addslashes($product->name) }}', sku: '{{ $product->sku }}', category_id: {{ $product->category_id }}, unit_price: {{ $product->unit_price }}, capacity: '{{ addslashes($product->capacity) }}' })"
                                class="flex-1 inline-flex items-center justify-center gap-1.5 py-2.5 px-3 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-xs font-bold text-zinc-700 dark:text-zinc-300 hover:text-amber-600 dark:hover:text-amber-400 hover:border-amber-300 dark:hover:border-amber-400/30 hover:bg-amber-50/50 dark:hover:bg-amber-400/5 transition-all shadow-sm">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                            <span>Edit</span>
                        </button>

                        <button type="button" 
                                @click="confirmDelete({{ $product->id }}, '{{ addslashes($product->name) }}')"
                                title="Delete Product"
                                class="p-2.5 rounded-xl bg-white dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-800 text-zinc-700 dark:text-zinc-300 hover:text-red-600 dark:hover:text-red-400 hover:border-red-300 dark:hover:border-red-400/30 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors shadow-sm">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>

                </div>
            @empty
                <div class="col-span-full py-16 px-6 text-center backdrop-blur-3xl bg-white/50 dark:bg-zinc-950/40 border border-dashed border-zinc-300 dark:border-zinc-800 rounded-3xl">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center text-zinc-400">
                        <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-zinc-800 dark:text-zinc-200">No dairy products found</h4>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 max-w-sm mx-auto">
                        Your catalog is currently empty. Click "Add New Product" above to create your first product.
                    </p>
                    <button type="button" 
                            @click="openAddModal()" 
                            class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-lime-500/10 dark:bg-lime-400/15 border border-lime-500/30 text-lime-800 dark:text-lime-300 font-bold text-xs hover:bg-lime-500/20 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add First Product</span>
                    </button>
                </div>
            @endforelse
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
                <form @submit.prevent="submitForm" method="POST" action="{{ route('products.store') }}" novalidate class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name"
                               value="{{ old('name') }}"
                               placeholder="e.g. Vanilla Fresh Milk (Bottle)"
                               :class="errors.name ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                               class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                        <p x-show="errors.name" x-text="errors.name" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" 
                                    :class="errors.category_id ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                    class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm">
                                <option value="" disabled selected>Select a category</option>
                                <template x-for="category in categoriesList" :key="category.id">
                                    <option :value="category.id" x-text="category.name"></option>
                                </template>
                            </select>
                            <p x-show="errors.category_id" x-text="errors.category_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Unit Price (Rs.) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="unit_price"
                                   value="{{ old('unit_price') }}"
                                   placeholder="e.g. 450.00"
                                   step="0.01"
                                   :class="errors.unit_price ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                            <p x-show="errors.unit_price" x-text="errors.unit_price" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Capacity <span class="text-red-500">*</span>
                            </label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" 
                                       name="capacity_value"
                                       value="{{ old('capacity_value') }}"
                                       placeholder="e.g. 500"
                                       step="any"
                                       :class="errors.capacity_value ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="flex-1 min-w-0 px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-r-0 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-l-xl focus:ring-4 focus:outline-none focus:z-10 transition-all" />
                                <select name="capacity_unit"
                                        :class="errors.capacity_unit ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                        class="w-24 px-3 py-2.5 bg-zinc-50/90 dark:bg-zinc-800/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-r-xl focus:ring-4 focus:outline-none focus:z-10 transition-all cursor-pointer font-medium">
                                    <option value="g" {{ old('capacity_unit') == 'g' ? 'selected' : '' }}>g</option>
                                    <option value="kg" {{ old('capacity_unit') == 'kg' ? 'selected' : '' }}>kg</option>
                                    <option value="ml" {{ old('capacity_unit') == 'ml' ? 'selected' : '' }}>ml</option>
                                    <option value="l" {{ old('capacity_unit') == 'l' ? 'selected' : '' }}>l</option>
                                </select>
                            </div>
                            <p x-show="errors.capacity_value || errors.capacity_unit || errors.capacity" 
                               x-text="errors.capacity_value || errors.capacity_unit || errors.capacity" 
                               class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                    SKU Number
                                </label>
                                <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 font-medium">Auto-generated</span>
                            </div>
                            <input type="text" 
                                   :value="skuNumber"
                                   x-model="skuNumber"
                                   placeholder="Auto-generated (e.g. DRY-0001)"
                                   readonly
                                   disabled
                                   class="w-full px-4 py-2.5 bg-zinc-100/90 dark:bg-zinc-950/70 border border-zinc-300/60 dark:border-zinc-800/80 text-zinc-800 dark:text-zinc-200 font-bold placeholder-zinc-400/90 dark:placeholder-zinc-600 text-sm rounded-xl cursor-not-allowed select-none opacity-80 shadow-sm font-mono focus:outline-none" />
                            <input type="hidden" name="sku" :value="skuNumber" />
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
                                :disabled="isSubmitting"
                                :class="isSubmitting ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
                            <span x-text="isSubmitting ? 'Saving...' : 'Save Product'">Save Product</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- "EDIT PRODUCT" SLIDE-UP / MODAL (Alpine.js)               -->
        <!-- ========================================================= -->
        <div x-show="showEditModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showEditModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showEditModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showEditModal" 
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
                            Edit Dairy Product
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Update product details in your catalog.
                        </p>
                    </div>

                    <button @click="showEditModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Edit Form -->
                <form @submit.prevent="submitEditForm" :action="'/products/' + editForm.id" method="POST" novalidate class="space-y-4">
                    @csrf
                    @method('PUT')

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                            Product Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               name="name"
                               x-model="editForm.name"
                               placeholder="e.g. Vanilla Fresh Milk (Bottle)"
                               :class="editErrors.name ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                               class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                        <p x-show="editErrors.name" x-text="editErrors.name" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Category <span class="text-red-500">*</span>
                            </label>
                            <select name="category_id" 
                                    x-model="editForm.category_id"
                                    :class="editErrors.category_id ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                    class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm">
                                <option value="" disabled>Select a category</option>
                                <template x-for="category in categoriesList" :key="category.id">
                                    <option :value="category.id" x-text="category.name" :selected="editForm.category_id == category.id"></option>
                                </template>
                            </select>
                            <p x-show="editErrors.category_id" x-text="editErrors.category_id" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Unit Price (Rs.) <span class="text-red-500">*</span>
                            </label>
                            <input type="number" 
                                   name="unit_price"
                                   x-model="editForm.unit_price"
                                   placeholder="e.g. 450.00"
                                   step="0.01"
                                   :class="editErrors.unit_price ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                            <p x-show="editErrors.unit_price" x-text="editErrors.unit_price" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                                Capacity <span class="text-red-500">*</span>
                            </label>
                            <div class="flex rounded-xl shadow-sm">
                                <input type="number" 
                                       name="capacity_value"
                                       x-model="editForm.capacity_value"
                                       placeholder="e.g. 500"
                                       step="any"
                                       :class="editErrors.capacity_value ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                       class="flex-1 min-w-0 px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-r-0 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-l-xl focus:ring-4 focus:outline-none focus:z-10 transition-all" />
                                <select name="capacity_unit"
                                        x-model="editForm.capacity_unit"
                                        :class="editErrors.capacity_unit ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                        class="w-24 px-3 py-2.5 bg-zinc-50/90 dark:bg-zinc-800/80 border text-zinc-900 dark:text-zinc-100 text-sm rounded-r-xl focus:ring-4 focus:outline-none focus:z-10 transition-all cursor-pointer font-medium">
                                    <option value="g">g</option>
                                    <option value="kg">kg</option>
                                    <option value="ml">ml</option>
                                    <option value="l">l</option>
                                </select>
                            </div>
                            <p x-show="editErrors.capacity_value || editErrors.capacity_unit || editErrors.capacity" 
                               x-text="editErrors.capacity_value || editErrors.capacity_unit || editErrors.capacity" 
                               class="text-red-500 dark:text-red-400 text-xs font-medium mt-1"></p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                                    SKU Number
                                </label>
                                <span class="text-[10px] font-mono text-zinc-400 dark:text-zinc-500 font-medium">Read-only</span>
                            </div>
                            <input type="text" 
                                   :value="editForm.sku"
                                   readonly
                                   disabled
                                   class="w-full px-4 py-2.5 bg-zinc-100/90 dark:bg-zinc-950/70 border border-zinc-300/60 dark:border-zinc-800/80 text-zinc-800 dark:text-zinc-200 font-bold placeholder-zinc-400/90 dark:placeholder-zinc-600 text-sm rounded-xl cursor-not-allowed select-none opacity-80 shadow-sm font-mono focus:outline-none" />
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end gap-3">
                        <button type="button" 
                                @click="showEditModal = false"
                                class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                            Cancel
                        </button>

                        <button type="submit" 
                                :disabled="isUpdating"
                                :class="isUpdating ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                                class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md transition-all inline-flex items-center gap-2">
                            <span x-text="isUpdating ? 'Updating...' : 'Update Product'">Update Product</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- "DELETE PRODUCT" CONFIRMATION MODAL (Alpine.js)           -->
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
                            Delete Dairy Product
                        </h3>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                            Are you sure you want to delete this product?
                        </p>
                    </div>
                </div>

                <!-- Product Name Box -->
                <div class="p-3.5 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/60 border border-zinc-200/60 dark:border-white/5 my-4">
                    <span class="text-[10px] uppercase font-bold text-zinc-400 dark:text-zinc-500 block mb-0.5">Product</span>
                    <span class="text-xs font-bold text-zinc-800 dark:text-zinc-200" x-text="deleteProductName"></span>
                </div>

                <p class="text-[11px] text-zinc-400 dark:text-zinc-500 mb-6">
                    This action cannot be undone and will permanently remove this dairy product from your catalog.
                </p>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 pt-3 border-t border-zinc-200/80 dark:border-white/5">
                    <button type="button" 
                            @click="showDeleteModal = false"
                            class="px-4 py-2.5 rounded-xl border border-zinc-200 dark:border-zinc-800 text-xs font-semibold text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Cancel
                    </button>

                    <button type="button" 
                            @click="submitDelete()"
                            :disabled="isDeleting"
                            :class="isDeleting ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                            class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-red-600 to-rose-600 text-white font-bold text-xs shadow-md shadow-red-500/20 hover:from-red-500 hover:to-rose-500 transition-all inline-flex items-center gap-2">
                        <span x-text="isDeleting ? 'Deleting...' : 'Delete Product'">Delete Product</span>
                    </button>
                </div>

            </div>
        </div>

        <!-- ========================================================= -->
        <!-- "MANAGE CATEGORIES" MODAL (Alpine.js)                     -->
        <!-- ========================================================= -->
        <div x-show="showCategoryModal" 
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 flex items-center justify-center">
            
            <!-- Frosted Backdrop -->
            <div x-show="showCategoryModal" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="showCategoryModal = false"
                 class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm"></div>

            <!-- Modal Panel -->
            <div x-show="showCategoryModal" 
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
                <div class="flex items-start justify-between mb-6">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-lime-100 dark:bg-lime-400/10 border border-lime-300 dark:border-lime-400/20 flex items-center justify-center text-lime-600 dark:text-lime-400">
                            <svg class="w-5 h-5 stroke-[2]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                                Manage Categories
                            </h3>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                Add new product classifications or remove unused categories.
                            </p>
                        </div>
                    </div>

                    <button @click="showCategoryModal = false" 
                            type="button"
                            class="p-2 rounded-xl text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Add Category Form (Top Section) -->
                <div class="p-4 rounded-2xl bg-zinc-100/70 dark:bg-zinc-900/40 border border-zinc-200/70 dark:border-white/5 mb-6">
                    <form @submit.prevent="submitAddCategory()" novalidate class="space-y-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300">
                            Category Name <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="text" 
                                   x-model="categoryForm.name"
                                   @input="categoryErrors.name = ''"
                                   placeholder="e.g. Cheese, Butter, Ice Cream"
                                   required
                                   maxlength="50"
                                   :class="categoryErrors.name ? 'border-red-500 dark:border-red-500 focus:border-red-500 focus:ring-red-400/20' : 'border-zinc-300 dark:border-zinc-800 focus:border-lime-500 focus:ring-lime-400/20'"
                                   class="flex-1 min-w-0 px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 text-sm rounded-xl focus:ring-4 focus:outline-none transition-all shadow-sm" />
                            
                            <button type="submit" 
                                    :disabled="isAddingCategory"
                                    :class="isAddingCategory ? 'opacity-70 cursor-not-allowed' : 'hover:scale-[1.02] active:scale-[0.98]'"
                                    class="px-4 py-2.5 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs shadow-md transition-all shrink-0 inline-flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                                <span x-text="isAddingCategory ? 'Adding...' : 'Add Category'">Add Category</span>
                            </button>
                        </div>
                        <p x-show="categoryErrors.name" x-text="categoryErrors.name" class="text-red-500 dark:text-red-400 text-xs font-medium mt-1.5"></p>
                    </form>
                </div>

                <!-- Existing Categories List (Bottom Section) -->
                <div>
                    <div class="flex items-center justify-between mb-3 px-1">
                        <span class="text-xs font-bold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Existing Categories
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-zinc-100 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300" 
                              x-text="categoriesList.length + ' Registered'"></span>
                    </div>

                    <div class="max-h-64 overflow-y-auto space-y-2 pr-1">
                        <template x-for="cat in categoriesList" :key="cat.id">
                            <div class="p-3.5 rounded-2xl bg-zinc-50/80 dark:bg-zinc-900/50 border border-zinc-200/70 dark:border-white/5 flex items-center justify-between gap-3 hover:border-zinc-300 dark:hover:border-white/10 transition-colors">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <span class="w-2 h-2 rounded-full bg-lime-500 dark:bg-lime-400 shrink-0"></span>
                                    <span class="text-xs sm:text-sm font-bold text-zinc-900 dark:text-white truncate" x-text="cat.name"></span>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="px-2.5 py-1 rounded-full text-[11px] font-semibold bg-zinc-200/70 dark:bg-zinc-800 text-zinc-700 dark:text-zinc-300 border border-zinc-300/50 dark:border-white/5"
                                          x-text="(cat.products_count ?? 0) + ' ' + ((cat.products_count ?? 0) === 1 ? 'Product' : 'Products')">
                                    </span>

                                    <button type="button" 
                                            @click="deleteCategory(cat.id, cat.name)"
                                            :disabled="isDeletingCategory === cat.id"
                                            title="Delete Category"
                                            class="p-2 rounded-xl text-zinc-400 hover:text-red-600 dark:hover:text-red-400 hover:bg-red-50 dark:hover:bg-red-400/10 transition-colors">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div x-show="categoriesList.length === 0" class="py-8 px-4 text-center rounded-2xl bg-zinc-50/50 dark:bg-zinc-900/20 border border-dashed border-zinc-200 dark:border-zinc-800">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">No categories registered yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Actions -->
                <div class="mt-6 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-end">
                    <button type="button" 
                            @click="showCategoryModal = false"
                            class="px-5 py-2.5 rounded-xl border border-zinc-300 dark:border-zinc-800 text-xs font-semibold text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-900 transition-colors">
                        Close
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
