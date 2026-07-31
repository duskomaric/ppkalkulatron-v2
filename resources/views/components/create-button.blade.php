@props(['href' => null, 'label'])

<{{ $href ? 'a' : 'button' }} {{ $href ? '' : 'type=button' }} @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class('shrink-0 inline-flex items-center gap-2 px-4 py-2.5 bg-primary text-white rounded-xl font-black text-[11px] uppercase tracking-[0.2em] shadow-glow-primary hover:scale-[1.02] active:scale-95 transition-all cursor-pointer') }}>
    <x-icon name="plus" class="h-4 w-4" />
    <span class="hidden sm:inline">{{ $label }}</span>
</{{ $href ? 'a' : 'button' }}>
