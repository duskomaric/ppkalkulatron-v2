@props(['label' => null, 'name', 'value' => null, 'rows' => 3, 'placeholder' => null, 'required' => false])

<div class="space-y-1.5 w-full">
    @if ($label)<x-field-label :required="$required" :for="$name">{{ $label }}</x-field-label>@endif

    <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required) placeholder="{{ $placeholder }}"
              class="w-full p-4 bg-[var(--color-bg)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] focus:border-primary focus:ring-1 focus:ring-primary outline-none transition-all font-bold text-sm placeholder:text-[var(--color-text-muted)] resize-none">{{ old($name, $value) }}</textarea>

    @error($name)<p class="text-[10px] font-bold text-red-500 ml-1 uppercase tracking-tight">{{ $message }}</p>@enderror
</div>
