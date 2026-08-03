@extends('layouts.app')
@section('title', 'Generalno')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.general.update') }}" class="space-y-8 animate-fade-in">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="hash" title="Numeracija računa" subtitle="Format: PREFIKS-broj/godina (npr. INV-0001/{{ date('Y') }})"
                              :help="route('help').'#numeracija'" />

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <x-form-input label="Prefiks računa" name="invoice_prefix" :value="$numbering->invoice_prefix" placeholder="npr. INV" />
                <x-form-input label="Početni broj računa" name="invoice_starting_number" type="number" :value="$numbering->invoice_starting_number" required />
                <x-form-input label="Broj nula (padding)" name="pad_zeros" type="number" :value="$numbering->pad_zeros" required />
            </div>

            <x-toggle name="reset_yearly" :checked="$numbering->reset_yearly" label="Resetuj numeraciju godišnje" />
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Zadane vrijednosti novog računa" :help="route('help').'#racuni'" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-select label="Jezik" name="language" :value="$document->language" required
                               :options="\App\Enums\DocumentLanguage::options()" />
                <x-form-input label="Rok plaćanja (dana)" name="invoice_due_days" type="number" :value="$document->invoice_due_days" required />
            </div>

            <div x-data>
                <x-form-textarea label="Podrazumijevana napomena na računu" name="invoice_notes" rows="3"
                                 :value="$document->invoice_notes" placeholder="Napomene na novim računima..." x-ref="invoiceNotes" />
                <div class="mt-3 flex flex-wrap gap-2">
                    <button type="button" x-on:click="$refs.invoiceNotes.value = [$refs.invoiceNotes.value, @js($company->name.' nije u sistemu PDV-a.')].filter((value, index, values) => value && values.indexOf(value) === index).join('\n')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-bold text-[var(--color-text-muted)] hover:border-primary/40 hover:text-primary">
                        <x-icon name="plus" class="h-3.5 w-3.5" /> Nije u PDV sistemu
                    </button>
                    <button type="button" x-on:click="$refs.invoiceNotes.value = [$refs.invoiceNotes.value, 'Ova faktura je validna bez pečata i potpisa.'].filter((value, index, values) => value && values.indexOf(value) === index).join('\n')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-[var(--color-border)] px-3 py-2 text-xs font-bold text-[var(--color-text-muted)] hover:border-primary/40 hover:text-primary">
                        <x-icon name="plus" class="h-3.5 w-3.5" /> Validna bez pečata
                    </button>
                </div>
            </div>
        </x-section-block>

        {{-- Uža ivica od ostalih kartica: minijature dobijaju svu širinu koju imaju. --}}
        <x-section-block variant="card" class="sm:p-6 space-y-6">
            <x-section-header icon="file-text" title="Izgled računa" subtitle="Minijatura je stvarni PDF sa oglednim podacima."
                              :help="route('help').'#racuni'" />

            <x-document-template-gallery :value="$document->template" />
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
