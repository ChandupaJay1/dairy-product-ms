<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('theme') === 'dark',
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
              if (this.darkMode) {
                  document.documentElement.classList.add('dark');
              } else {
                  document.documentElement.classList.remove('dark');
              }
          }
      }"
      x-init="
          if (darkMode) {
              document.documentElement.classList.add('dark');
          } else {
              document.documentElement.classList.remove('dark');
          }
      "
      :class="{ 'dark': darkMode }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>PulseERP &mdash; Enterprise Account Registration</title>

    <!-- Prevent Flash of Unstyled Theme -->
    <script>
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:300,400,500,600,700,800,900&display=swap" rel="stylesheet" />

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 dark:bg-zinc-950 text-zinc-800 dark:text-zinc-100 font-sans antialiased min-h-screen selection:bg-lime-400 selection:text-black overflow-x-hidden transition-colors duration-300">
    
    <!-- Top Right Floating Theme Toggle Button -->
    <div class="fixed top-5 right-5 z-50">
        <button @click="toggleTheme()" 
                type="button"
                title="Toggle Light / Dark Mode"
                class="p-2.5 rounded-full bg-white/80 dark:bg-zinc-900/70 border border-zinc-200/80 dark:border-white/10 hover:border-lime-500/50 dark:hover:border-lime-400/50 shadow-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-lime-400/40 group overflow-hidden">
            <div class="relative w-4 h-4 flex items-center justify-center">
                <!-- Sun Icon (Active in Dark Mode to switch to Light) -->
                <svg x-show="darkMode" 
                     x-cloak 
                     class="w-4 h-4 text-amber-400 group-hover:rotate-45 transition-transform duration-300" 
                     fill="none" 
                     viewBox="0 0 24 24" 
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>

                <!-- Moon Icon (Active in Light Mode to switch to Dark) -->
                <svg x-show="!darkMode" 
                     class="w-4 h-4 text-zinc-600 group-hover:text-amber-600 transition-colors duration-300" 
                     fill="none" 
                     viewBox="0 0 24 24" 
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                </svg>
            </div>
        </button>
    </div>

    <div class="min-h-screen w-full flex flex-col lg:flex-row relative">

        <!-- ========================================================= -->
        <!-- LEFT SIDE: BRANDING PANEL (Hidden on Mobile)              -->
        <!-- ========================================================= -->
        <aside class="hidden lg:flex lg:w-1/2 xl:w-7/12 relative flex-col justify-between p-12 xl:p-16 overflow-hidden bg-[#f1f5f9] dark:bg-zinc-950/90 border-r border-zinc-200/80 dark:border-white/5 select-none transition-colors duration-300">
            
            <!-- Ambient Floating & Pulsing Glow Auroras (Soft Watercolor Washes) -->
            <div class="absolute -top-36 -left-36 w-[480px] h-[480px] rounded-full bg-gradient-to-br from-amber-400/15 dark:from-amber-500/12 via-yellow-300/10 dark:via-yellow-400/6 to-transparent blur-[120px] pointer-events-none animate-float-slow"></div>
            <div class="absolute -bottom-36 -right-36 w-[500px] h-[500px] rounded-full bg-gradient-to-tl from-lime-400/15 dark:from-lime-400/12 via-lime-300/10 dark:via-lime-500/6 to-transparent blur-[130px] pointer-events-none animate-float-reverse"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[550px] h-[550px] rounded-full bg-gradient-to-tr from-amber-400/8 dark:from-amber-400/5 via-lime-400/8 dark:via-lime-400/5 to-transparent blur-[140px] pointer-events-none animate-pulse-glow"></div>
            
            <!-- Subtle Radial Dot Matrix Overlay -->
            <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#27272a_1px,transparent_1px)] [background-size:28px_28px] opacity-40 dark:opacity-20 pointer-events-none"></div>

            <!-- Top Header Status (Graceful Slide-up Entrance) -->
            <div class="relative z-10 flex items-center justify-between animate-fade-slide-up">
                <div class="flex items-center gap-2.5 px-3.5 py-1.5 rounded-full bg-white/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 backdrop-blur-md shadow-sm transition-colors duration-300">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-500 dark:bg-lime-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-lime-500 dark:bg-lime-400"></span>
                    </span>
                    <span class="text-xs font-mono font-bold tracking-wider uppercase text-zinc-700 dark:text-zinc-300">
                        Pulse Systems &bull; Access Portal
                    </span>
                </div>

                <div class="flex items-center gap-2 text-xs font-mono text-zinc-600 dark:text-zinc-400">
                    <span class="px-2.5 py-1 rounded-md bg-white/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 shadow-sm font-semibold">TLS 1.3</span>
                    <span class="px-2.5 py-1 rounded-md bg-white/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-lime-700 dark:text-lime-400 font-bold shadow-sm">SOC-2 Type II</span>
                </div>
            </div>

            <!-- Centered Branding & Value Proposition -->
            <div class="relative z-10 my-auto flex flex-col items-center text-center py-10">
                
                <!-- MANDATORY REQUIREMENT: Logo & Text container MUST retain its STRICTLY SOLID BLACK background (#000000) -->
                <div class="animate-fade-slide-up animation-delay-100 p-8 sm:p-10 rounded-[2rem] border border-zinc-800 shadow-[0_15px_45px_rgba(0,0,0,0.3)] flex flex-col items-center max-w-sm w-full mx-auto hover:scale-[1.01] transition-all duration-500 group"
                     style="background-color: #000000 !important;">
                    
                    <!-- PulseERP Glowing Vector Emblem with Subtle Pulse Node -->
                    <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
                        <svg class="w-20 h-20 group-hover:scale-105 transition-transform duration-500" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <defs>
                                <linearGradient id="regBrandGoldLimeGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#F59E0B" />
                                    <stop offset="50%" stop-color="#EAB308" />
                                    <stop offset="100%" stop-color="#A3E635" />
                                </linearGradient>
                                <linearGradient id="regBrandInnerShield" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#F59E0B" stop-opacity="0.12" />
                                    <stop offset="100%" stop-color="#A3E635" stop-opacity="0.16" />
                                </linearGradient>
                            </defs>
                            <!-- Enterprise Hexagonal Shield Frame -->
                            <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" 
                                  stroke="url(#regBrandGoldLimeGrad)" 
                                  stroke-width="3" 
                                  stroke-linejoin="round" />
                            <!-- Inner Ambient Shading -->
                            <path d="M32 11L50 21.5V42.5L32 53L14 42.5V21.5L32 11Z" 
                                  fill="url(#regBrandInnerShield)" 
                                  stroke="#27272a" 
                                  stroke-width="1.5" />
                            <!-- Dynamic Pulse Waveform -->
                            <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" 
                                  stroke="url(#regBrandGoldLimeGrad)" 
                                  stroke-width="3" 
                                  stroke-linecap="round" 
                                  stroke-linejoin="round" />
                            <!-- Live Peak Pulse Node -->
                            <circle cx="35" cy="18" r="3.5" fill="#A3E635" class="animate-ping opacity-60" />
                            <circle cx="35" cy="18" r="2.5" fill="#A3E635" />
                        </svg>
                    </div>

                    <!-- PulseERP Wordmark -->
                    <div class="flex items-center text-4xl sm:text-5xl font-extrabold tracking-tight">
                        <span class="text-white">Pulse</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400">ERP</span>
                    </div>

                    <!-- Monospace Tagline inside Solid Black Box -->
                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.22em] text-zinc-400">
                        Enterprise Intelligence
                    </p>
                </div>

                <!-- Descriptive Enterprise Narrative -->
                <div class="animate-fade-slide-up animation-delay-200 mt-9 max-w-md text-center space-y-4">
                    <p class="text-base text-zinc-700 dark:text-zinc-300 font-medium leading-relaxed transition-colors duration-300">
                        Request authorization and initialize your credentials on PulseERP's unified intelligence infrastructure.
                    </p>

                    <!-- Feature Highlights Pills (Dual Theme Support) -->
                    <div class="animate-fade-slide-up animation-delay-300 grid grid-cols-1 gap-2.5 pt-2 text-left">
                        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white/80 dark:bg-zinc-900/50 border border-zinc-200/80 dark:border-white/5 backdrop-blur-sm shadow-sm hover:bg-white dark:hover:bg-zinc-900/80 hover:border-zinc-300 dark:hover:border-white/10 hover:translate-x-1.5 transition-all duration-300 group cursor-default">
                            <div class="w-8 h-8 rounded-xl bg-lime-50 dark:bg-lime-400/10 border border-lime-200/80 dark:border-lime-400/20 flex items-center justify-center shrink-0 group-hover:scale-110 shadow-sm text-lime-600 dark:text-lime-400 transition-all duration-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-black dark:group-hover:text-white transition-colors">Instant Verification</h4>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">Automatic domain validation for authorized enterprise organizations.</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3.5 p-3.5 rounded-2xl bg-white/80 dark:bg-zinc-900/50 border border-zinc-200/80 dark:border-white/5 backdrop-blur-sm shadow-sm hover:bg-white dark:hover:bg-zinc-900/80 hover:border-zinc-300 dark:hover:border-white/10 hover:translate-x-1.5 transition-all duration-300 group cursor-default">
                            <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-400/10 border border-amber-200/80 dark:border-amber-400/20 flex items-center justify-center shrink-0 group-hover:scale-110 shadow-sm text-amber-600 dark:text-amber-400 transition-all duration-300">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-xs font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-black dark:group-hover:text-white transition-colors">Role-Based Access</h4>
                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400 font-medium">Pre-configured workspace permissions assigned directly by admin.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom Footer Meta -->
            <div class="relative z-10 pt-6 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400 font-mono animate-fade-slide-up animation-delay-400 transition-colors duration-300">
                <span>&copy; {{ date('Y') }} PulseERP Solutions Inc.</span>
                <span class="flex items-center gap-1.5 font-medium">
                    <span class="w-1.5 h-1.5 rounded-full bg-lime-500 dark:bg-lime-400 animate-pulse"></span>
                    <span>Zero-Trust Registration Gateway</span>
                </span>
            </div>
        </aside>

        <!-- ========================================================= -->
        <!-- RIGHT SIDE: REGISTRATION FORM (Dual Light/Dark Glass)     -->
        <!-- ========================================================= -->
        <main class="w-full lg:w-1/2 xl:w-5/12 flex items-center justify-center p-6 sm:p-10 lg:p-12 xl:p-16 relative overflow-hidden bg-slate-50 dark:bg-zinc-950 transition-colors duration-300">
            
            <!-- Soft Ambient Glow Washes for Glassmorphism Refraction -->
            <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-gradient-to-bl from-amber-400/15 dark:from-amber-500/10 via-yellow-300/10 dark:via-yellow-400/5 to-transparent blur-[120px] pointer-events-none animate-float-reverse"></div>
            <div class="absolute -bottom-40 -left-20 w-96 h-96 rounded-full bg-gradient-to-tr from-lime-400/15 dark:from-lime-400/10 via-lime-300/10 dark:via-lime-500/5 to-transparent blur-[120px] pointer-events-none animate-float-slow"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-80 h-80 rounded-full bg-gradient-to-br from-amber-400/8 dark:from-amber-400/4 via-lime-400/8 dark:via-lime-400/4 to-transparent blur-[100px] pointer-events-none animate-pulse-glow"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#27272a_1px,transparent_1px)] [background-size:24px_24px] opacity-30 dark:opacity-15 pointer-events-none"></div>

            <div class="w-full max-w-md relative z-10">
                
                <!-- MOBILE-ONLY BRANDING (Centered Solid Black Container with Fade Entrance) -->
                <div class="lg:hidden mb-8 flex justify-center animate-fade-slide-up">
                    <div class="p-4 sm:p-5 rounded-2xl border border-zinc-800 shadow-xl flex items-center gap-3.5"
                         style="background-color: #000000 !important;">
                        <div class="w-10 h-10 flex items-center justify-center">
                            <svg class="w-10 h-10" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" stroke="#EAB308" stroke-width="3" stroke-linejoin="round" />
                                <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" stroke="#A3E635" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <circle cx="35" cy="18" r="3" fill="#A3E635" />
                            </svg>
                        </div>
                        <div class="flex items-center text-2xl font-extrabold tracking-tight">
                            <span class="text-white">Pulse</span>
                            <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-lime-400">ERP</span>
                        </div>
                    </div>
                </div>

                <!-- POLISHED DUAL LIGHT/DARK GLASSMORPHISM REGISTRATION CARD -->
                <div class="animate-fade-slide-up animation-delay-200 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-7 sm:p-9 shadow-[0_20px_50px_-10px_rgba(0,0,0,0.06),0_0_20px_rgba(0,0,0,0.02)] dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-500 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50">
                    
                    <!-- Card Top Soft Accent Sheen -->
                    <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>

                    <!-- Header -->
                    <div class="mb-6">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-400/10 border border-amber-200/80 dark:border-amber-400/20 text-[11px] font-bold text-amber-800 dark:text-amber-300 mb-3 shadow-sm transition-colors duration-300">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 dark:bg-amber-400 animate-pulse"></span>
                            New Enterprise Account
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-zinc-900 dark:text-white transition-colors duration-300">
                            Create your account
                        </h1>
                        <p class="mt-1.5 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 transition-colors duration-300">
                            Join the PulseERP workspace to get started.
                        </p>
                    </div>

                    <!-- Registration Form -->
                    <form method="POST" action="{{ route('register') }}" class="space-y-4" x-data="{ showPassword: false, showConfirmPassword: false }">
                        @csrf

                        <!-- Name Input -->
                        <div>
                            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                                Full Name
                            </label>
                            <div class="relative group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 group-focus-within:text-lime-600 dark:group-focus-within:text-lime-400 transition-colors duration-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <input id="name" 
                                       type="text" 
                                       name="name" 
                                       value="{{ old('name') }}" 
                                       required 
                                       autofocus 
                                       autocomplete="name" 
                                       placeholder="Alex Morgan"
                                       class="w-full pl-10 pr-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
                            </div>
                            @error('name')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                                Work Email Address
                            </label>
                            <div class="relative group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 group-focus-within:text-lime-600 dark:group-focus-within:text-lime-400 transition-colors duration-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input id="email" 
                                       type="email" 
                                       name="email" 
                                       value="{{ old('email') }}" 
                                       required 
                                       autocomplete="username" 
                                       placeholder="alex@company.com"
                                       class="w-full pl-10 pr-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
                            </div>
                            @error('email')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <!-- Password Input -->
                        <div>
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                                Password
                            </label>
                            <div class="relative group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 group-focus-within:text-lime-600 dark:group-focus-within:text-lime-400 transition-colors duration-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input id="password" 
                                       :type="showPassword ? 'text' : 'password'"
                                       name="password" 
                                       required 
                                       autocomplete="new-password" 
                                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                       class="w-full pl-10 pr-11 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
                                
                                <button type="button" 
                                        @click="showPassword = !showPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors duration-200 focus:outline-none"
                                        aria-label="Toggle password visibility">
                                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <!-- Confirm Password Input -->
                        <div>
                            <label for="password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                                Confirm Password
                            </label>
                            <div class="relative group">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-zinc-400 group-focus-within:text-lime-600 dark:group-focus-within:text-lime-400 transition-colors duration-300">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                </div>
                                <input id="password_confirmation" 
                                       :type="showConfirmPassword ? 'text' : 'password'"
                                       name="password_confirmation" 
                                       required 
                                       autocomplete="new-password" 
                                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                                       class="w-full pl-10 pr-11 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
                                
                                <button type="button" 
                                        @click="showConfirmPassword = !showConfirmPassword"
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors duration-200 focus:outline-none"
                                        aria-label="Toggle password confirmation visibility">
                                    <svg x-show="!showConfirmPassword" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <svg x-show="showConfirmPassword" x-cloak class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            @error('password_confirmation')
                                <p class="mt-1.5 text-xs text-rose-600 dark:text-rose-400 font-semibold flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                    <span>{{ $message }}</span>
                                </p>
                            @enderror
                        </div>

                        <!-- Registration Button: Gold to Lime Gradient, Smooth Scale Hover (hover:scale-[1.02] duration-300) & Deeper Shadow -->
                        <div class="pt-2">
                            <button type="submit" 
                                    class="w-full relative group overflow-hidden rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 p-[1px] font-bold text-zinc-950 shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_20px_45px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30">
                                <div class="w-full py-3 px-6 rounded-xl flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-sm tracking-wide transition-all duration-300">
                                    <span>Create Enterprise Account</span>
                                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </div>

                        <!-- Already Registered link -->
                        <div class="pt-3 text-center">
                            <p class="text-xs text-zinc-600 dark:text-zinc-400 font-medium">
                                Already registered?
                                <a href="{{ route('login') }}" class="font-bold text-amber-700 dark:text-amber-400 hover:text-lime-700 dark:hover:text-lime-300 transition-colors duration-200 ml-1">
                                    Sign in
                                </a>
                            </p>
                        </div>
                    </form>

                    <!-- Security Footnote -->
                    <div class="mt-5 pt-4 border-t border-zinc-200/80 dark:border-white/5 flex items-center justify-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400 font-medium transition-colors duration-300">
                        <svg class="w-3.5 h-3.5 text-lime-600 dark:text-lime-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        <span>Protected by 256-bit SSL &bull; Zero-Trust Architecture</span>
                    </div>

                </div>

            </div>
        </main>

    </div>
</body>
</html>
