<form method="POST" action="{{ $account ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}"
      class="space-y-4">
    @csrf
    @if ($account) @method('PUT') @endif

    <x-form-errors />

    <x-section-block variant="card">
        <x-section-header icon="credit-card" title="Podaci o računu" :help="route('help').'#bankovni-racuni'" />

        <x-form-input label="Naziv banke" name="bank_name" :value="$account?->bank_name" required
                      placeholder="npr. NLB Banka a.d. Banja Luka" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form-input label="Broj računa" name="account_number" :value="$account?->account_number" required
                          placeholder="5620998123456789" />
            <x-form-input label="SWIFT" name="swift" :value="$account?->swift" placeholder="RAZBBA22"
                          hint="Samo za uplate iz inostranstva." />
        </div>
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="file-text" title="Prikaz" :help="route('help').'#bankovni-racuni'" />

        <x-toggle name="show_on_documents" :checked="old('show_on_documents', $account?->show_on_documents ?? true)"
                  label="Prikaži na dokumentima" />
    </x-section-block>

    <x-form-actions :label="$account ? 'Sačuvaj izmjene' : 'Dodaj račun'"
                    :delete="$account ? route('bank-accounts.destroy', $account) : null" />
</form>

@if ($account)
    <x-delete-form :action="route('bank-accounts.destroy', $account)" :confirm="'Obrisati račun '.$account->account_number.'?'" />
@endif
