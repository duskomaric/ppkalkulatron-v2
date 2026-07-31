@props(['required' => false, 'for' => null])

<label @if($for) for="{{ $for }}" @endif
       class="text-[11px] font-black uppercase tracking-wider text-[var(--color-text-dim)] pl-1 block">
    {{ $slot }}@if ($required)<span class="text-primary ml-1">*</span>@endif
</label>
