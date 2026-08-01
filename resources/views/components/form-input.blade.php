@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'placeholder' => null,
    'icon' => null,
    'hint' => null,
    'compact' => false,
    'variant' => 'field',
    'autoSubmit' => false,
])

@php
    $isFilter = $variant === 'filter';

    $inputClasses = $isFilter
        ? 'h-11 w-full min-w-0 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-[10px] font-black uppercase tracking-[0.18em] text-[var(--color-text-main)] placeholder:normal-case placeholder:tracking-normal placeholder:text-[var(--color-text-dim)] focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10'
        : implode(' ', [
            'w-full bg-[var(--color-bg)] border rounded-xl text-[var(--color-text-main)] focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm placeholder:text-[var(--color-text-muted)]',
            $compact ? 'h-11 min-h-11' : 'h-12',
            $errors->has($name) ? 'border-red-500/50' : 'border-[var(--color-border)]',
        ]);

    $paddingClasses = $icon
        ? ($isFilter ? 'pl-10 pr-3' : 'pl-11 pr-4')
        : ($isFilter ? 'px-4' : 'px-4');
@endphp

<div @class([
    'relative min-w-0 w-full group' => $isFilter,
    'space-y-1.5 w-full group' => ! $isFilter,
])>
    @if ($label)
        <x-field-label variant="settings" :required="$required" :for="$name">{{ $label }}</x-field-label>
    @endif

    <div class="relative flex items-center">
        @if ($icon)
            <div @class([
                'pointer-events-none absolute z-10 text-[var(--color-text-dim)] transition-colors group-focus-within:text-primary',
                'left-3' => $isFilter,
                'left-4' => ! $isFilter,
            ])>
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif

        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
               @required($required) placeholder="{{ $placeholder }}"
               {{ $attributes->merge($autoSubmit ? ['onchange' => 'this.form.requestSubmit()'] : [])->class("{$inputClasses} {$paddingClasses}") }}>
    </div>

    @if ($hint && ! $errors->has($name))
        <p class="text-[10px] text-[var(--color-text-dim)] pl-1">{{ $hint }}</p>
    @endif

    @error($name)<p class="text-[10px] font-bold text-[var(--color-error)] ml-1 uppercase tracking-tight">{{ $message }}</p>@enderror
</div>
