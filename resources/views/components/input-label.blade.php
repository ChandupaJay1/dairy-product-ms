@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-bold text-xs uppercase tracking-wider text-zinc-700 dark:text-zinc-300 transition-colors duration-300']) }}>
    {{ $value ?? $slot }}
</label>
