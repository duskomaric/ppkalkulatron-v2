<form method="POST" action="{{ $article ? route('articles.update', $article) : route('articles.store') }}"
      class="space-y-4">
    @csrf
    @if ($article) @method('PUT') @endif

    <x-form-errors />

    <x-section-block variant="card">
        <x-section-header icon="boxes" title="Osnovni podaci" :help="route('help').'#artikli'" />

        <x-form-input label="Naziv" name="name" :value="$article?->name" required placeholder="npr. Web razvoj" />
        <x-form-textarea label="Opis" name="description" rows="2" :value="$article?->description"
                         placeholder="Kratki opis artikla..." />

        <x-form-select label="Jedinica mjere" name="unit" :value="$article?->unit->value ?? 'kom'"
                       :options="\App\Enums\Unit::options()" required />
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="currency-euro" title="Cijena" :help="route('help').'#artikli'" />

        <x-form-input label="Cijena" name="last_unit_price" type="number" step="0.01"
                      :value="$article?->last_unit_price ? number_format($article->last_unit_price / 100, 2, '.', '') : null"
                      hint="Sa porezom. Ponudi se sama pri dodavanju na račun." />
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="hash" title="Porez i barkod" :help="route('help').'#artikli'" />

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @if ($taxRateOptions === [])
                <div class="rounded-xl border border-amber-500/30 bg-amber-500/10 px-4 py-3 sm:col-span-2">
                    <p class="text-sm font-black text-amber-700 dark:text-amber-300">Poreske stope nisu preuzete</p>
                    <p class="mt-1 text-xs text-[var(--color-text-dim)]">Prvo ih ručno preuzmite sa fiskalne kase, zatim možete odabrati oznaku za artikal.</p>
                    <a href="{{ route('settings.fiscal.edit') }}" class="mt-3 inline-flex text-xs font-black text-primary hover:underline">Otvori fiskalizaciju</a>
                </div>
            @else
                <x-form-select label="Poreska oznaka" name="tax_label" :value="$article?->tax_label"
                               :options="$taxRateOptions"
                               required
                               hint="Preuzeta direktno sa trenutno dostupne fiskalne kase." />
            @endif

            <x-form-input label="GTIN" name="gtin" :value="$article?->gtin"
                          hint="Barkod, 8 do 14 cifara." />
        </div>
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="check" title="Status" :help="route('help').'#artikli'" />

        <x-toggle name="is_active" :checked="old('is_active', $article?->is_active ?? true)" label="Artikl je aktivan" />
    </x-section-block>

    @if ($taxRateOptions !== [])
        <x-form-actions :label="$article ? 'Sačuvaj izmjene' : 'Kreiraj artikl'"
                        :delete="$article ? route('articles.destroy', $article) : null" />
    @endif
</form>

@if ($article)
    <x-delete-form :action="route('articles.destroy', $article)" :confirm="'Obrisati artikl '.$article->name.'?'" />
@endif
