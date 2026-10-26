<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-2">
            <h2 class="text-sm font-bold text-zinc-800 dark:text-white tracking-tight transition-colors duration-300">
                {{ __('Account Profile & Security') }}
            </h2>
        </div>
    </x-slot>

    <div class="max-w-4xl mx-auto space-y-6">

        <!-- Card 1: Profile Information (Dual Light/Dark Glassmorphic) -->
        <div class="animate-fade-slide-up backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50">
            <!-- Top Sheen Line -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-amber-400 via-lime-400 to-transparent opacity-80"></div>
            
            <div class="max-w-2xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Card 2: Update Password (Dual Light/Dark Glassmorphic) -->
        <div class="animate-fade-slide-up animation-delay-150 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-zinc-200/70 dark:border-white/5 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-zinc-300/80 dark:hover:border-white/10 hover:bg-white/85 dark:hover:bg-zinc-900/50">
            <!-- Top Sheen Line -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-lime-400 via-amber-400 to-transparent opacity-80"></div>

            <div class="max-w-2xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- Card 3: Delete Account (Dual Light/Dark Glassmorphic) -->
        <div class="animate-fade-slide-up animation-delay-300 backdrop-blur-3xl bg-white/70 dark:bg-zinc-950/60 border border-rose-200/80 dark:border-rose-500/20 rounded-3xl p-6 sm:p-8 shadow-sm dark:shadow-[0_25px_60px_-15px_rgba(0,0,0,0.85)] relative overflow-hidden transition-all duration-300 hover:border-rose-300 dark:hover:border-rose-500/40 hover:bg-white/85 dark:hover:bg-zinc-900/50">
            <!-- Top Sheen Line -->
            <div class="absolute inset-x-0 top-0 h-[2px] bg-gradient-to-r from-transparent via-rose-400 to-transparent opacity-80"></div>

            <div class="max-w-2xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>

    </div>
</x-app-layout>
