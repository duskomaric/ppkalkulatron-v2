@props(['variant' => 'primary', 'href' => null])

@php
    $classes = match ($variant) {
        'primary' => 'bg-primary text-white shadow-glow-primary hover:bg-primary-hover',
        'ghost' => 'border border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-hover)]',
        'danger' => 'border border-[var(--color-error)]/40 text-[var(--color-error)] hover:bg-[var(--color-error)]/10',
    };
    $base = 'inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold transition-all cursor-pointer '.$classes;
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($base) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->class($base)->merge(['type' => 'submit']) }}>{{ $slot }}</button>
@endif
