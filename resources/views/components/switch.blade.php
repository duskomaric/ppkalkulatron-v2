@props(['model', 'label' => null, 'labelExpr' => null])

{{-- v1 Toggle, ali vezan na Alpine model umjesto na ime polja u formi. --}}
<label class="flex items-center gap-3 border bg-[var(--color-surface)] border-[var(--color-border)] p-3 rounded-xl group cursor-pointer transition-all">
    <div class="relative flex items-center">
        <input type="checkbox" x-model="{{ $model }}" {{ $attributes }} class="sr-only peer">
        <div class="w-9 h-5 bg-[var(--color-border-strong)] rounded-full peer-focus:outline-none peer-checked:bg-primary after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:h-4 after:w-4 after:rounded-full after:bg-gray-400 after:border after:border-gray-300 after:transition-all peer-checked:after:translate-x-full peer-checked:after:bg-white relative"></div>
    </div>

    @if ($labelExpr)
        <span class="text-[13px] font-bold text-[var(--color-text-muted)]" x-text="{{ $labelExpr }}"></span>
    @elseif ($label)
        <span class="text-[13px] font-bold text-[var(--color-text-muted)]">{{ $label }}</span>
    @endif
</label>
