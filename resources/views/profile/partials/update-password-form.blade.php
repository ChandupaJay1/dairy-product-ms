<section>
    <header>
        <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white tracking-tight transition-colors duration-300">
            {{ __('Update Security Password') }}
        </h2>

        <p class="mt-1 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
            {{ __('Ensure your enterprise account uses a strong, random password to maintain security integrity.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                {{ __('Current Password') }}
            </label>
            <input id="update_password_current_password" 
                   name="current_password" 
                   type="password" 
                   autocomplete="current-password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                {{ __('New Security Password') }}
            </label>
            <input id="update_password_password" 
                   name="password" 
                   type="password" 
                   autocomplete="new-password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                {{ __('Confirm New Password') }}
            </label>
            <input id="update_password_password_confirmation" 
                   name="password_confirmation" 
                   type="password" 
                   autocomplete="new-password"
                   placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;"
                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4 pt-2">
            <!-- Save Button: Gold to Lime Gradient with Hover Scale -->
            <button type="submit" 
                    class="relative group overflow-hidden rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 p-[1px] font-bold text-zinc-950 shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30">
                <div class="py-2.5 px-6 rounded-xl flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs sm:text-sm tracking-wide">
                    <span>{{ __('Update Password') }}</span>
                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-xs text-lime-700 dark:text-lime-400 font-semibold flex items-center gap-1.5"
                >
                    <svg class="w-4 h-4 text-lime-600 dark:text-lime-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ __('Password updated.') }}</span>
                </p>
            @endif
        </div>
    </form>
</section>
