@props(['state' => 'emailModal'])

<div x-cloak x-show="{{ $state }}" role="dialog" aria-modal="true" aria-labelledby="email-modal-title"
     x-on:keydown.escape.window="emailSending || ({{ $state }} = false)"
     class="fixed inset-0 z-[1100] flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-[12px]" x-on:click="emailSending || ({{ $state }} = false)"></div>

    <div class="relative w-full max-w-[480px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-[var(--color-border)]">
            <h3 id="email-modal-title" class="text-lg font-black text-[var(--color-text-main)] flex items-center gap-2">
                <x-icon name="mail" class="h-5 w-5 text-primary" /> Pošalji račun mailom
            </h3>
        </div>

        <form x-on:submit.prevent="sendEmail()" class="p-6 space-y-4">
            <template x-if="emailError">
                <p class="p-3 rounded-xl border border-red-500/30 bg-red-500/10 text-[11px] font-bold text-red-500" x-text="emailError"></p>
            </template>

            <x-form-input label="Email primaoca" name="email-to" type="email" icon="mail" required
                          placeholder="klijent@email.com" x-model="emailForm.to" />
            <x-form-input label="Predmet" name="email-subject" icon="file-text" required
                          x-model="emailForm.subject" />
            <x-form-textarea label="Tekst maila" name="email-body" rows="5" required
                             placeholder="Tekst maila..." x-model="emailForm.body" />

            <div class="flex flex-col gap-2 pt-2">
                <x-toggle model="emailForm.attach_pdf" label="Priloži PDF računa" />

                <template x-for="record in emailReceipts" :key="record.id">
                    <x-toggle model="emailForm.attach_fiscal_record_ids" ::value="record.id"
                              label-expr="'Priloži fiskalni račun (' + record.type_label + ')'" />
                </template>
            </div>

            <div class="flex gap-2 pt-4">
                <x-button variant="ghost" type="button" x-on:click="{{ $state }} = false" x-bind:disabled="emailSending"
                          class="flex-1 !py-3 !text-sm !font-bold disabled:opacity-50">
                    Odustani
                </x-button>
                <x-button x-bind:disabled="emailSending" class="flex-1 !py-3 !text-sm !font-bold disabled:opacity-50">
                    <span x-show="emailSending" class="animate-spin rounded-full h-4 w-4 border-2 border-white border-t-transparent"></span>
                    <x-icon name="mail" class="h-4 w-4" x-show="! emailSending" />
                    <span x-text="emailSending ? 'Slanje...' : 'Pošalji'"></span>
                </x-button>
            </div>
        </form>
    </div>
</div>
