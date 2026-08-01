@extends('layouts.app')
@section('title', 'Artikli')

@section('actions')
    <x-create-button label="Novi artikl" :href="route('articles.create')" />
@endsection

@section('content')
    <div>
        @if ($taxRates === [])
            <x-section-block variant="accent" class="mb-4" x-data="{ fiscalState: @js($fiscalHealth['state']) }"
                             @fiscal-health-updated="fiscalState = $event.detail.state">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <x-fiscal-health-indicator :health="$fiscalHealth" :url="route('settings.fiscal.status', [], false)" />
                        <div>
                            <p class="text-sm font-black text-[var(--color-text-main)]">Poreske stope nisu preuzete</p>
                            <p class="mt-1 text-xs text-[var(--color-text-dim)]">Artikli se mogu dodati tek kada se preuzmu stope sa dostupne fiskalne kase.</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('settings.fiscal.tax-rates.sync') }}">
                        @csrf
                        <input type="hidden" name="return_to" value="articles">
                        <x-button variant="primary" class="w-full sm:w-auto disabled:cursor-not-allowed disabled:opacity-50 disabled:shadow-none"
                                  x-bind:disabled="fiscalState !== 'ready'"
                                  x-bind:title="fiscalState === 'ready' ? '' : 'Preuzimanje je dostupno kada je kasa povezana.'">
                            Preuzmi stope sa kase
                        </x-button>
                    </form>
                </div>
            </x-section-block>
        @endif

        <x-search-bar :value="$q" placeholder="Pretraži artikle…" />

        <div>
            @if ($articles->isEmpty())
                <x-empty-state icon="x" title="Nema pronađenih artikala"
                               :action="route('articles.create')" action-label="Dodaj artikl" />
            @else
                <x-list-header grid="grid-cols-[minmax(0,1.4fr)_0.6fr_0.6fr_0.7fr_1fr]" :columns="[
                    ['label' => 'Artikl'], ['label' => 'Status'], ['label' => 'JM'],
                    ['label' => 'Porez'], ['label' => 'Cijena', 'align' => 'right'],
                ]" />

                <div class="space-y-4 md:space-y-3">
                    @foreach ($articles as $article)
                        @php
                            $status = ['label' => $article->is_active ? 'Aktivan' : 'Neaktivan', 'color' => $article->is_active ? 'green' : 'gray'];
                            $rate = $article->tax_label ? ($taxRates[$article->tax_label] ?? null) : null;
                            $tax = $article->tax_label ? $article->tax_label.' ('.$rate.'%)' : '—';
                            $price = $article->last_unit_price ? number_format($article->last_unit_price / 100, 2, ',', '.') : null;
                        @endphp

                        <x-responsive-entity-card :href="route('articles.edit', $article)">
                            <x-slot:mobile>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <x-icon name="boxes" class="w-4 h-4 shrink-0 text-primary" />
                                        <div class="flex flex-col min-w-0">
                                            <span class="text-base font-black tracking-tighter italic leading-tight truncate group-hover:text-primary transition-colors">
                                                {{ $article->name }}
                                            </span>
                                            @if ($article->description)
                                                <p class="text-[10px] font-bold text-[var(--color-text-dim)] uppercase tracking-widest truncate">
                                                    {{ $article->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <x-status-badge :label="$status['label']" :color="$status['color']" />
                                </div>

                                <div class="h-[1px] w-full my-1 bg-[var(--color-border)]"></div>

                                <div class="flex justify-between items-end gap-3">
                                    <div class="flex gap-4">
                                        <x-meta-item icon="hash" label="JM" :value="$article->unit->label()" />
                                        <x-meta-item icon="hash" label="Porez" :value="$tax" />
                                    </div>

                                    <x-meta-item icon="currency-euro" label="Cijena" class="items-end text-right shrink-0">
                                        @if ($price)
                                            <span class="text-sm font-black text-[var(--color-text-main)] tracking-tighter italic leading-none">
                                                {{ $price }}
                                                <span class="text-[10px] opacity-60 not-italic font-bold">{{ $currencySymbol }}</span>
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </x-meta-item>
                                </div>
                            </x-slot:mobile>

                            <x-slot:desktop>
                                <div class="grid grid-cols-[minmax(0,1.4fr)_0.6fr_0.6fr_0.7fr_1fr] gap-3 items-center">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <x-icon name="boxes" class="h-5 w-5 shrink-0 text-primary" />
                                        <div class="min-w-0">
                                            <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">
                                                {{ $article->name }}
                                            </p>
                                            @if ($article->description)
                                                <p class="text-[10px] font-bold text-[var(--color-text-dim)] uppercase tracking-widest truncate">
                                                    {{ $article->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div><x-status-badge :label="$status['label']" :color="$status['color']" /></div>

                                    <div class="text-xs font-bold text-[var(--color-text-muted)]">{{ $article->unit->label() }}</div>
                                    <div class="text-xs font-bold text-[var(--color-text-muted)]">{{ $tax }}</div>

                                    <div class="text-right">
                                        @if ($price)
                                            <p class="text-sm font-black text-[var(--color-text-main)] tracking-tighter italic leading-none">
                                                {{ $price }}
                                                <span class="text-[10px] opacity-60 not-italic font-bold">{{ $currencySymbol }}</span>
                                            </p>
                                        @else
                                            <p class="text-xs font-bold text-[var(--color-text-muted)]">—</p>
                                        @endif
                                    </div>
                                </div>
                            </x-slot:desktop>
                        </x-responsive-entity-card>
                    @endforeach
                </div>

                <div class="mt-6">{{ $articles->links() }}</div>
            @endif
        </div>

    </div>
@endsection
