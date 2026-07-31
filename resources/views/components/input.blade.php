@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'icon' => null, 'required' => false, 'compact' => false])

{{-- v1 Input: ikona unutar polja, oznaka iznad, greška ispod. --}}
<div class="space-y-1.5 w-full group">
    @if ($label)<x-field-label :required="$required">{{ $label }}</x-field-label>@endif

    <div class="relative flex items-center">
        @if ($icon)
            <div class="absolute left-4 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors duration-300">
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif

        <input name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" @required($required)
               {{ $attributes->class([
                   'w-full bg-[var(--color-surface)] border rounded-2xl text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] outline-none transition-all duration-300 font-bold text-sm focus:border-primary/50 focus:ring-4 focus:ring-primary/10 focus:bg-[var(--color-surface-hover)]',
                   $icon ? 'pl-11 pr-4' : 'px-5',
                   $compact ? 'h-[44px] min-h-[44px] py-2 rounded-xl' : 'py-3.5',
                   $errors->has($name) ? 'border-red-500/50 ring-red-500/10' : 'border-[var(--color-border)]',
               ]) }}>
    </div>

    @error($name)
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
    @enderror
</div>
