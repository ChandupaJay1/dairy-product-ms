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
        
        <!-- Ambient Glowing Washes -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-32 -right-32 w-96 h-96 rounded-full bg-gradient-to-bl from-amber-400/12 dark:from-amber-500/10 to-transparent blur-[120px] animate-float-reverse"></div>
            <div class="absolute -bottom-32 -left-32 w-96 h-96 rounded-full bg-gradient-to-tr from-lime-400/12 dark:from-lime-400/10 to-transparent blur-[120px] animate-float-slow"></div>
        </div>

        <!-- Top Right Theme Toggle in Guest Mode -->
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

        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-8 sm:pt-0 p-4 relative z-10">
            <!-- MANDATORY SOLID BLACK (#000000) LOGO CONTAINER (Both Light & Dark) -->
            <a href="/" class="mb-6 block transition-transform duration-300 hover:scale-105">
                <div class="p-4 rounded-2xl border border-zinc-800 shadow-xl flex items-center gap-3"
                     style="background-color: #000000 !important;">
                    <div class="w-9 h-9 flex items-center justify-center shrink-0">
                        <svg class="w-9 h-9" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M32 4L56 18V46L32 60L8 46V18L32 4Z" stroke="#EAB308" stroke-width="3.5" stroke-linejoin="round" />
                            <path d="M14 32H23L27 23L31 42L35 18L39 37L43 32H50" stroke="#A3E635" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" />
                            <circle cx="35" cy="18" r="2.5" fill="#A3E635" />
                        </svg>
                    </div>
                    <div class="flex items-center text-xl font-extrabold tracking-tight">
                        <span class="text-white">Pulse</span>
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-400 to-lime-400">ERP</span>
                    </div>
                </div>
            </a>

            <!-- Frosted Glassmorphism Content Card with Dual Theme -->
            <div class="w-full sm:max-w-md p-6 sm:p-8 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl shadow-sm dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300">
                <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
