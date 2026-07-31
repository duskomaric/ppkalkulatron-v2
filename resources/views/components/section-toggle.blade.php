@props(['title', 'subtitle' => null, 'open' => 'open'])

{{-- $open je Alpine izraz; komponenta ga i čita i prebacuje. --}}
<x-card-row as="button" variant="accent" size="md" interactive
            x-on:click="{{ $open }} = ! {{ $open }}"
            class="w-full justify-between gap-2 px-3 py-2.5 hover:bg-[var(--color-page-bg-hover)] transition-colors">
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
