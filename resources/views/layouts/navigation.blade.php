<!-- ========================================================= -->
<!-- DESKTOP SIDEBAR NAVIGATION (Dual Theme Support)           -->
<!-- ========================================================= -->
<aside class="hidden md:flex md:w-72 md:flex-col md:fixed md:inset-y-0 z-40 select-none overflow-hidden transition-colors duration-300">
    <div class="flex flex-col justify-between h-full backdrop-blur-3xl bg-white/75 dark:bg-zinc-950/50 border-r border-zinc-200/70 dark:border-white/5 p-5 relative overflow-y-auto overflow-x-hidden shadow-[4px_0_24px_rgba(0,0,0,0.02)] transition-colors duration-300">
        
        <!-- Ambient Subtle Background Glow inside Sidebar (Contained within overflow-hidden) -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 -left-20 w-48 h-48 rounded-full bg-gradient-to-br from-amber-400/10 dark:from-amber-500/10 to-transparent blur-3xl"></div>
            <div class="absolute bottom-10 -right-20 w-48 h-48 rounded-full bg-gradient-to-tl from-lime-400/10 dark:from-lime-400/10 to-transparent blur-3xl"></div>
        </div>

        <div class="relative z-10 min-w-0">
            <!-- Sidebar Header: MANDATORY STRICTLY SOLID BLACK (#000000) LOGO CONTAINER (Both Light & Dark) -->
            <div class="p-3.5 rounded-2xl border border-zinc-800 shadow-[0_4px_20px_rgba(0,0,0,0.25)] flex items-center gap-3 w-full transition-all duration-300 hover:scale-[1.01]"
                 style="background-color: #000000 !important;">
                <div class="w-9 h-9 flex items-center justify-center shrink-0">
                    <svg class="w-9 h-9" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <linearGradient id="navBrandDualGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" stop-color="#F59E0B" />
                                <stop offset="50%" stop-color="#EAB308" />
                                <stop offset="100%" stop-color="#A3E635" />
                            </linearGradient>
                        </defs>
                        <!-- Hexagonal Shield Outline -->
                        <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" stroke="url(#navBrandDualGrad)" stroke-width="3.5" stroke-linejoin="round" />
                        <!-- Dynamic Pulse Wave -->
                        <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" stroke="url(#navBrandDualGrad)" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                        <!-- Active Node -->
                        <circle cx="35" cy="18" r="2.5" fill="#A3E635" />
                    </svg>
                </div>
                <div class="flex flex-col min-w-0">
                    <div class="flex items-center text-xl font-extrabold tracking-tight truncate">
                        <span class="text-white">Pulse</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400">ERP</span>
                    </div>
                    <span class="text-[9px] font-mono tracking-widest text-zinc-400 uppercase truncate">Enterprise Core</span>
                </div>
            </div>

            <!-- Navigation Section -->
            <div class="mt-8">
                <p class="px-3 text-[10px] font-mono uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold mb-3">
                    Main Navigation
                </p>

                <nav class="space-y-1.5">
                    <!-- Dashboard Link -->
                    <a href="{{ route('dashboard') }}" 
                       class="group flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm dark:shadow-[inset_0_0_20px_rgba(163,230,53,0.06)]' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100/70 dark:hover:bg-white/[0.03] hover:translate-x-1' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                        </svg>
                        <span class="truncate">{{ __('Dashboard') }}</span>
                    </a>

                    <!-- Products Directory Link -->
                    <a href="{{ route('products.index') }}" 
                       class="group flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 {{ request()->routeIs('products.*') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm dark:shadow-[inset_0_0_20px_rgba(163,230,53,0.06)]' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100/70 dark:hover:bg-white/[0.03] hover:translate-x-1' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('products.*') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span class="truncate">{{ __('Products Directory') }}</span>
                    </a>

                    <!-- Stocks Management Link -->
                    <a href="{{ route('stocks.index') }}" 
                       class="group flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 {{ request()->routeIs('stocks.*') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm dark:shadow-[inset_0_0_20px_rgba(163,230,53,0.06)]' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100/70 dark:hover:bg-white/[0.03] hover:translate-x-1' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('stocks.*') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        <span class="truncate">{{ __('Stocks Management') }}</span>
                    </a>

                    <!-- Profile Link -->
                    <a href="{{ route('profile.edit') }}" 
                       class="group flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-xs font-medium transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm dark:shadow-[inset_0_0_20px_rgba(163,230,53,0.06)]' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-100/70 dark:hover:bg-white/[0.03] hover:translate-x-1' }}">
                        <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500 group-hover:text-amber-500 dark:group-hover:text-amber-400 transition-colors' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span class="truncate">{{ __('My Profile') }}</span>
                    </a>
                </nav>

                <!-- System Telemetry Section (Non-Technical Friendly) -->
                <p class="px-3 text-[10px] font-mono uppercase tracking-widest text-zinc-400 dark:text-zinc-500 font-bold mt-7 mb-3">
                    System Telemetry
                </p>

                <div class="px-3.5 py-3 rounded-2xl bg-zinc-100/80 dark:bg-zinc-950/40 border border-zinc-200/70 dark:border-white/5 space-y-2.5 shadow-sm transition-colors duration-300">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400">Node Status</span>
                        <span class="flex items-center gap-1.5 text-lime-700 dark:text-lime-400 font-mono text-[11px] font-semibold">
                            <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400 animate-pulse"></span>
                            Online
                        </span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400">Security</span>
                        <span class="text-zinc-800 dark:text-zinc-300 font-mono text-[11px] font-semibold">TLS 1.3</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400">Environment</span>
                        <span class="text-amber-700 dark:text-amber-300 font-mono text-[11px] font-semibold">PulseCloud</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Footer: User Card & Logout -->
        <div class="pt-4 border-t border-zinc-200/70 dark:border-white/5 relative z-10 min-w-0 transition-colors duration-300">
            <div class="p-3 rounded-2xl bg-zinc-100/90 dark:bg-zinc-950/60 border border-zinc-200/80 dark:border-white/5 flex items-center justify-between gap-3 shadow-sm transition-colors duration-300">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-amber-400 to-lime-400 p-[1px] shrink-0 shadow-sm">
                        <div class="w-full h-full rounded-xl bg-black flex items-center justify-center text-xs font-bold text-white uppercase">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ Auth::user()->name }}</p>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ Auth::user()->email }}</p>
                    </div>
                </div>

                <!-- Logout Button -->
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" 
                            title="Log Out"
                            class="w-7 h-7 rounded-lg bg-white dark:bg-zinc-900/80 hover:bg-rose-50 dark:hover:bg-rose-500/10 border border-zinc-200 dark:border-zinc-800 hover:border-rose-200 dark:hover:border-rose-500/30 text-zinc-500 dark:text-zinc-400 hover:text-rose-600 dark:hover:text-rose-400 flex items-center justify-center transition-colors duration-200 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>

    </div>
