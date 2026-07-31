@props(['as' => 'div', 'variant' => 'surface', 'size' => 'md', 'interactive' => false])

@php
    $variants = [
        'surface' => 'bg-[var(--color-surface)] border-[var(--color-border)]',
        'muted' => 'bg-[var(--color-border)] border-[var(--color-border)]',
        'accent' => 'border-[var(--color-page-border-subtle)] bg-[var(--color-page-bg-strong)]',
    ];
    $sizes = ['sm' => 'p-2 rounded-xl', 'md' => 'p-3 rounded-xl', 'lg' => 'p-4 rounded-2xl'];
    $classes = trim('flex items-center gap-3 border '.$variants[$variant].' '.$sizes[$size]
        .($interactive ? ' group cursor-pointer transition-all' : ''));
@endphp

<{{ $as }} {{ $as === 'button' ? 'type=button' : '' }} {{ $attributes->class($classes) }}>{{ $slot }}</{{ $as }}>
