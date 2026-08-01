<div x-cloak x-show="receiptModal" role="dialog" aria-modal="true"
     x-on:keydown.escape.window="closeReceipt()"
     class="fixed inset-0 z-[1200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/80 backdrop-blur-sm" x-on:click="closeReceipt()"></div>

    <div class="relative max-w-[95vw] max-h-[95vh] flex flex-col items-center">
        <h3 class="mb-2 text-center text-sm font-bold text-white" x-text="receiptKind === 'pdf' ? 'Fiskalni račun (PDF)' : 'Fiskalni račun'"></h3>

        <div x-show="receiptLoading" class="flex min-h-40 items-center justify-center rounded-xl bg-white/10 px-8 text-sm font-bold text-white">
            Učitavanje računa…
        </div>

        <div x-show="receiptError" class="max-w-sm rounded-xl bg-white px-6 py-5 text-center">
            <p x-text="receiptError" class="text-sm font-bold text-[var(--color-text-main)]"></p>
            <a :href="receiptSourceUrl" class="mt-4 inline-flex items-center justify-center rounded-lg bg-primary px-4 py-2 text-xs font-black uppercase tracking-wider text-white">
                Otvori direktno
            </a>
        </div>

        <template x-if="!receiptLoading && !receiptError && receiptKind === 'image'">
            <img :src="receiptUrl" x-on:error="receiptFailed()" alt="Fiskalni račun"
                 class="max-w-full max-h-[85vh] object-contain rounded-xl shadow-2xl bg-white">
        </template>

        <template x-if="!receiptLoading && !receiptError && receiptKind === 'html'">
            <iframe :srcdoc="receiptHtml" title="Fiskalni račun"
                    sandbox class="h-[80vh] w-[90vw] max-w-[900px] rounded-xl bg-white"></iframe>
        </template>

        <template x-if="!receiptLoading && !receiptError && receiptKind === 'pdf'">
            <iframe :src="receiptUrl" title="Fiskalni račun"
                    class="w-[90vw] max-w-[900px] h-[80vh] rounded-xl bg-white"></iframe>
        </template>

        <div class="mt-4 flex items-center gap-2">
            <button type="button" x-show="receiptVerificationUrl" x-on:click="window.location.assign(receiptVerificationUrl)"
                    class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-white/20">
                <x-icon name="external-link" class="h-5 w-5" />
                Provjeri
            </button>
            <button type="button" x-on:click="closeReceipt()"
                    class="inline-flex items-center gap-2 rounded-xl bg-white/10 px-3 py-2 text-xs font-bold text-white transition-colors hover:bg-white/20">
                <x-icon name="x" class="h-5 w-5" />
                Zatvori
            </button>
        </div>
    </div>
</div>
