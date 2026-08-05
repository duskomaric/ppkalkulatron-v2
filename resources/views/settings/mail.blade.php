@extends('layouts.app')
@section('title', 'Mail')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <form method="POST" action="{{ route('settings.mail.update') }}" class="space-y-8 animate-fade-in">
        @csrf
        @method('PUT')

        <x-section-block variant="card">
            <x-section-header icon="mail" title="Pošiljalac" :help="route('help').'#mail'" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Adresa pošiljaoca" name="from_address" type="email" :value="$settings->from_address" />
                <x-form-input label="Ime pošiljaoca" name="from_name" :value="$settings->from_name" />
            </div>
        </x-section-block>

        <x-section-block variant="card">
            <x-section-header icon="cog" title="SMTP" subtitle="Ostavite host prazan da se šalje podrazumijevanim mailerom" :help="route('help').'#mail'" />

            <x-form-input label="Host" name="host" :value="$settings->host" placeholder="npr. smtp.gmail.com" />

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-form-input label="Port" name="port" type="number" inputmode="numeric" :value="$settings->port" placeholder="587" />
                <x-form-select label="Enkripcija" name="encryption" :value="$settings->encryption"
                               :options="['tls' => 'TLS', 'ssl' => 'SSL']" placeholder="Bez enkripcije" />
                <x-form-input label="Korisničko ime" name="username" :value="$settings->username" />
                <x-form-input label="Lozinka" name="password" type="password"
                              hint="Ostavite prazno da zadržite postojeću." />
            </div>
        </x-section-block>

        <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
            Sačuvaj izmjene
        </x-button>
    </form>
@endsection
