<form method="POST" action="{{ $client ? route('clients.update', $client) : route('clients.store') }}"
      class="space-y-4">
    @csrf
    @if ($client) @method('PUT') @endif

    <x-form-errors />

    <x-section-block variant="card">
        <x-section-header icon="contact" title="Osnovni podaci" />

        <x-form-input label="Naziv" name="name" :value="$client?->name" required autocomplete="organization"
                      placeholder="npr. Kafe Bar Centar" />

        <x-toggle name="is_active" :checked="old('is_active', $client?->is_active ?? true)" label="Klijent je aktivan" />
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="mail" title="Kontakt" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form-input label="Email" name="email" type="email" :value="$client?->email" placeholder="info@klijent.com" />
            <x-form-input label="Telefon" name="phone" :value="$client?->phone" placeholder="+387 61 ..." />
        </div>
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="map-pin" title="Adresa" />

        <x-form-input label="Adresa" name="address" :value="$client?->address" />

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <x-form-input label="ZIP" name="zip" :value="$client?->zip" placeholder="71000" />
            <x-form-input label="Grad" name="city" :value="$client?->city" placeholder="Sarajevo" />
            <x-form-input label="Država" name="country" :value="$client?->country ?? 'BA'" placeholder="Bosna i Hercegovina" />
        </div>
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="hash" title="Poreski podaci" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form-input label="JIB" name="vat_id" :value="$client?->vat_id" placeholder="Identifikacioni broj"
                          hint="Ide fiskalnom uređaju kao identifikacija kupca." />
            <x-form-input label="PDV" name="tax_id" :value="$client?->tax_id" placeholder="Porezni broj" />
        </div>
    </x-section-block>

    <x-form-actions :label="$client ? 'Sačuvaj izmjene' : 'Kreiraj klijenta'"
                    :delete="$client ? route('clients.destroy', $client) : null" />
</form>

@if ($client)
    <x-delete-form :action="route('clients.destroy', $client)" :confirm="'Obrisati klijenta '.$client->name.'?'" />
@endif
