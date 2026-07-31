@props(['label'])

<button type="button" {{ $attributes->class('w-full py-3.5 bg-[var(--color-border)] text-[var(--color-text-muted)] border border-[var(--color-border)] rounded-xl font-black text-[10px] uppercase tracking-widest hover:text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)] transition-all cursor-pointer') }}>
    {{ $label }}
</button>
