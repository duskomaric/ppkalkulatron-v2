{{-- v1 ConfirmModal: potvrda prije radnje koja se ne poništava. --}}
<div x-cloak x-show="confirm.open" role="dialog" aria-modal="true"
     x-on:keydown.escape.window="confirm.open = false" z-[1200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[12px]" x-on:click="confirm.running || (confirm.open = false)"></div>

    <div class="relative w-full max-w-[400px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 space-y-2">
            <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                <x-icon name="alert" class="h-5 w-5" />
            </div>
            <p class="text-sm font-bold text-[var(--color-text-main)]" x-text="confirm.message"></p>
        </div>

        <div class="flex gap-2 p-6 pt-0">
            <button type="button" x-on:click="confirm.open = false" :disabled="confirm.running"
                    class="flex-1 py-3 rounded-xl border border-[var(--color-border)] text-[var(--color-text-muted)] font-bold text-sm hover:bg-[var(--color-surface-hover)] transition-all disabled:opacity-50 cursor-pointer">
                Odustani
            </button>
            <button type="button" x-on:click="runConfirmed()" :disabled="confirm.running"
                    class="flex-1 py-3 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-all disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                <span x-show="confirm.running" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                <span>Potvrdi</span>
            </button>
        </div>
    </div>
</div>
