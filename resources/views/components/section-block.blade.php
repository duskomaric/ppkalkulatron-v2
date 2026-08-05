@props(['variant' => 'plain'])

{{--
    Padding je dio komponente, ne stvar pojedinačnog ekrana: kad se dodavao ručno,
    sekcije pisane bez njega ostajale su uže od susjednih u istoj koloni.
--}}
@php($classes = match ($variant) {
    'card' => 'rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)]/40 p-3 sm:p-8 space-y-3 sm:space-y-6',
    'accent' => 'rounded-2xl border-2 border-dashed border-primary/30 bg-primary/5 p-3 sm:p-6 space-y-3 sm:space-y-4',
    default => 'space-y-2',
})

<div {{ $attributes->class($classes) }}>{{ $slot }}</div>
