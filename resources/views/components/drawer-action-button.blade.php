@props(['tone' => 'primary', 'icon' => null, 'label', 'href' => null])

@php
    $classes = $tone === 'danger'
        ? 'bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500 hover:text-white'
        : 'bg-primary text-white shadow-glow-primary hover:scale-[1.02] active:scale-95';
@endphp

<{{ $href ? 'a' : 'button' }} {{ $href ? '' : 'type=submit' }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class("flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl font-black text-[11px] uppercase tracking-[0.15em] transition-all group cursor-pointer $classes") }}>
    @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
    {{ $label }}
</{{ $href ? 'a' : 'button' }}>
