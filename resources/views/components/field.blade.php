@props(['label', 'name', 'type' => 'text', 'value' => null, 'options' => null, 'rows' => null, 'required' => false, 'step' => null, 'hint' => null])

<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] ml-1">
        {{ $label }}
    </label>

    @php($classes = 'w-full px-4 py-3 bg-[var(--color-surface)] border rounded-2xl text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all font-bold text-sm outline-none '.($errors->has($name) ? 'border-[var(--color-error)]' : 'border-[var(--color-border)]'))

    @if ($options !== null)
        <select id="{{ $name }}" name="{{ $name }}" @required($required) class="{{ $classes }}">
            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) old($name, $value) === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif ($rows)
        <textarea id="{{ $name }}" name="{{ $name }}" rows="{{ $rows }}" @required($required)
                  class="{{ $classes }} resize-none">{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}"
               @if ($step) step="{{ $step }}" @endif @required($required) {{ $attributes }} class="{{ $classes }}">
    @endif

    @if ($hint && ! $errors->has($name))
        <p class="text-[11px] text-[var(--color-text-dim)] ml-1">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
    @enderror
</div>
