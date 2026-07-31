@extends('layouts.app')
@section('title', 'Valute')

@section('actions')
    <x-create-button label="Nova valuta" x-on:click="$dispatch('open-entity-form')" />
@endsection

@section('content')
    <div x-data="entityIndex()"
         x-on:open-entity-form.window="openForm({{ \App\Support\Js::from(route('currencies.create', ['partial' => 1])) }}, 'Nova valuta')">
        <div data-entity-list>
            <x-list-header grid="grid-cols-[0.6fr_minmax(0,1.3fr)_0.5fr_0.6fr_0.9fr]" :columns="[
                ['label' => 'Oznaka'], ['label' => 'Naziv'], ['label' => 'Simbol'],
                ['label' => 'Status'], ['label' => 'Kurs (KM)', 'align' => 'right'],
            ]" />

            <div class="space-y-3">
                @foreach ($currencies as $currency)
                    <x-entity-card :href="route('currencies.edit', $currency)"
                                   :x-on:click.prevent="\App\Support\Js::call('openForm', route('currencies.edit', [$currency, 'partial' => 1]), 'Izmjena valute')">
                        <div class="md:grid md:grid-cols-[0.6fr_minmax(0,1.3fr)_0.5fr_0.6fr_0.9fr] md:gap-3 md:items-center flex flex-col gap-2">
                            <div class="flex items-center gap-3 min-w-0">
                                <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-[11px] font-black">
                                    {{ $currency->code }}
                                </span>
                            </div>

                            <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">
                                {{ $currency->name }}
                            </p>

                            <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $currency->symbol }}</span>

                            <div>
                                @if ($currency->is_default)
                                    <x-status-badge label="Osnovna" color="blue" />
                                @else
                                    <x-status-badge :label="isset($rates[$currency->code]) ? 'Ima kurs' : 'Bez kursa'"
                                                    :color="isset($rates[$currency->code]) ? 'green' : 'amber'" />
                                @endif
                            </div>

                            <span class="text-xs font-bold text-[var(--color-text-muted)] tabular-nums md:text-right">
                                @if ($currency->is_default)
                                    1,00000
                                @else
                                    {{ isset($rates[$currency->code])
                                        ? number_format($rates[$currency->code]->rate_to_bam, 5, ',', '.')
                                        : '—' }}
                                @endif
                            </span>
                        </div>
                    </x-entity-card>
                @endforeach
            </div>
        </div>

        <p class="mt-5 px-1 text-[11px] font-bold text-[var(--color-text-dim)]">
            Kursevi se koriste za preračun stranih valuta u konvertibilnu marku pri fiskalizaciji.
        </p>

        <x-entity-form-drawer />
    </div>
@endsection
