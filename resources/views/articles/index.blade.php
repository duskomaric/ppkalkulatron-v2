@extends('layouts.app')
@section('title', 'Artikli')

@section('actions')
    <x-create-button label="Novi artikl" x-on:click="$dispatch('open-entity-form')" />
@endsection

@section('content')
    <div x-data="entityIndex()"
         x-on:open-entity-form.window="openForm({{ \App\Support\Js::from(route('articles.create', ['partial' => 1])) }}, 'Novi artikl')">
        <x-search-bar :value="$q" placeholder="Pretraga po nazivu…" />

        <div data-entity-list>
            @if ($articles->isEmpty())
                <x-empty-state icon="boxes" title="Nema pronađenih artikala" />
            @else
                {{-- Kolone kao u v1: artikl, status, JM, porez, cijena. --}}
                <x-list-header grid="grid-cols-[minmax(0,1.4fr)_0.6fr_0.6fr_0.7fr_1fr]" :columns="[
                    ['label' => 'Artikl'], ['label' => 'Status'], ['label' => 'JM'],
                    ['label' => 'Porez'], ['label' => 'Cijena', 'align' => 'right'],
                ]" />

                <div class="space-y-3">
                    @foreach ($articles as $article)
                        <x-entity-card :href="route('articles.edit', $article)"
                                       :x-on:click.prevent="\App\Support\Js::call('openForm', route('articles.edit', [$article, 'partial' => 1]), 'Izmjena artikla')">
                            <div class="md:grid md:grid-cols-[minmax(0,1.4fr)_0.6fr_0.6fr_0.7fr_1fr] md:gap-3 md:items-center flex flex-col gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <x-icon name="boxes" class="h-5 w-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $article->name }}</p>
                                        @if ($article->description)
                                            <p class="text-[11px] font-bold text-[var(--color-text-dim)] truncate">{{ $article->description }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <x-status-badge :label="$article->is_active ? 'Aktivan' : 'Neaktivan'"
                                                    :color="$article->is_active ? 'green' : 'gray'" />
                                </div>

                                <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $article->unit->label() }}</span>

                                <span class="text-xs font-bold text-[var(--color-text-muted)]">
                                    {{ $article->tax_label ? 'PDV '.$article->tax_label : '—' }}
                                </span>

                                <p class="md:text-right text-base font-black tracking-tighter italic">
                                    {{ $article->last_unit_price
                                        ? number_format($article->last_unit_price / 100, 2, ',', '.').' BAM'
                                        : '—' }}
                                </p>
                            </div>
                        </x-entity-card>
                    @endforeach
                </div>

                <div class="mt-6">{{ $articles->links() }}</div>
            @endif
        </div>

        <x-entity-form-drawer />
    </div>
@endsection
