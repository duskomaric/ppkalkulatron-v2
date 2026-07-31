@extends('layouts.app')
@section('title', $account ? 'Izmjena bankovnog računa' : 'Novi bankovni račun')

@section('content')
    <x-back-link :href="route('bank-accounts.index')" />

    <form method="POST" action="{{ $account ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}"
          class="space-y-5 max-w-3xl">
        @csrf
        @if ($account) @method('PUT') @endif

        <x-section title="Podaci o računu" icon="credit-card">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="Naziv banke" name="bank_name" :value="$account?->bank_name" required class="md:col-span-2" />
                <x-field label="Broj računa" name="account_number" :value="$account?->account_number" required />
                <x-field label="SWIFT" name="swift" :value="$account?->swift"
                         hint="Potreban samo za plaćanja iz inostranstva." />
            </div>
        </x-section>

        <label class="flex items-center gap-3 px-1">
            <input type="checkbox" name="show_on_documents" value="1"
                   @checked(old('show_on_documents', $account?->show_on_documents ?? true))
                   class="h-5 w-5 rounded-md accent-[var(--color-primary)]">
            <span class="text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)]">Prikaži na dokumentima</span>
        </label>

        <div class="flex gap-3">
            <x-button variant="primary" class="grow">{{ $account ? 'Sačuvaj izmjene' : 'Dodaj račun' }}</x-button>
            @if ($account)
                <button type="submit" form="delete-account" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold border border-[var(--color-error)]/40 text-[var(--color-error)] hover:bg-[var(--color-error)]/10 transition-all cursor-pointer">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </form>

    @if ($account)
        <form id="delete-account" method="POST" action="{{ route('bank-accounts.destroy', $account) }}" class="hidden"
              onsubmit="return confirm('Obrisati račun {{ $account->account_number }}?')">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
