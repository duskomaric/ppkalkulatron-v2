<form method="POST" action="{{ $currency ? route('currencies.update', $currency) : route('currencies.store') }}"
      class="space-y-4">
    @csrf
    @if ($currency) @method('PUT') @endif

    <x-form-errors />

    <x-section-block variant="card">
        <x-section-header icon="hash" title="Valuta" :help="route('help').'#valute'" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-form-input label="Oznaka" name="code" :value="$currency?->code" required maxlength="3"
                          placeholder="EUR" hint="Troslovna ISO oznaka." />
            <x-form-input label="Simbol" name="symbol" :value="$currency?->symbol" required placeholder="€" />
        </div>

        <x-form-input label="Naziv" name="name" :value="$currency?->name" required placeholder="Euro" />

        <x-toggle name="is_default" :checked="old('is_default', $currency?->is_default ?? false)"
                  label="Osnovna valuta" :disabled="(bool) $currency?->is_default" />
    </x-section-block>

    <x-form-actions :label="$currency ? 'Sačuvaj izmjene' : 'Dodaj valutu'"
                    :delete="$currency && ! $currency->is_default ? route('currencies.destroy', $currency) : null" />
</form>

@if ($currency && ! $currency->is_default)
    <x-delete-form :action="route('currencies.destroy', $currency)"
                   :confirm="'Obrisati valutu '.$currency->code.'?'" />
@endif

@if ($currency && ! $currency->is_default)
    {{-- Kurs je zaseban obrazac: mijenja se češće od same valute. --}}
    <div class="mt-4">
        <x-section-block variant="card">
            <x-section-header icon="credit-card" title="Kursevi prema KM" :help="route('help').'#valute'" />

            <form method="POST" action="{{ route('currencies.rates.store', $currency) }}" class="space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input label="Kurs" name="rate_to_bam" type="number" step="0.00001" inputmode="decimal" required
                                  :hint="'Koliko KM vrijedi 1 '.$currency->code.'.'" />
                    <x-form-input label="Datum" name="rate_date" type="date" :value="now()->format('Y-m-d')" required />
                </div>

                <x-button variant="ghost" class="w-full !py-3.5">Sačuvaj kurs</x-button>
            </form>

            @if ($rates->isNotEmpty())
                <div class="space-y-1.5">
                    @foreach ($rates as $rate)
                        <div class="flex items-center justify-between px-3 py-2 rounded-xl bg-[var(--color-bg)]/40 text-xs font-bold">
                            <span class="text-[var(--color-text-muted)]">{{ $rate->rate_date->format('d.m.Y.') }}</span>
                            <span class="tabular-nums">{{ number_format($rate->rate_to_bam, 5, ',', '.') }} KM</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-[11px] text-[var(--color-text-dim)] pl-1">
                    Bez kursa se račun u ovoj valuti ne može fiskalizovati.
                </p>
            @endif
        </x-section-block>
    </div>
@endif
