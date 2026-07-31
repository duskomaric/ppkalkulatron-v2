@extends('layouts.app')
@section('title', $currency ? 'Izmjena valute' : 'Nova valuta')

@section('content')
    <x-back-link :href="route('currencies.index')" />

    <form method="POST" action="{{ $currency ? route('currencies.update', $currency) : route('currencies.store') }}"
          class="space-y-5 max-w-3xl">
        @csrf
        @if ($currency) @method('PUT') @endif

        <x-section title="Valuta" icon="hash">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="Oznaka" name="code" :value="$currency?->code" required
                         hint="Troslovna ISO oznaka, npr. EUR." />
                <x-field label="Simbol" name="symbol" :value="$currency?->symbol" required />
                <x-field label="Naziv" name="name" :value="$currency?->name" required class="md:col-span-2" />
            </div>
        </x-section>

        <label class="flex items-center gap-3 px-1">
            <input type="checkbox" name="is_default" value="1" @checked(old('is_default', $currency?->is_default ?? false))
                   @disabled($currency?->is_default) class="h-5 w-5 rounded-md accent-[var(--color-primary)]">
            <span class="text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)]">Osnovna valuta</span>
        </label>

        <div class="flex gap-3">
            <x-button variant="primary" class="grow">{{ $currency ? 'Sačuvaj izmjene' : 'Dodaj valutu' }}</x-button>
            @if ($currency && ! $currency->is_default)
                <button type="submit" form="delete-currency" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold border border-[var(--color-error)]/40 text-[var(--color-error)] hover:bg-[var(--color-error)]/10 transition-all cursor-pointer">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </form>

    @if ($currency && ! $currency->is_default)
        <x-section title="Kursevi prema KM" icon="credit-card" class="mt-5 max-w-3xl">
            <form method="POST" action="{{ route('currencies.rates.store', $currency) }}"
                  class="grid grid-cols-1 sm:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                @csrf
                <x-field label="Kurs" name="rate_to_bam" type="number" step="0.00001" required
                         hint="Koliko KM vrijedi 1 {{ $currency->code }}." />
                <x-field label="Datum" name="rate_date" type="date" :value="now()->format('Y-m-d')" required />
                <x-button variant="ghost" class="shrink-0">Sačuvaj kurs</x-button>
            </form>

            @if ($rates->isNotEmpty())
                <div class="space-y-1.5 pt-2">
                    @foreach ($rates as $rate)
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-[var(--color-bg)]/40 text-xs font-bold">
                            <span class="text-[var(--color-text-muted)]">{{ $rate->rate_date->format('d.m.Y.') }}</span>
                            <span class="tabular-nums">{{ number_format($rate->rate_to_bam, 5, ',', '.') }} KM</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[11px] text-[var(--color-text-dim)] ml-1">
                    Bez kursa se račun u ovoj valuti ne može fiskalizovati.
                </p>
            @endif
        </x-section>
    @endif

    @if ($currency && ! $currency->is_default)
        <form id="delete-currency" method="POST" action="{{ route('currencies.destroy', $currency) }}" class="hidden"
              onsubmit="return confirm('Obrisati valutu {{ $currency->code }}?')">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
