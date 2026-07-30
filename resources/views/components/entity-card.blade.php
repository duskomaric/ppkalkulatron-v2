@props(['href' => null])

@php($classes = 'group cursor-pointer bg-[var(--color-glass)] backdrop-blur-xl border border-[var(--color-border)] rounded-xl transition-all duration-500 hover:bg-[var(--color-surface-hover)] hover:border-primary/40 p-3 flex flex-col gap-2 relative overflow-hidden')

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class($classes) }}
    style="box-shadow: 0 4px 20px rgba(var(--primary-base), 0.05)">
    {{ $slot }}
</{{ $href ? 'a' : 'div' }}>
