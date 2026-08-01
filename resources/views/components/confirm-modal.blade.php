{{-- Jedina potvrda za radnje koje se ne mogu poništiti. --}}
<div x-cloak x-show="$store.confirmation.open" role="dialog" aria-modal="true" aria-describedby="confirmation-message"
     x-on:keydown.escape.window="$store.confirmation.dismiss()"
     class="fixed inset-0 z-[1200] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[12px]" x-on:click="$store.confirmation.dismiss()"></div>

    <div class="relative w-full max-w-[400px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 space-y-2">
            <div class="h-10 w-10 rounded-xl bg-amber-500/10 text-amber-500 flex items-center justify-center">
                <x-icon name="alert" class="h-5 w-5" />
            </div>
            <p id="confirmation-message" class="text-sm font-bold text-[var(--color-text-main)]" x-text="$store.confirmation.message"></p>
        </div>

        <div class="flex gap-2 p-6 pt-0">
            <x-button variant="ghost" type="button" x-on:click="$store.confirmation.dismiss()" x-bind:disabled="$store.confirmation.running"
                      class="flex-1 !py-3 !text-sm !font-bold disabled:opacity-50">
                Odustani
            </x-button>
            <x-button type="button" x-on:click="$store.confirmation.execute()" x-bind:disabled="$store.confirmation.running"
                      class="flex-1 !py-3 !text-sm !font-bold disabled:opacity-50">
                <span x-show="$store.confirmation.running" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                <span>Potvrdi</span>
            </x-button>
        </div>
    </div>
</div>
