@props(['icon', 'label', 'value' => null, 'valueClass' => ''])

<div {{ $attributes->class('flex flex-col gap-0.5') }}>
    <div class="flex items-center gap-1 text-[var(--color-text-dim)]">
        <x-icon :name="$icon" class="w-2.5 h-2.5" />
        <span class="text-[9px] font-black uppercase tracking-tight">{{ $label }}</span>
    </div>

    @if ($value !== null || ! $slot->isEmpty())
        <p class="text-xs font-bold text-[var(--color-text-muted)] {{ $valueClass }}">{{ $value ?? $slot }}</p>
    @endif
</div>
