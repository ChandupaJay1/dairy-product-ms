<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-zinc-800 dark:text-white tracking-tight transition-colors duration-300">
                {{ __('Dashboard Overview') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ========================================================= -->
        <!-- HERO WELCOME CARD (Dual Light/Dark Glassmorphism)         -->
        <!-- ========================================================= -->
        <div class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-6 sm:p-9 shadow-[0_15px_35px_-5px_rgba(0,0,0,0.04)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:scale-[1.005] hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50 group">
            
            <!-- Top Subtle Sheen Line (Gold/Lime) -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

            <!-- Soft Internal Corner Glow -->
            <div class="absolute -right-20 -bottom-20 w-64 h-64 rounded-full bg-gradient-to-tl from-lime-400/10 via-amber-400/5 to-transparent blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-lime-50 dark:bg-zinc-900/60 border border-lime-200/80 dark:border-white/5 text-[11px] font-semibold text-lime-800 dark:text-lime-400 mb-3.5 shadow-sm transition-colors duration-300">
                        <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400 animate-pulse"></span>
                        Active Session &bull; All Systems Nominal
                    </div>

                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                        Welcome back, {{ Auth::user()->name }}!
                    </h1>
                    <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
                        Your PulseERP enterprise environment is synchronized and running smoothly. All security layers, data pipelines, and real-time operations are fully active.
                    </p>
                </div>

                <!-- Simple Quick Action for Non-Technical Users -->
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('profile.edit') }}" 
                       class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 text-xs font-bold shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98]">
                        <span>Manage My Profile</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>

        <!-- ========================================================= -->
        <!-- KEY METRIC CARDS (Dual Light/Dark Glassmorphism)          -->
        <!-- ========================================================= -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
            
            <!-- Metric Card 1: System Health -->
            <div class="animate-fade-slide-up animation-delay-100 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-5 shadow-sm dark:shadow-lg relative overflow-hidden transition-all duration-300 hover:border-zinc-300/90 dark:hover:border-white/10 hover:scale-[1.01] hover:bg-white/90 dark:hover:bg-zinc-900/40">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">System Health</span>
                    <div class="w-8 h-8 rounded-lg bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 flex items-center justify-center text-lime-600 dark:text-lime-400 shadow-sm transition-colors duration-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight transition-colors duration-300">
                    100% Operational
                </div>
                <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 font-medium transition-colors duration-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                    Continuous real-time telemetry active
                </p>
            </div>

            <!-- Metric Card 2: Security Clearance -->
            <div class="animate-fade-slide-up animation-delay-200 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-5 shadow-sm dark:shadow-lg relative overflow-hidden transition-all duration-300 hover:border-zinc-300/90 dark:hover:border-white/10 hover:scale-[1.01] hover:bg-white/90 dark:hover:bg-zinc-900/40">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Security Protocol</span>
                    <div class="w-8 h-8 rounded-lg bg-amber-50 dark:bg-amber-400/10 border border-amber-200/80 dark:border-amber-400/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shadow-sm transition-colors duration-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight transition-colors duration-300">
                    Zero-Trust Verified
                </div>
                <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 font-medium transition-colors duration-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400"></span>
                    256-bit AES authenticated session
                </p>
            </div>

            <!-- Metric Card 3: Enterprise Sync -->
            <div class="animate-fade-slide-up animation-delay-300 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-2xl p-5 shadow-sm dark:shadow-lg relative overflow-hidden transition-all duration-300 hover:border-zinc-300/90 dark:hover:border-white/10 hover:scale-[1.01] hover:bg-white/90 dark:hover:bg-zinc-900/40 sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">Cloud Synchronization</span>
                    <div class="w-8 h-8 rounded-lg bg-yellow-50 dark:bg-yellow-400/10 border border-yellow-200/80 dark:border-yellow-400/20 flex items-center justify-center text-yellow-600 dark:text-yellow-400 shadow-sm transition-colors duration-300">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                    </div>
                </div>
                <div class="text-2xl font-black text-zinc-900 dark:text-white tracking-tight transition-colors duration-300">
                    Sub-second Latency
                </div>
                <p class="mt-1.5 text-[11px] text-zinc-500 dark:text-zinc-400 flex items-center gap-1.5 font-medium transition-colors duration-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400"></span>
                    Data clusters fully synchronized
                </p>
            </div>

        </div>

        <!-- ========================================================= -->
        <!-- QUICK NAVIGATION SHORTCUTS (Dual Light/Dark Glass)        -->
        <!-- ========================================================= -->
        <div class="animate-fade-slide-up animation-delay-400 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-lg transition-colors duration-300">
            <h3 class="text-sm font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-4 flex items-center gap-2">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                Quick Access Hub
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Profile & Security Card -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-zinc-900/40 border border-zinc-200/70 dark:border-white/5 hover:border-lime-500/50 dark:hover:border-lime-400/30 hover:bg-white dark:hover:bg-zinc-900/80 hover:translate-x-1 transition-all duration-300 group shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 flex items-center justify-center text-lime-600 dark:text-lime-400 shrink-0 group-hover:scale-110 transition-transform shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-lime-700 dark:group-hover:text-lime-300 transition-colors">
                            Account Profile & Security Settings
                        </h4>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 leading-relaxed transition-colors">
                            Update your personal information, manage your password, and review active sessions.
                        </p>
                    </div>
                </a>

                <!-- Support & Help Card -->
                <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-zinc-900/40 border border-zinc-200/70 dark:border-white/5 hover:border-amber-500/50 dark:hover:border-amber-400/30 hover:bg-white dark:hover:bg-zinc-900/80 transition-all duration-300 group shadow-sm">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-400/10 border border-amber-200/80 dark:border-amber-400/20 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0 group-hover:scale-110 transition-transform shadow-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-amber-700 dark:group-hover:text-amber-300 transition-colors">
                            PulseERP System Assistance
                        </h4>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 leading-relaxed transition-colors">
                            Need guidance? Connect with your organization's designated PulseERP IT administrator.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</x-app-layout>
