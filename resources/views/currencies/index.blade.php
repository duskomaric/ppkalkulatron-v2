@extends('layouts.app')
@section('title', 'Valute')

@section('actions')
    <x-create-button label="Nova valuta" :href="route('currencies.create')" />
@endsection

@section('content')
    <div>
        <div>
            @if ($currencies->isEmpty())
                <x-empty-state icon="hash" title="Nema valuta"
                               :action="route('currencies.create')" action-label="Dodaj valutu" />
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 animate-fade-in">
                @foreach ($currencies as $currency)
                    {{-- Cijela kartica je meta za dodir; olovka od 32px je premala na telefonu. --}}
                    <a href="{{ route('currencies.edit', $currency) }}"
                       class="block bg-[var(--color-surface)] border border-[var(--color-border)] p-5 rounded-2xl relative group shadow-sm hover:shadow-md hover:border-primary/40 transition-all">
                        <div class="flex justify-between items-center gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="h-10 min-w-10 px-2 bg-[var(--color-surface-hover)] rounded-full flex items-center justify-center text-primary font-black text-sm shrink-0">
                                    {{ $currency->symbol }}
                                </div>
                                <span class="font-bold text-[var(--color-text-main)] text-lg">{{ $currency->code }}</span>
                            </div>

                            @if ($currency->is_default)
                                <span class="shrink-0" title="Podrazumijevana valuta">
                                    <x-icon name="star" class="h-5 w-5 text-primary" />
                                    <span class="sr-only">Podrazumijevana valuta</span>
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-[var(--color-text-dim)] font-medium pl-1">{{ $currency->name }}</p>

                        @unless ($currency->is_default)
                            <p class="text-xs font-bold mt-3 pt-3 border-t border-[var(--color-border)] pl-1
                                      {{ isset($rates[$currency->code]) ? 'text-[var(--color-text-muted)]' : 'text-amber-500' }}">
                                @isset($rates[$currency->code])
                                    Kurs: {{ number_format($rates[$currency->code]->rate_to_bam, 5, ',', '.') }} KM
                                @else
                                    Bez kursa — ne može se fiskalizovati
                                @endisset
                            </p>
                        @endunless
                    </a>
                @endforeach
            </div>
            @endif
        </div>

    </div>
@endsection
