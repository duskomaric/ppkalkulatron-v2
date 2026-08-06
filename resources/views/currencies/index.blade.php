@extends('layouts.app')
@section('title', 'Valute')

@section('actions')
    <x-create-button label="Nova valuta" :href="route('currencies.create')" />
@endsection

@section('content')
    <div>
        {{-- Kursna lista se preuzima sama jednom dnevno; ovdje se vidi šta je zadnje stiglo. --}}
        @if ($rateCheck['state'] !== 'off')
            <div class="mb-4 flex flex-col gap-3 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="text-[13px] font-black text-[var(--color-text-main)]">
                        @if ($rateCheck['rate_date'])
                            Kursna lista Centralne banke BiH sa danom {{ now()->parse($rateCheck['rate_date'])->format('d.m.Y.') }}
                        @else
                            Kursna lista još nije preuzeta
                        @endif
                    </p>
                    <p class="mt-0.5 text-[11px] text-[var(--color-text-dim)]">
                        @if ($rateCheck['state'] === 'unavailable')
                            Posljednji pokušaj preuzimanja nije uspio; računa se sa posljednjim poznatim kursom.
                        @else
                            Preuzima se sama, jednom dnevno, kad se otvore računi.
                        @endif
                    </p>
                </div>

                <form method="POST" action="{{ route('currencies.rates.fetch') }}" class="shrink-0">
                    @csrf
                    <x-button variant="ghost" class="w-full sm:w-auto !py-2.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                        <x-icon name="repeat" class="h-4 w-4" /> Preuzmi kurseve
                    </x-button>
                </form>
            </div>
        @endif

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
                                    Kurs: {{ rtrim(rtrim(number_format($rates[$currency->code]->rate_to_bam, 6, ',', '.'), '0'), ',') }} KM
                                    <span class="font-medium text-[var(--color-text-dim)]">· {{ $rates[$currency->code]->rate_date->format('d.m.Y.') }}</span>
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
