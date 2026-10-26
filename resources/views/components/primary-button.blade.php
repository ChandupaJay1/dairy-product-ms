<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-gradient-to-r from-amber-400 via-yellow-400 to-lime-400 rounded-xl font-bold text-xs text-zinc-950 uppercase tracking-wider shadow-[0_10px_25px_-5px_rgba(163,230,53,0.3)] hover:shadow-[0_15px_35px_-5px_rgba(163,230,53,0.45)] transform hover:scale-[1.02] active:scale-[0.98] focus:outline-none focus:ring-4 focus:ring-lime-400/30 transition-all duration-300']) }}>
    {{ $slot }}
</button>
