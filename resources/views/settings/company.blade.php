@extends('layouts.app')
@section('title', 'Profil kompanije')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.company.update') }}" class="space-y-8 animate-fade-in">
        @csrf
        @method('PUT')

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="building" title="Osnovni podaci" :help="route('help').'#profil-kompanije'" />

            <x-form-input label="Naziv kompanije" name="name" :value="$settings->name" required />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Email" name="email" type="email" :value="$settings->email" />
                <x-form-input label="Telefon" name="phone" :value="$settings->phone" />
            </div>

            <x-form-input label="Adresa" name="address" :value="$settings->address" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Grad" name="city" :value="$settings->city" />
                <x-form-input label="Poštanski broj" name="zip" :value="$settings->zip" />
            </div>

            <x-form-input label="Država" name="country" :value="$settings->country" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="JIB" name="identification_number" :value="$settings->identification_number"
                              hint="Štampa se na dokumentima." />
                <x-form-input label="PIB" name="vat_number" :value="$settings->vat_number" />
            </div>

            <x-toggle name="is_vat_obligor" :checked="$settings->is_vat_obligor" label="PDV obveznik" />
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
