<section class="space-y-6">
    <header>
        <h2 class="text-base sm:text-lg font-bold text-rose-700 dark:text-rose-400 tracking-tight flex items-center gap-2 transition-colors duration-300">
            <svg class="w-5 h-5 text-rose-600 dark:text-rose-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            {{ __('Danger Zone: Delete Account') }}
        </h2>

        <p class="mt-1 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
            {{ __('Once your account is deleted, all enterprise resources, tokens, and telemetry bindings will be permanently purged. Please download any critical data before proceeding.') }}
        </p>
    </header>

    <div>
        <button
            type="button"
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
            class="px-4 py-2.5 rounded-xl bg-rose-50 dark:bg-rose-500/10 hover:bg-rose-100 dark:hover:bg-rose-500/20 text-rose-700 dark:text-rose-400 border border-rose-200 dark:border-rose-500/30 text-xs sm:text-sm font-bold transition-all duration-200 focus:outline-none focus:ring-4 focus:ring-rose-500/20 shadow-sm"
        >
            {{ __('Delete Account') }}
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6 bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 text-zinc-800 dark:text-zinc-100 rounded-2xl shadow-xl transition-colors duration-300">
            @csrf
            @method('delete')

            <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white tracking-tight">
                {{ __('Confirm Account Deletion') }}
            </h2>

            <p class="mt-2 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                {{ __('Are you sure you want to permanently delete your account? Please enter your current password to authorize this action.') }}
            </p>

            <div class="mt-5">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5">
                    {{ __('Confirm With Password') }}
                </label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="w-full px-4 py-2.5 bg-white dark:bg-zinc-950 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:outline-none focus:border-rose-500 focus:ring-4 focus:ring-rose-500/20 transition-all duration-300 shadow-sm"
                    placeholder="{{ __('Your password') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" 
                        x-on:click="$dispatch('close')"
                        class="px-4 py-2 rounded-xl bg-zinc-100 dark:bg-zinc-800 hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 text-xs font-bold transition-colors">
                    {{ __('Cancel') }}
                </button>

                <button type="submit" 
                        class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs transition-colors shadow-md shadow-rose-600/30">
                    {{ __('Delete Account') }}
                </button>
            </div>
        </form>
    </x-modal>
</section>
