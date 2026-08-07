@extends('layouts.app')
@section('title', 'Profil kompanije')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-8 animate-fade-in">
        @csrf
        @method('PUT')

        <x-section-block variant="card">
            <x-section-header icon="building" title="Osnovni podaci" :help="route('help').'#profil-kompanije'" />

            {{-- Isti podaci stoje na sertifikatu kase, pa se mogu preuzeti umjesto prepisivati. --}}
            <div class="flex flex-col gap-2 rounded-xl border border-primary/20 bg-primary/5 p-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-[11px] font-bold leading-relaxed text-[var(--color-text-dim)]">
                    Naziv, adresu, grad, državu i JIB nosi sertifikat fiskalne kase.
                </p>
                <x-button variant="ghost" type="button" form="import-company"
                          class="shrink-0 !py-2.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                    <x-icon name="printer" class="h-4 w-4" /> Preuzmi sa kase
                </x-button>
            </div>

            <x-form-input label="Naziv kompanije" name="name" :value="$settings->name" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Email" name="email" type="email" :value="$settings->email" />
                <x-form-input label="Telefon" name="phone" type="tel" inputmode="tel" autocomplete="tel" :value="$settings->phone" />
            </div>

            <x-form-input label="Adresa" name="address" :value="$settings->address" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Grad" name="city" :value="$settings->city" />
                <x-form-input label="Poštanski broj" name="zip" inputmode="numeric" autocomplete="postal-code" :value="$settings->zip" />
            </div>

            <x-form-input label="Država" name="country" :value="$settings->country" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="JIB" name="identification_number" inputmode="numeric" :value="$settings->identification_number"
                              hint="Štampa se na dokumentima." />
                <x-form-input label="PIB" name="vat_number" inputmode="numeric" :value="$settings->vat_number" />
            </div>

            <x-toggle name="is_vat_obligor" :checked="$settings->is_vat_obligor" label="PDV obveznik" />

            <x-toggle name="is_small_entrepreneur" :checked="$settings->is_small_entrepreneur" label="Mali preduzetnik" />
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>

    {{-- Zaseban zahtjev: preuzimanje sa kase ne smije nositi nesačuvane izmjene forme. --}}
    <form id="import-company" method="POST" action="{{ route('settings.company.import') }}" class="hidden">@csrf</form>
@endsection
