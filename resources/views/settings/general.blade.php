@extends('layouts.app')
@section('title', 'Generalno')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.general.update') }}" class="space-y-8 animate-fade-in">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="hash" title="Numeracija" subtitle="Format: PREFIKS-broj/godina (npr. INV-0001/{{ date('Y') }})"
                              :help="route('help').'#numeracija'" />

            <x-toggle name="reset_yearly" :checked="$numbering->reset_yearly" label="Resetuj numeraciju godišnje" />

            <x-form-input label="Broj nula (padding)" name="pad_zeros" type="number" :value="$numbering->pad_zeros" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Prefiks računa" name="invoice_prefix" :value="$numbering->invoice_prefix" placeholder="npr. INV" />
                <x-form-input label="Početni broj računa" name="invoice_starting_number" type="number" :value="$numbering->invoice_starting_number" required />
                <x-form-input label="Prefiks predračuna" name="proforma_prefix" :value="$numbering->proforma_prefix" placeholder="npr. PRO" />
                <x-form-input label="Početni broj predračuna" name="proforma_starting_number" type="number" :value="$numbering->proforma_starting_number" required />
                <x-form-input label="Prefiks ponude" name="quote_prefix" :value="$numbering->quote_prefix" placeholder="npr. PON" />
                <x-form-input label="Početni broj ponude" name="quote_starting_number" type="number" :value="$numbering->quote_starting_number" required />
            </div>
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="file-text" title="Dokumenti" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-select label="Predložak" name="template" :value="$document->template" required
                               :options="\App\Enums\DocumentTemplate::options()" />
                <x-form-select label="Jezik" name="language" :value="$document->language" required
                               :options="\App\Enums\DocumentLanguage::options()" />
                <x-form-input label="Rok plaćanja (dana)" name="invoice_due_days" type="number" :value="$document->invoice_due_days" required />
            </div>

            <x-form-textarea label="Podrazumijevana napomena na računu" name="invoice_notes" rows="3"
                             :value="$document->invoice_notes" placeholder="Napomene na novim računima..." />
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
