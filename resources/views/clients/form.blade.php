@extends('layouts.app')
@section('title', $client ? 'Izmjena klijenta' : 'Novi klijent')

@section('content')
    <a href="{{ route('clients.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[var(--color-text-dim)] hover:text-primary transition-colors mb-5">
        <x-icon name="arrow-left" class="h-4 w-4" /> Nazad
    </a>

    <form method="POST" action="{{ $client ? route('clients.update', $client) : route('clients.store') }}" class="space-y-5 max-w-3xl">
        @csrf
        @if ($client) @method('PUT') @endif

        <x-section title="Osnovni podaci" icon="contact">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="Naziv" name="name" :value="$client?->name" required class="md:col-span-2" />
                <x-field label="Email" name="email" type="email" :value="$client?->email" />
                <x-field label="Telefon" name="phone" :value="$client?->phone" />
            </div>
        </x-section>

        <x-section title="Adresa" icon="contact">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="Adresa" name="address" :value="$client?->address" />
                <x-field label="Grad" name="city" :value="$client?->city" />
                <x-field label="Poštanski broj" name="zip" :value="$client?->zip" />
                <x-field label="Država" name="country" :value="$client?->country ?? 'BA'" />
            </div>
        </x-section>

        <x-section title="Poreski podaci" icon="hash">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="JIB" name="vat_id" :value="$client?->vat_id"
                         hint="Ide fiskalnom uređaju kao identifikacija kupca." />
                <x-field label="PDV" name="tax_id" :value="$client?->tax_id" />
            </div>
        </x-section>

        <label class="flex items-center gap-3 px-1">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $client?->is_active ?? true))
                   class="h-5 w-5 rounded-md accent-[var(--color-primary)]">
            <span class="text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)]">Klijent je aktivan</span>
        </label>

        <div class="flex gap-3">
            <x-button variant="primary" class="grow">{{ $client ? 'Sačuvaj izmjene' : 'Kreiraj klijenta' }}</x-button>
            @if ($client)
                <button type="submit" form="delete-client" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold border border-[var(--color-error)]/40 text-[var(--color-error)] hover:bg-[var(--color-error)]/10 transition-all cursor-pointer">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </form>

    @if ($client)
        <form id="delete-client" method="POST" action="{{ route('clients.destroy', $client) }}" class="hidden"
              onsubmit="return confirm('Obrisati klijenta {{ $client->name }}?')">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
