<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="{
          darkMode: localStorage.getItem('theme') === 'dark',
          sidebarOpen: false,
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

    <title>{{ config('app.name', 'PulseERP') }} &mdash; Enterprise Portal</title>

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

    <!-- Responsive Sidebar Component (Desktop + Mobile Drawer) -->
    @include('layouts.navigation')

    <!-- ========================================================= -->
    <!-- MAIN CONTENT AREA (Offset by Sidebar on Desktop)          -->
    <!-- ========================================================= -->
    <div class="md:pl-72 flex flex-col min-h-screen min-w-0 relative bg-slate-50 dark:bg-zinc-950 overflow-x-hidden transition-colors duration-300">
        
        <!-- Ambient Subtle Background Glow Orbs -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -right-32 w-[480px] h-[480px] rounded-full bg-gradient-to-bl from-amber-400/12 dark:from-amber-500/10 via-yellow-300/8 dark:via-yellow-400/5 to-transparent blur-[120px] animate-float-reverse"></div>
            <div class="absolute -bottom-32 left-1/4 w-[500px] h-[500px] rounded-full bg-gradient-to-tr from-lime-400/12 dark:from-lime-400/10 via-lime-300/8 dark:via-lime-500/5 to-transparent blur-[120px] animate-float-slow"></div>
            <div class="absolute inset-0 bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] dark:bg-[radial-gradient(#27272a_1px,transparent_1px)] opacity-40 dark:opacity-15"></div>
        </div>

        <!-- Minimalist Frosted Glassmorphic Top Header -->
        <header class="sticky top-0 z-30 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border-b border-zinc-200/70 dark:border-white/5 px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between select-none shadow-[0_1px_10px_rgba(0,0,0,0.02)] transition-colors duration-300">
            <div class="flex items-center gap-3 min-w-0">
                <!-- Mobile Hamburger Button -->
                <button @click="sidebarOpen = true" 
                        class="md:hidden p-2 rounded-xl text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-white bg-white dark:bg-zinc-900/80 border border-zinc-200/80 dark:border-zinc-800/80 shadow-sm focus:outline-none focus:ring-2 focus:ring-lime-400/40"
                        aria-label="Open navigation menu">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>

                <!-- Page Header / Breadcrumb -->
                <div class="min-w-0">
                    @isset($header)
                        {{ $header }}
                    @else
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-zinc-400 dark:text-zinc-500 font-mono">PulseERP</span>
                            <span class="text-zinc-300 dark:text-zinc-600">/</span>
                            <h1 class="text-sm font-bold text-zinc-800 dark:text-zinc-200 truncate">Enterprise Portal</h1>
                        </div>
                    @endisset
                </div>
            </div>

            <!-- Top Right Operational Status, Theme Toggle & Quick Actions -->
            <div class="flex items-center gap-3 shrink-0">
                <!-- Live Telemetry Status Badge -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/80 dark:bg-zinc-900/60 border border-zinc-200/80 dark:border-white/5 text-xs font-mono text-zinc-600 dark:text-zinc-400 shadow-sm transition-colors duration-300">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-lime-500 dark:bg-lime-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-lime-500 dark:bg-lime-400"></span>
                    </span>
                    <span class="text-zinc-700 dark:text-zinc-300 font-medium">Live Telemetry</span>
                </div>

                <!-- Theme Toggle Button (Light / Dark) -->
                <button @click="toggleTheme()" 
                        type="button"
                        title="Toggle Light / Dark Mode"
                        class="relative p-2 rounded-full bg-white/80 dark:bg-zinc-900/70 border border-zinc-200/80 dark:border-white/10 hover:border-lime-500/50 dark:hover:border-lime-400/50 shadow-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-lime-400/40 group overflow-hidden">
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

                <!-- User Quick Chip -->
                <a href="{{ route('profile.edit') }}" 
                   class="flex items-center gap-2.5 pl-2.5 pr-3.5 py-1 rounded-full bg-white/80 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-800/60 border border-zinc-200/80 dark:border-white/5 hover:border-zinc-300 dark:hover:border-white/10 shadow-sm transition-all duration-200">
                    <div class="w-6 h-6 rounded-full bg-gradient-to-r from-amber-400 to-lime-400 p-[1px] shadow-sm">
                        <div class="w-full h-full rounded-full bg-black flex items-center justify-center text-[10px] font-bold text-white uppercase">
                            {{ substr(Auth::user()->name, 0, 1) }}
                        </div>
                    </div>
                    <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300 hidden sm:inline-block max-w-[120px] truncate">
                        {{ Auth::user()->name }}
                    </span>
                </a>
            </div>
        </header>

        <!-- Page Main Slot -->
        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 sm:py-8 relative z-10 min-w-0">
            {{ $slot }}
        </main>

        <!-- Minimalist Dashboard Footer -->
        <footer class="px-4 sm:px-6 lg:px-8 py-4 border-t border-zinc-200/70 dark:border-white/5 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-600 font-mono relative z-10 shrink-0 transition-colors duration-300">
            <span>&copy; {{ date('Y') }} PulseERP Intelligence Suite</span>
            <span class="hidden sm:inline">Protected by 256-bit AES &bull; Zero-Trust Protocol</span>
        </footer>

    </div>

</body>
</html>