</aside>

<!-- ========================================================= -->
<!-- MOBILE SIDEBAR DRAWER (Sliding Transition via Alpine.js)  -->
<!-- ========================================================= -->
<!-- Backdrop Overlay -->
<div x-show="sidebarOpen" 
     x-cloak
     x-transition:enter="transition-opacity ease-linear duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition-opacity ease-linear duration-300"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     class="fixed inset-0 bg-zinc-900/40 dark:bg-black/80 backdrop-blur-sm z-50 md:hidden"
     @click="sidebarOpen = false"></div>

<!-- Slide-in Drawer (Dual Theme Support) -->
<div x-show="sidebarOpen" 
     x-cloak
     x-transition:enter="transition ease-out duration-300 transform"
     x-transition:enter-start="-translate-x-full"
     x-transition:enter-end="translate-x-0"
     x-transition:leave="transition ease-in duration-300 transform"
     x-transition:leave-start="translate-x-0"
     x-transition:leave-end="-translate-x-full"
     class="fixed inset-y-0 left-0 w-72 max-w-[85vw] backdrop-blur-3xl bg-slate-50/95 dark:bg-zinc-950/95 border-r border-zinc-200/80 dark:border-white/10 z-50 p-5 flex flex-col justify-between md:hidden select-none overflow-y-auto overflow-x-hidden shadow-2xl transition-colors duration-300">
    
    <div>
        <!-- Mobile Sidebar Header with Close Button -->
        <div class="flex items-center justify-between mb-6">
            <!-- MANDATORY STRICTLY SOLID BLACK (#000000) LOGO CONTAINER ON MOBILE -->
            <div class="p-3 rounded-2xl border border-zinc-800 shadow-xl flex items-center gap-2.5"
                 style="background-color: #000000 !important;">
                <div class="w-8 h-8 flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" stroke="#EAB308" stroke-width="3.5" stroke-linejoin="round" />
                        <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" stroke="#A3E635" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                        <circle cx="35" cy="18" r="2.5" fill="#A3E635" />
                    </svg>
                </div>
                <div class="flex items-center text-lg font-extrabold tracking-tight">
                    <span class="text-white">Pulse</span>
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-lime-400">ERP</span>
                </div>
            </div>

            <!-- Close Drawer Button -->
            <button @click="sidebarOpen = false" 
                    class="p-2 rounded-xl text-zinc-500 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 shadow-sm focus:outline-none">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Mobile Navigation Links -->
        <nav class="space-y-1.5 mt-4">
            <a href="{{ route('dashboard') }}" 
               @click="sidebarOpen = false"
               class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('dashboard') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-white/[0.03]' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('dashboard') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>{{ __('Dashboard') }}</span>
            </a>

            <a href="{{ route('products.index') }}" 
               @click="sidebarOpen = false"
               class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('products.*') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-white/[0.03]' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('products.*') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
                <span>{{ __('Products Directory') }}</span>
            </a>

            <a href="{{ route('stocks.index') }}" 
               @click="sidebarOpen = false"
               class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('stocks.*') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-white/[0.03]' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('stocks.*') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <span>{{ __('Stocks Management') }}</span>
            </a>

            <a href="{{ route('profile.edit') }}" 
               @click="sidebarOpen = false"
               class="flex items-center gap-3.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200 {{ request()->routeIs('profile.edit') ? 'border-l-2 border-lime-600 dark:border-lime-400 bg-lime-500/10 dark:bg-lime-400/10 text-lime-800 dark:text-lime-300 font-semibold shadow-sm' : 'border-l-2 border-transparent text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-white/[0.03]' }}">
                <svg class="w-4 h-4 shrink-0 {{ request()->routeIs('profile.edit') ? 'text-lime-600 dark:text-lime-400' : 'text-zinc-400 dark:text-zinc-500' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                <span>{{ __('My Profile') }}</span>
            </a>
        </nav>
    </div>

    <!-- Mobile Footer Profile -->
    <div class="pt-4 border-t border-zinc-200/80 dark:border-white/10">
        <div class="p-3 rounded-2xl bg-white dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-white/5 flex items-center justify-between gap-3 shadow-sm">
            <div class="flex items-center gap-2.5 min-w-0">
                <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-amber-400 to-lime-400 p-[1px] shrink-0">
                    <div class="w-full h-full rounded-xl bg-black flex items-center justify-center text-xs font-bold text-white uppercase">
                        {{ substr(Auth::user()->name, 0, 1) }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-bold text-zinc-800 dark:text-zinc-200 truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-zinc-500 dark:text-zinc-400 truncate">{{ Auth::user()->email }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" 
                        class="px-2.5 py-1.5 rounded-lg bg-zinc-100 dark:bg-zinc-800 hover:bg-rose-50 dark:hover:bg-rose-500/10 text-xs font-medium text-zinc-600 dark:text-zinc-300 hover:text-rose-600 dark:hover:text-rose-400 border border-zinc-200 dark:border-zinc-700 transition-colors">
                    Log out
                </button>
            </form>
        </div>
    </div>

</div>
