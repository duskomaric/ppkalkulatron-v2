@props(['label' => null, 'name', 'value' => null, 'options', 'required' => false, 'placeholder' => 'Odaberi...'])

<div class="space-y-1.5 w-full">
    @if ($label)
        <x-field-label variant="settings" :required="$required" :for="$name">{{ $label }}</x-field-label>
    @endif

    <div class="relative">
        <select id="{{ $name }}" name="{{ $name }}" @required($required) {{ $attributes }}
                class="w-full h-12 px-4 bg-[var(--color-bg)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm appearance-none cursor-pointer">
            @if (! $required)<option value="">{{ $placeholder }}</option>@endif
            @foreach ($options as $optionValue => $label)
                <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $label }}</option>
            @endforeach
        </select>
        <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
            <x-icon name="chevron-down" class="h-4 w-4" />
        </div>
    </div>

    @error($name)<p class="text-[10px] font-bold text-red-500 ml-1 uppercase tracking-tight">{{ $message }}</p>@enderror
</div>
