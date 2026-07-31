@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'required' => false, 'placeholder' => null, 'icon' => null, 'hint' => null])

<div class="space-y-1.5 w-full group">
    @if ($label)
        <x-field-label variant="settings" :required="$required" :for="$name">{{ $label }}</x-field-label>
    @endif

    <div class="relative flex items-center">
        @if ($icon)
            <div class="absolute left-4 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors">
                <x-icon :name="$icon" class="h-4 w-4" />
            </div>
        @endif

        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
               @required($required) placeholder="{{ $placeholder }}" {{ $attributes }}
               class="w-full h-12 bg-[var(--color-bg)] border rounded-xl text-[var(--color-text-main)] focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm placeholder:text-[var(--color-text-muted)] {{ $icon ? 'pl-11 pr-4' : 'px-4' }} {{ $errors->has($name) ? 'border-red-500/50' : 'border-[var(--color-border)]' }}">
    </div>

    @if ($hint && ! $errors->has($name))
        <p class="text-[10px] text-[var(--color-text-dim)] pl-1">{{ $hint }}</p>
    @endif

    @error($name)<p class="text-[10px] font-bold text-red-500 ml-1 uppercase tracking-tight">{{ $message }}</p>@enderror
</div>
