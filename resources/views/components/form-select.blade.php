@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'options' => [],
    'required' => false,
    'placeholder' => 'Odaberi...',
    'showPlaceholder' => true,
    'icon' => null,
    'compact' => false,
    'hint' => null,
    'variant' => 'field',
    'autoSubmit' => false,
])

@php
    $inputId = $id ?? $name;
    $selectedValue = $name ? old($name, $value) : $value;
    $hasCustomOptions = ! $slot->isEmpty();

    $selectClasses = $variant === 'filter'
        ? 'h-11 w-full min-w-0 pl-4 pr-10 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-[10px] font-black uppercase tracking-[0.18em] text-[var(--color-text-main)] appearance-none cursor-pointer focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10'
        : implode(' ', [
            'w-full appearance-none bg-[var(--color-bg)] border rounded-xl text-[var(--color-text-main)] font-bold text-sm pr-10 outline-none focus:border-primary focus:ring-1 focus:ring-primary cursor-pointer',
            $compact ? 'h-11 min-h-11' : 'h-12',
            $icon ? ($compact ? 'pl-11' : 'pl-10') : 'pl-4',
            $errors->has($name) ? 'border-red-500/50' : 'border-[var(--color-border)]',
        ]);
@endphp

<div @class([
    'relative min-w-[140px] w-full' => $variant === 'filter',
    'space-y-1.5 w-full group' => $variant !== 'filter',
])>
    @if ($label)
        <x-field-label variant="settings" :required="$required" :for="$inputId">{{ $label }}</x-field-label>
    @endif

    <div class="relative">
        @if ($icon && $variant !== 'filter')
            <div @class([
                'absolute top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors',
                'left-4' => $compact,
                'left-3' => ! $compact,
            ])>
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif

        <select @if ($inputId) id="{{ $inputId }}" @endif @if ($name) name="{{ $name }}" @endif @required($required)
                {{ $attributes->merge($autoSubmit ? ['onchange' => 'this.form.requestSubmit()'] : [])->class($selectClasses) }}>
            @if ($hasCustomOptions)
                {{ $slot }}
            @else
                @if ($variant !== 'filter' && ! $required && $showPlaceholder)
                    <option value="">{{ $placeholder }}</option>
                @endif

                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $selectedValue === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            @endif
        </select>

        <div @class([
            'absolute top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none',
            'right-3' => $variant === 'filter',
            'right-4' => $variant !== 'filter',
        ])>
            <x-icon name="chevron-down" @class(['h-3.5 w-3.5' => $variant === 'filter', 'h-4 w-4' => $variant !== 'filter']) />
        </div>
    </div>

    @if ($hint && ! $errors->has($name))
        <p class="text-[10px] text-[var(--color-text-dim)] pl-1">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-[10px] font-bold text-[var(--color-error)] ml-1 uppercase tracking-tight">{{ $message }}</p>
    @enderror
</div>
