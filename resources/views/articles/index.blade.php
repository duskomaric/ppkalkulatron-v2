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
                <x-empty-state icon="x" title="Nema pronađenih artikala" />
            @else
                <x-list-header grid="grid-cols-[minmax(0,1.4fr)_0.6fr_0.6fr_0.7fr_1fr]" :columns="[
                    ['label' => 'Artikl'], ['label' => 'Status'], ['label' => 'JM'],
                    ['label' => 'Porez'], ['label' => 'Cijena', 'align' => 'right'],
                ]" />

                <div class="space-y-4 md:space-y-3">
                    @foreach ($articles as $article)
                        @php
                            $status = ['label' => $article->is_active ? 'Aktivan' : 'Neaktivan', 'color' => $article->is_active ? 'green' : 'gray'];
                            $rate = $article->tax_label ? \App\Models\TaxRate::where('label', $article->tax_label)->value('rate') : null;
                            $tax = $article->tax_label ? $article->tax_label.' ('.$rate.'%)' : '—';
                            $price = $article->last_unit_price ? number_format($article->last_unit_price / 100, 2, ',', '.') : null;
                        @endphp

                        <x-responsive-entity-card :href="route('articles.edit', $article)"
                                                  :x-on:click.prevent="\App\Support\Js::call('openForm', route('articles.edit', [$article, 'partial' => 1]), 'Izmjena artikla')">
                            <x-slot:mobile>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <div class="p-1.5 rounded-lg bg-primary/10 shrink-0">
                                            <x-icon name="boxes" class="w-3.5 h-3.5 text-primary" />
                                        </div>
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

                                <div class="h-[1px] w-full bg-[var(--color-border)]"></div>

                                <div class="flex justify-between items-end gap-3">
                                    <div class="flex gap-4">
                                        <x-meta-item icon="hash" label="JM" :value="$article->unit->label()" />
                                        <x-meta-item icon="hash" label="Porez" :value="$tax" />
                                    </div>

                                    <x-meta-item icon="currency-euro" label="Cijena" class="items-end text-right shrink-0">
                                        @if ($price)
                                            <span class="text-sm font-black text-[var(--color-text-main)] tracking-tighter italic leading-none">
                                                {{ $price }}
                                                <span class="text-[10px] opacity-60 not-italic uppercase font-bold">BAM</span>
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
                                        <div class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                            <x-icon name="boxes" class="h-5 w-5" />
                                        </div>
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
                                                <span class="text-[10px] opacity-60 not-italic uppercase font-bold">BAM</span>
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

        <x-entity-form-drawer />
    </div>
@endsection
