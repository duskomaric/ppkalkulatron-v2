@props(['href' => null])

@php($classes = 'group cursor-pointer bg-[var(--color-glass)] backdrop-blur-xl border border-[var(--color-border)] rounded-xl shadow-[0_4px_20px_rgba(var(--primary-base),0.05)] transition-all duration-500 hover:bg-[var(--color-surface-hover)] hover:border-primary/40 p-3 flex flex-col gap-2 relative overflow-hidden')

<{{ $href ? 'a' : 'div' }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->class($classes) }}>
    {{ $slot }}
</{{ $href ? 'a' : 'div' }}>
