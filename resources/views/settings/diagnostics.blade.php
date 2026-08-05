@extends('layouts.app')
@section('title', 'Dijagnostika')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <div class="space-y-8 animate-fade-in">
        <x-section-block variant="card">
            <x-section-header icon="activity" title="Sigurna dijagnostika" subtitle="Pomoć pri rješavanju tehničkih problema." :help="route('help').'#dijagnostika'" />

            <div class="rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4">
                <div class="flex gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"><x-icon name="shield-check" class="h-5 w-5" /></span>
                    <div class="space-y-1 text-sm text-[var(--color-text-muted)]">
                        <p class="font-bold text-[var(--color-text-main)]">Računi i privatni podaci se ne šalju.</p>
                        <p>Izvještaj nikada ne sadrži PDF račune, fiskalne dokumente, podatke kupaca, API ključ, PAK, PIN ni SMTP lozinku.</p>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.diagnostics.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form-input label="Email za dijagnostiku" name="email" type="email" :value="$settings->email"
                              hint="Na ovu adresu se šalje samo sigurni tehnički izvještaj. Mail server se podešava u Podešavanja → Mail." />

                <x-toggle name="detailed_logging" :checked="$settings->detailedLoggingEnabled()" label="Uključi detaljnu dijagnostiku na 24 sata">
                    <x-icon name="bug" class="h-4 w-4 text-primary" />
                    <span>Detaljni tehnički događaji</span>
                </x-toggle>

                @if ($settings->detailedLoggingEnabled())
                    <p class="text-xs text-[var(--color-text-dim)]">Aktivna do {{ $settings->detailed_until->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}. Nakon toga se automatski isključuje; greške ostaju evidentirane.</p>
                @else
                    <p class="text-xs text-[var(--color-text-dim)]">Greške se bilježe uvijek. Ovu opciju uključite samo dok ponavljate problem za podršku.</p>
                @endif

                <x-button variant="ghost" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">Sačuvaj dijagnostiku</x-button>
            </form>
        </x-section-block>

        <x-section-block variant="card" class="space-y-5">
            <x-section-header icon="send" title="Pošalji dijagnostiku" subtitle="Jedan sigurni tekstualni prilog sa zadnjih najviše sedam dana." :help="route('help').'#dijagnostika'" />

            @if ($settings->last_sent_at)
                <p class="text-xs text-[var(--color-text-dim)]">Posljednje slanje: {{ $settings->last_sent_at->timezone(config('app.timezone'))->format('d.m.Y. H:i') }}.</p>
            @endif

            <form method="POST" action="{{ route('settings.diagnostics.send') }}" x-data="{ sending: false }" x-on:submit="sending = true">
                @csrf
                <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black disabled:opacity-50" :disabled="blank($settings->email)" x-bind:disabled="sending" x-bind:aria-busy="sending">
                    <span x-show="sending" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <x-icon name="send" class="h-4 w-4" x-show="! sending" />
                    <span x-text="sending ? 'Šaljem dijagnostiku...' : 'Pošalji sigurni log'">Pošalji sigurni log</span>
                </x-button>
            </form>
        </x-section-block>
    </div>
@endsection
