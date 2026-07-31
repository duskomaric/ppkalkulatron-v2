@props(['variant' => 'plain'])

@php($classes = match ($variant) {
    'card' => 'rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)]/40 p-3 space-y-3',
    'accent' => 'rounded-2xl border-2 border-dashed border-primary/30 bg-primary/5 p-3 space-y-3',
    default => 'space-y-2',
})

<div {{ $attributes->class($classes) }}>{{ $slot }}</div>
