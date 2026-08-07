@props(['icon', 'title', 'description', 'href' => null])

{{-- Stavka vodi na stranicu, ili je dugme kad panel ostaje na istom ekranu. --}}
@php
    $classes = 'w-full text-left p-3.5 rounded-xl transition-all flex items-center gap-3 border border-transparent hover:border-[var(--color-border)] hover:bg-[var(--color-surface-hover)] group cursor-pointer';
@endphp

<{{ $href ? 'a' : 'button' }}
    @if ($href) href="{{ $href }}" @else type="button" @endif
    {{ $attributes->class($classes) }}>
    <div class="h-9 w-9 bg-[var(--color-border)] rounded-lg flex items-center justify-center text-[var(--color-text-dim)] group-hover:text-primary transition-colors shrink-0">
        <x-icon :name="$icon" class="h-4 w-4" />
    </div>
    <div class="min-w-0">
        <p class="text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)] group-hover:text-[var(--color-text-main)] transition-colors">{{ $title }}</p>
        <p class="text-[11px] text-[var(--color-text-dim)] truncate">{{ $description }}</p>
    </div>
</{{ $href ? 'a' : 'button' }}>
