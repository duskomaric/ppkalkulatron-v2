@props(['name', 'value' => '', 'options'])

<div class="relative min-w-[140px] w-full">
    <select name="{{ $name }}" onchange="this.form.requestSubmit()"
            class="h-9 w-full min-w-0 pl-4 pr-9 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-[10px] font-black uppercase tracking-[0.18em] text-[var(--color-text-main)] appearance-none cursor-pointer focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10">
        @foreach ($options as $optionValue => $label)
            <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $label }}</option>
        @endforeach
    </select>
    <div class="absolute right-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
        <x-icon name="chevron-down" class="h-3.5 w-3.5" />
    </div>
</div>
