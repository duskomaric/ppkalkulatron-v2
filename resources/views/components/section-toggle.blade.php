@props(['title', 'subtitle' => null, 'open' => 'open', 'help' => null])

<div class="flex items-center gap-2">
    <x-card-row as="button" variant="accent" size="md" interactive
                x-on:click="{{ $open }} = ! {{ $open }}"
                class="min-w-0 flex-1 justify-between gap-2 px-3 py-2.5 hover:bg-[var(--color-page-bg-hover)] transition-colors">
        <div class="flex items-center gap-2">
            <div class="h-7 w-7 bg-[var(--color-page-bg-strong)] text-[var(--color-primary)] rounded-lg flex items-center justify-center shrink-0">
                <x-icon name="chevron-down" class="h-4 w-4" x-show="! ({{ $open }})" />
                <x-icon name="chevron-up" class="h-4 w-4" x-show="{{ $open }}" x-cloak />
            </div>
            <div class="text-left">
                <p class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-primary)]">{{ $title }}</p>
                @if ($subtitle)
                    <p class="text-[10px] text-[var(--color-primary)] opacity-80">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <span class="text-[11px] font-bold text-[var(--color-primary)]" x-text="{{ $open }} ? 'Sakrij' : 'Prikaži'"></span>
    </x-card-row>

    @if ($help)
        <a href="{{ $help }}" title="Pomoć"
           class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[var(--color-border)] text-[var(--color-text-dim)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]">
            <x-icon name="info" class="h-4 w-4" />
        </a>
    @endif
</div>
