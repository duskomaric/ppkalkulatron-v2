@extends('layouts.app')
@section('title', $article ? 'Izmjena artikla' : 'Novi artikl')

@section('content')
    <a href="{{ route('articles.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[var(--color-text-dim)] hover:text-primary transition-colors mb-5">
        <x-icon name="arrow-left" class="h-4 w-4" /> Nazad
    </a>

    <form method="POST" action="{{ $article ? route('articles.update', $article) : route('articles.store') }}" class="space-y-5 max-w-3xl">
        @csrf
        @if ($article) @method('PUT') @endif

        <x-section title="Artikl" icon="boxes">
            <x-field label="Naziv" name="name" :value="$article?->name" required />
            <x-field label="Opis" name="description" rows="3" :value="$article?->description" />

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-field label="Jedinica mjere" name="unit" :value="$article?->unit->value ?? 'kom'"
                         :options="\App\Enums\Unit::options()" required />

                <x-field label="Poreska oznaka" name="tax_label" :value="$article?->tax_label"
                         :options="['' => '—'] + collect(config('ofs.tax_labels'))->mapWithKeys(fn ($rate, $label) => [$label => $label.' — '.$rate / 100 .'%'])->all()"
                         hint="Uređaj javlja koje oznake priznaje." />

                <x-field label="Cijena" name="last_unit_price" type="number" step="0.01"
                         :value="$article?->last_unit_price ? number_format($article->last_unit_price / 100, 2, '.', '') : null"
                         hint="Ponudi se sama pri dodavanju na račun." />

                <x-field label="GTIN" name="gtin" :value="$article?->gtin"
                         hint="Barkod, 8 do 14 cifara. Fiskalni uređaj ga traži." />
            </div>
        </x-section>

        <label class="flex items-center gap-3 px-1">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $article?->is_active ?? true))
                   class="h-5 w-5 rounded-md accent-[var(--color-primary)]">
            <span class="text-xs font-black uppercase tracking-widest text-[var(--color-text-muted)]">Artikl je aktivan</span>
        </label>

        <div class="flex gap-3">
            <x-button variant="primary" class="grow">{{ $article ? 'Sačuvaj izmjene' : 'Kreiraj artikl' }}</x-button>
            @if ($article)
                <button type="submit" form="delete-article" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold border border-[var(--color-error)]/40 text-[var(--color-error)] hover:bg-[var(--color-error)]/10 transition-all cursor-pointer">
                    <x-icon name="trash" class="h-4 w-4" />
                </button>
            @endif
        </div>
    </form>

    @if ($article)
        <form id="delete-article" method="POST" action="{{ route('articles.destroy', $article) }}" class="hidden"
              onsubmit="return confirm('Obrisati artikl {{ $article->name }}?')">
            @csrf @method('DELETE')
        </form>
    @endif
@endsection
