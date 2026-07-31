@extends('layouts.app')
@section('title', 'Valute')
@section('actions')<x-create-button :href="route('currencies.create')" label="Nova valuta" />@endsection

@section('content')
    <x-list-header grid="grid-cols-[0.5fr_minmax(0,1.4fr)_0.5fr_0.9fr]" :columns="[
        ['label' => 'Oznaka'], ['label' => 'Naziv'], ['label' => 'Simbol'], ['label' => 'Kurs (KM)'],
    ]" />

    <div class="space-y-3">
        @foreach ($currencies as $currency)
            <x-entity-card :href="route('currencies.edit', $currency)">
                <div class="md:grid md:grid-cols-[0.5fr_minmax(0,1.4fr)_0.5fr_0.9fr] md:gap-3 md:items-center flex flex-col gap-2">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 text-[11px] font-black">
                            {{ $currency->code }}
                        </span>
                        @if ($currency->is_default)
                            <x-status-badge label="Osnovna" color="blue" class="md:hidden" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $currency->name }}</p>
                        @if ($currency->is_default)
                            <p class="text-[11px] font-bold text-primary hidden md:block">Osnovna valuta</p>
                        @endif
                    </div>
                    <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $currency->symbol }}</span>
                    <span class="text-xs font-bold text-[var(--color-text-muted)] tabular-nums">
                        @if ($currency->is_default)
                            1,00000
                        @else
                            {{ isset($rates[$currency->code]) ? number_format($rates[$currency->code]->rate_to_bam, 5, ',', '.') : '—' }}
                        @endif
                    </span>
                </div>
            </x-entity-card>
        @endforeach
    </div>

    <p class="mt-5 px-1 text-[11px] font-bold text-[var(--color-text-dim)]">
        Kursevi se koriste za preračun stranih valuta u konvertibilnu marku na dokumentima.
    </p>
@endsection
