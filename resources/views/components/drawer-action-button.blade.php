@props(['tone' => 'primary', 'icon' => null, 'label', 'href' => null, 'confirm' => null])

@php
    $classes = $tone === 'danger'
        ? 'bg-red-500/10 text-red-500 border border-red-500/20 hover:bg-red-500 hover:text-white'
        : 'bg-primary text-white shadow-glow-primary hover:scale-[1.02] active:scale-95';

    // Potvrda vodi na isti link, samo kroz modal — zato dugme, ne <a>.
    $element = $confirm ? 'button' : ($href ? 'a' : 'button');
@endphp

<{{ $element }} @if ($element === 'button') type="{{ $confirm ? 'button' : 'submit' }}" @endif
    @if ($href && ! $confirm) href="{{ $href }}" @endif
    @if ($confirm) x-on:click="$store.confirmation.ask(@js($confirm), () => window.location = @js($href))" @endif
    {{ $attributes->class("flex-1 flex items-center justify-center gap-2 py-3.5 rounded-xl font-black text-[11px] uppercase tracking-[0.15em] transition-all group cursor-pointer $classes") }}>
    @if ($icon)<x-icon :name="$icon" class="h-4 w-4" />@endif
    {{ $label }}
</{{ $element }}>
