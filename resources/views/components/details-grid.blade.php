@props(['columns' => 2])

@php
    $cols = [2 => 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4', 3 => 'grid-cols-3', 4 => 'grid-cols-4'][$columns]
        ?? 'grid-cols-2 md:grid-cols-3 lg:grid-cols-4';
@endphp

<div {{ $attributes->class("grid $cols gap-2") }}>{{ $slot }}</div>
