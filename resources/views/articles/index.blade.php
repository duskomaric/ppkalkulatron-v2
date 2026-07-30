@extends('layouts.app')
@section('title', 'Artikli')
@section('actions')<x-create-button :href="route('articles.create')" label="Novi artikl" />@endsection

@section('content')
    <x-search-bar :value="$q" placeholder="Pretraga po nazivu…" />

    @if ($articles->isEmpty())
        <x-empty-state icon="boxes" title="Nema artikala" :action="route('articles.create')" action-label="Dodaj prvi artikl" />
    @else
        <x-list-header grid="grid-cols-[minmax(0,1.6fr)_0.5fr_0.5fr_0.7fr_0.6fr]" :columns="[
            ['label' => 'Artikl'], ['label' => 'JM'], ['label' => 'Porez'],
            ['label' => 'Zadnja cijena', 'align' => 'right'], ['label' => 'Status'],
        ]" />

        <div class="space-y-3">
            @foreach ($articles as $article)
                <x-entity-card :href="route('articles.edit', $article)">
                    <div class="md:grid md:grid-cols-[minmax(0,1.6fr)_0.5fr_0.5fr_0.7fr_0.6fr] md:gap-3 md:items-center flex flex-col gap-2">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <x-icon name="boxes" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $article->name }}</p>
                                @if ($article->description)
                                    <p class="text-[11px] font-bold text-[var(--color-text-dim)] truncate whitespace-pre-line">{{ $article->description }}</p>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $article->unit->label() }}</span>
                        <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $article->tax_label ?: '—' }}</span>
                        <span class="text-sm font-black italic tracking-tight md:text-right">
                            {{ $article->last_unit_price ? number_format($article->last_unit_price / 100, 2, ',', '.').' KM' : '—' }}
                        </span>
                        <div><x-status-badge :label="$article->is_active ? 'Aktivan' : 'Neaktivan'" :color="$article->is_active ? 'green' : 'gray'" /></div>
                    </div>
                </x-entity-card>
            @endforeach
        </div>

        <div class="mt-6">{{ $articles->links() }}</div>
    @endif
@endsection
