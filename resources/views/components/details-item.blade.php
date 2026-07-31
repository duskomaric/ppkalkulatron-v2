@props(['icon', 'label', 'value' => null, 'color' => 'bg-primary/10 text-primary'])

<x-card-row variant="muted" size="sm" class="gap-2.5">
    <div class="h-7 w-7 {{ $color }} rounded-lg flex items-center justify-center shrink-0">
        <x-icon :name="$icon" class="h-3.5 w-3.5" />
    </div>
    <div class="min-w-0">
        <p class="text-[10px] font-black text-[var(--color-text-dim)] uppercase tracking-[0.1em] leading-none mb-1">{{ $label }}</p>
        <p class="text-sm font-bold text-[var(--color-text-main)] truncate italic leading-tight">
            {{ filled($value) ? $value : '-' }}
        </p>
    </div>
</x-card-row>
