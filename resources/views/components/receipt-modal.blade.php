{{-- v1 ImageModal: slika fiskalnog računa preko cijelog ekrana. --}}
<div x-cloak x-show="receiptModal" class="fixed inset-0 z-[1200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-[12px]" x-on:click="receiptModal = false"></div>

    <div class="relative w-full max-w-[560px] max-h-[90vh] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-xl overflow-hidden flex flex-col">
        <div class="p-4 border-b border-[var(--color-border)] flex items-center justify-between gap-3">
            <h3 class="text-sm font-black text-[var(--color-text-main)] flex items-center gap-2">
                <x-icon name="image" class="h-4 w-4 text-primary" /> Fiskalni račun
            </h3>
            <div class="flex items-center gap-2">
                <a :href="receiptUrl" target="_blank" rel="noopener noreferrer" title="Otvori u novom tabu"
                   class="h-9 w-9 rounded-xl bg-[var(--color-border)] hover:bg-[var(--color-surface-hover)] flex items-center justify-center text-[var(--color-text-muted)] transition-all">
                    <x-icon name="external-link" class="h-4 w-4" />
                </a>
                <button type="button" x-on:click="receiptModal = false" aria-label="Zatvori"
                        class="h-9 w-9 rounded-xl bg-[var(--color-border)] hover:bg-[var(--color-surface-hover)] flex items-center justify-center text-[var(--color-text-muted)] transition-all cursor-pointer">
                    <x-icon name="x" class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="p-4 overflow-auto bg-white flex justify-center">
            {{-- Uređaj vraća PNG, PDF ili HTML — <object> prikaže sva tri. --}}
            <object :data="receiptUrl" class="w-full min-h-[60vh]">
                <img :src="receiptUrl" alt="Fiskalni račun" class="max-w-full h-auto">
            </object>
        </div>
    </div>
</div>
