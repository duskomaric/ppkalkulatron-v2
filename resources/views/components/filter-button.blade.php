@props(['state'])

<button type="button" @click="{{ $state }} = !{{ $state }}"
        :class="{{ $state }}
            ? 'border-primary/40 bg-primary/10 text-primary'
            : 'border-[var(--color-border)] bg-[var(--color-surface)] text-[var(--color-text-muted)] hover:text-[var(--color-text-main)] hover:border-[var(--color-border-strong)]'"
        class="cursor-pointer h-9 px-2.5 md:px-4 rounded-full border text-[10px] font-black uppercase tracking-[0.18em] flex items-center gap-2 transition-colors">
    <x-icon name="file-sliders" class="h-3.5 w-3.5" />
    <span class="hidden md:inline">Filteri</span>
    <x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform" ::class="{{ $state }} && 'rotate-180'" />
</button>
