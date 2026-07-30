@props(['value' => '', 'placeholder' => 'Pretraži...'])

<div class="relative">
    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]">
        <x-icon name="search" class="h-4 w-4" />
    </div>
    <input name="q" value="{{ $value }}" placeholder="{{ $placeholder }}"
           class="w-full h-9 pl-10 pr-3 rounded-full border border-[var(--color-border)] bg-[var(--color-surface)] text-sm font-bold text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] focus:outline-none focus:border-primary/60 focus:ring-4 focus:ring-primary/10">
</div>
