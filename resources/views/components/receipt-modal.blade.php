{{--
    v1 ImageModal: sadržaj na tamnoj podlozi, bez okvira kartice. Slika ide u
    prirodnom odnosu stranica (isječak je uzak i visok), a PDF i HTML u okvir.
--}}
<div x-cloak x-show="receiptModal" class="fixed inset-0 z-[1200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" x-on:click="receiptModal = false"></div>

    <div class="relative max-w-[95vw] max-h-[95vh] flex flex-col items-center">
        <h3 class="text-white font-bold text-sm mb-2 text-center">Fiskalni račun</h3>

        <template x-if="receiptKind === 'image'">
            <img :src="receiptUrl" alt="Fiskalni račun"
                 class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl bg-white">
        </template>

        <template x-if="receiptKind !== 'image'">
            <iframe :src="receiptUrl" title="Fiskalni račun" sandbox="allow-same-origin"
                    class="w-[90vw] max-w-[900px] h-[80vh] rounded-xl bg-white"></iframe>
        </template>

        <div class="mt-4 flex items-center gap-2">
            <a :href="receiptUrl" target="_blank" rel="noopener noreferrer" aria-label="Otvori u novom tabu"
               class="p-3 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors">
                <x-icon name="external-link" class="h-5 w-5" />
            </a>
            <button type="button" x-on:click="receiptModal = false" aria-label="Zatvori"
                    class="p-3 rounded-xl bg-white/10 hover:bg-white/20 text-white transition-colors cursor-pointer">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
    </div>
</div>
