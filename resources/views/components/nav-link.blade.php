@props(['active' => false, 'icon' => null])

<a {{ $attributes->class([
        'group relative flex items-center gap-1.5 px-2.5 py-1.5 rounded-xl transition-all text-[10px] font-black uppercase tracking-widest',
        'bg-primary/20 text-primary shadow-glow-primary ring-1 ring-primary/30' => $active,
        'text-[var(--color-text-dim)] hover:text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)]' => ! $active,
    ]) }}>
    @if ($icon)
        <span @class([
            'h-5 w-5 rounded-lg flex items-center justify-center transition-colors',
            'bg-primary/20 text-primary' => $active,
            'bg-[var(--color-border)] text-[var(--color-text-dim)] group-hover:text-[var(--color-text-main)]' => ! $active,
        ])>
            <x-icon :name="$icon" class="h-3.5 w-3.5" />
        </span>
    @endif
    <span class="leading-none">{{ $slot }}</span>
</a>
