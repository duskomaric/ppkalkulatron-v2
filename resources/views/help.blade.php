@extends('layouts.app')
@section('title', 'Pomoć')

@section('content')
    <div class="space-y-5 max-w-3xl">
        <x-section title="Računi" icon="file-text">
            <p class="text-sm text-[var(--color-text-muted)]">
                Račun se sastoji od klijenta, stavki i načina plaćanja. Broj se dodjeljuje sam,
                po redu, u obliku <span class="font-bold text-[var(--color-text-main)]">0001/{{ date('Y') }}</span>.
                Ako obrišete zadnji račun, taj broj se oslobađa i sljedeći ga dobija.
            </p>
            <p class="text-sm text-[var(--color-text-muted)]">
                Cijena stavke se unosi <span class="font-bold text-[var(--color-text-main)]">sa porezom</span>.
                Osnovica i porez se izvode iz nje, kako fiskalni uređaj i očekuje.
            </p>
            <p class="text-sm text-[var(--color-text-muted)]">
                Fiskalizovan račun se više ne može mijenjati ni brisati.
            </p>
        </x-section>

        <x-section title="Klijenti" icon="contact">
            <p class="text-sm text-[var(--color-text-muted)]">
                <span class="font-bold text-[var(--color-text-main)]">JIB</span> je identifikacija kupca
                i šalje se fiskalnom uređaju. <span class="font-bold text-[var(--color-text-main)]">PDV</span>
                je poreski broj i služi samo za dokumente.
            </p>
        </x-section>

        <x-section title="Artikli" icon="boxes">
            <p class="text-sm text-[var(--color-text-muted)]">
                Poreska oznaka određuje stopu koju uređaj primjenjuje. Koje oznake važe javlja sam
                uređaj, pa ih nemojte pogađati.
            </p>
            <p class="text-sm text-[var(--color-text-muted)]">
                Zadnja cijena se pamti pri izdavanju računa i sljedeći put se ponudi sama.
            </p>
        </x-section>

        <x-section title="PIN" icon="lock">
            <p class="text-sm text-[var(--color-text-muted)]">
                PIN je opcionalan. Dok nije postavljen, aplikacija se otvara odmah. Kad ga postavite,
                traži se pri svakom pokretanju. Možete ga ukloniti u podešavanjima.
            </p>
        </x-section>
    </div>
@endsection
