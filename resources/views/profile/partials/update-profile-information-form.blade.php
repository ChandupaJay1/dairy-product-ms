<section>
    <header>
        <h2 class="text-base sm:text-lg font-bold text-zinc-900 dark:text-white tracking-tight transition-colors duration-300">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-xs sm:text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed transition-colors duration-300">
            {{ __("Update your account's profile name and enterprise email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                {{ __('Full Name') }}
            </label>
            <input id="name" 
                   name="name" 
                   type="text" 
                   value="{{ old('name', $user->name) }}" 
                   required 
                   autofocus 
                   autocomplete="name"
                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block text-xs font-bold uppercase tracking-wider text-zinc-700 dark:text-zinc-300 mb-1.5 transition-colors duration-300">
                {{ __('Work Email Address') }}
            </label>
            <input id="email" 
                   name="email" 
                   type="email" 
                   value="{{ old('email', $user->email) }}" 
                   required 
                   autocomplete="username"
                   class="w-full px-4 py-2.5 bg-white/90 dark:bg-zinc-900/80 border border-zinc-300 dark:border-zinc-800 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 text-sm rounded-xl focus:bg-white dark:focus:bg-zinc-900 focus:outline-none focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] transition-all duration-300 shadow-sm" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 rounded-xl bg-amber-50 dark:bg-amber-500/10 border border-amber-200 dark:border-amber-500/20 text-xs text-amber-800 dark:text-amber-300">
                    <p>
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="underline font-bold text-amber-900 dark:text-amber-200 hover:text-black dark:hover:text-white transition-colors ml-1 focus:outline-none">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-semibold text-lime-700 dark:text-lime-400">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <!-- Save Button: Gold to Lime Gradient with Hover Scale -->
            <button type="submit" 
                    class="relative group overflow-hidden rounded-xl bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 p-[1px] font-bold text-zinc-950 shadow-[0_10px_25px_-5px_rgba(163,230,53,0.35)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.5)] transform transition-all duration-300 hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30">
                <div class="py-2.5 px-6 rounded-xl flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 text-zinc-950 font-bold text-xs sm:text-sm tracking-wide">
                    <span>{{ __('Save Changes') }}</span>
                    <svg class="w-4 h-4 transition-transform duration-300 group-hover:translate-x-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </div>
            </button>

            @if (session('status') === 'profile-updated')
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
                    <span>{{ __('Profile saved.') }}</span>
                </p>
            @endif
        </div>
    </form>
</section>
