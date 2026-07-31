@props(['required' => false, 'variant' => 'default'])

@php
    $base = $variant === 'settings'
        ? 'text-[11px] font-black uppercase tracking-wider text-[var(--color-text-dim)] pl-1 block'
        : 'text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1 block';
@endphp

<label {{ $attributes->class($base) }}>
    {{ $slot }}
    @if ($required)<span class="text-primary {{ $variant === 'settings' ? 'ml-1' : 'ml-0.5' }}">*</span>@endif
</label>
