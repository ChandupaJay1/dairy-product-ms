@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-zinc-300 dark:border-zinc-800 bg-white/90 dark:bg-zinc-900/80 text-zinc-900 dark:text-zinc-100 placeholder-zinc-400 dark:placeholder-zinc-500 focus:bg-white dark:focus:bg-zinc-900 focus:border-lime-500 focus:ring-4 focus:ring-lime-400/20 focus:shadow-[0_0_15px_rgba(163,230,53,0.2)] rounded-xl shadow-sm transition-all duration-300']) }}>
