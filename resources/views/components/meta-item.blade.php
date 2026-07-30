@props(['icon', 'label', 'value'])

<div class="flex flex-col gap-0.5">
    <span class="flex items-center gap-1 text-[9px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">
        <x-icon :name="$icon" class="h-2.5 w-2.5" />
        {{ $label }}
    </span>
    <span class="text-[11px] font-bold text-[var(--color-text-muted)]">{{ $value }}</span>
</div>
