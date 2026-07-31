@props(['state' => 'emailModal'])

{{-- v1 EmailModal: primalac, predmet, tekst, prilozi, pa Odustani / Pošalji. --}}
<div x-cloak x-show="{{ $state }}" class="fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[12px]" x-on:click="emailSending || ({{ $state }} = false)"></div>

    <div class="relative w-full max-w-[480px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-[var(--color-border)]">
            <h3 class="text-lg font-black text-[var(--color-text-main)] flex items-center gap-2">
                <x-icon name="mail" class="h-5 w-5 text-primary" /> Pošalji račun mailom
            </h3>
        </div>

        <form x-on:submit.prevent="sendEmail()" class="p-6 space-y-4">
            <template x-if="emailError">
                <p class="p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-[11px] font-bold text-red-500" x-text="emailError"></p>
            </template>

            <div class="space-y-1.5 w-full group">
                <label class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1 block">
                    Email primaoca<span class="text-primary ml-0.5">*</span>
                </label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors duration-300">
                        <x-icon name="mail" class="h-4 w-4" />
                    </div>
                    <input type="email" x-model="emailForm.to" required placeholder="klijent@email.com"
                           class="w-full bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] outline-none transition-all duration-300 font-bold text-sm pl-11 pr-4 py-3.5 focus:border-primary/50 focus:ring-4 focus:ring-primary/10">
                </div>
            </div>

            <div class="space-y-1.5 w-full group">
                <label class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1 block">
                    Predmet<span class="text-primary ml-0.5">*</span>
                </label>
                <div class="relative flex items-center">
                    <div class="absolute left-4 text-[var(--color-text-dim)] group-focus-within:text-primary transition-colors duration-300">
                        <x-icon name="file-text" class="h-4 w-4" />
                    </div>
                    <input type="text" x-model="emailForm.subject" required
                           class="w-full bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl text-[var(--color-text-main)] outline-none transition-all duration-300 font-bold text-sm pl-11 pr-4 py-3.5 focus:border-primary/50 focus:ring-4 focus:ring-primary/10">
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1 block">Tekst maila</label>
                <textarea x-model="emailForm.body" rows="5" required placeholder="Tekst maila..."
                          class="w-full p-4 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] font-bold text-sm outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 placeholder:text-[var(--color-text-dim)] resize-none"></textarea>
            </div>

            <div class="flex flex-col gap-2 pt-2">
                <x-switch model="emailForm.attach_pdf" label="Priloži PDF računa" />

                <template x-for="record in emailReceipts" :key="record.id">
                    <x-switch model="emailForm.attach_fiscal_record_ids" ::value="record.id"
                              label-expr="'Priloži fiskalni račun (' + record.type_label + ')'" />
                </template>
            </div>

            <div class="flex gap-2 pt-4">
                <button type="button" x-on:click="{{ $state }} = false" :disabled="emailSending"
                        class="flex-1 py-3 rounded-xl border border-[var(--color-border)] text-[var(--color-text-muted)] font-bold text-sm hover:bg-[var(--color-surface-hover)] transition-all disabled:opacity-50 cursor-pointer">
                    Odustani
                </button>
                <button type="submit" :disabled="emailSending"
                        class="flex-1 py-3 rounded-xl bg-primary text-white font-bold text-sm hover:bg-primary/90 transition-all disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2">
                    <span x-show="emailSending" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                    <x-icon name="mail" class="h-4 w-4" x-show="! emailSending" />
                    <span x-text="emailSending ? 'Slanje...' : 'Pošalji'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
