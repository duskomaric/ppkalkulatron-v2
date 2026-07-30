@props(['label', 'color' => 'gray'])

@php
    $colors = [
        'green' => 'bg-[var(--color-success)]/10 text-[var(--color-success)] border-[var(--color-success)]/20',
        'gray' => 'bg-[var(--color-text-muted)]/10 text-[var(--color-text-muted)] border-[var(--color-text-muted)]/20',
        'red' => 'bg-[var(--color-error)]/10 text-[var(--color-error)] border-[var(--color-error)]/20',
        'amber' => 'bg-[var(--color-warning)]/10 text-[var(--color-warning)] border-[var(--color-warning)]/20',
        'blue' => 'bg-[var(--color-info)]/10 text-[var(--color-info)] border-[var(--color-info)]/20',
    ];
@endphp

<span {{ $attributes->class('px-2 py-0.5 rounded-md text-[9px] font-black uppercase tracking-widest border backdrop-blur-md '.$colors[$color]) }}>
    {{ $label }}
</span>
