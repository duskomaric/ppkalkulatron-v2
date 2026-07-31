@props(['label' => null, 'name', 'value' => null, 'icon' => null, 'options' => [], 'required' => false, 'compact' => false])

{{-- v1 SelectField: ikona unutar polja, bez praznog izbora. --}}
<div class="space-y-1.5 group">
    @if ($label)<x-field-label :required="$required">{{ $label }}</x-field-label>@endif

    <div class="relative">
        @if ($icon)
            <div class="absolute left-{{ $compact ? '4' : '3' }} top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors">
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif

        <select name="{{ $name }}" @required($required)
                {{ $attributes->class([
                    'w-full bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] font-bold text-sm pr-10 outline-none focus:border-primary/50 cursor-pointer',
                    $compact ? 'h-[44px] min-h-[44px] py-2 focus:ring-4 focus:ring-primary/10' : 'min-h-[44px] py-3',
                    $icon ? ($compact ? 'pl-11' : 'pl-10') : 'pl-4',
                ]) }}>
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    </div>

    @error($name)
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
    @enderror
</div>
