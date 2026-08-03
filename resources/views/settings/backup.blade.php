@extends('layouts.app')
@section('title', 'Backup')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <div class="space-y-8 animate-fade-in">
        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="archive" title="Email backup" subtitle="ZIP sadrži PDF račune, originalne fiskalne dokumente i manifest.csv." :help="route('help').'#backup'" />

            <form method="POST" action="{{ route('settings.backup.update') }}" class="space-y-5">
                @csrf
                @method('PUT')

                <x-form-input label="Email za backup" name="email" type="email" :value="$settings->email"
                              hint="Mail server mora biti podešen u Podešavanja → Mail." required />

                <x-button variant="ghost" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                    Sačuvaj email
                </x-button>
            </form>
        </x-section-block>

        <x-section-block variant="card" class="sm:p-8 space-y-6">
            <x-section-header icon="archive" title="Pošalji backup" subtitle="Odaberite jedan ZIP ili pojedinačne dokumente u emailu." :help="route('help').'#backup'" />

            @if ($settings->last_backup_at)
                <div class="flex items-center gap-3 border-b border-[var(--color-border)] pb-5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"><x-icon name="check" class="h-4 w-4" /></span>
                    <div class="min-w-0">
                        <p class="text-sm font-bold text-[var(--color-text-main)]">Posljednji backup je uspješno poslat</p>
                        <p class="mt-0.5 text-xs text-[var(--color-text-dim)]">{{ $settings->last_backup_at->timezone(config('app.timezone'))->format('d.m.Y.') }} u {{ $settings->last_backup_at->timezone(config('app.timezone'))->format('H:i') }} · {{ $settings->last_backup_invoice_count }} računa · {{ $settings->last_backup_fiscal_document_count }} fiskalnih dokumenata</p>
                    </div>
                </div>
            @else
                <x-empty-state icon="archive" title="Backup još nije poslat" description="Nakon slanja ovdje ostaje datum posljednjeg uspješnog backupa." />
            @endif

            <form method="POST" action="{{ route('settings.backup.send') }}" class="space-y-6" x-data="{ sending: false, format: @js($zipAvailable ? 'zip' : 'raw') }"
                  x-on:submit="sending = true">
                @csrf
                <fieldset class="grid gap-2 sm:grid-cols-2">
                    <legend class="mb-2 flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-[var(--color-text-dim)]"><x-icon name="mail" class="h-3.5 w-3.5" /> Format slanja</legend>
                    <label class="flex items-start gap-3 rounded-xl border p-3.5 transition-colors" :class="format === 'zip' ? 'border-primary bg-primary/5' : 'border-[var(--color-border)]'" @class(['cursor-pointer' => $zipAvailable, 'cursor-not-allowed opacity-50' => ! $zipAvailable])>
                        <input type="radio" name="delivery_format" value="zip" x-model="format" class="mt-0.5 accent-[var(--color-primary)]" @disabled(! $zipAvailable)>
                        <x-icon name="archive" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-[var(--color-text-main)]">ZIP arhiva</span>
                            <span class="mt-0.5 block text-xs text-[var(--color-text-dim)]">{{ $zipAvailable ? 'Preporučeno: svi dokumenti u jednom prilogu.' : 'ZIP nije dostupan u ovom Android buildu.' }}</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3.5 transition-colors" :class="format === 'raw' ? 'border-primary bg-primary/5' : 'border-[var(--color-border)]'">
                        <input type="radio" name="delivery_format" value="raw" x-model="format" class="mt-0.5 accent-[var(--color-primary)]">
                        <x-icon name="file-text" class="mt-0.5 h-4 w-4 shrink-0 text-primary" />
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-[var(--color-text-main)]">Pojedinačni fajlovi</span>
                            <span class="mt-0.5 block text-xs text-[var(--color-text-dim)]">PDF-ovi i fiskalni dokumenti kao odvojeni prilozi.</span>
                        </span>
                    </label>
                </fieldset>
                <fieldset>
                    <legend class="mb-2 flex items-center gap-2 text-[11px] font-black uppercase tracking-wider text-[var(--color-text-dim)]"><x-icon name="file-check" class="h-3.5 w-3.5" /> Sadržaj backupa</legend>
                    <div class="grid gap-2 sm:grid-cols-3">
                        <x-toggle name="include_invoices" checked>
                            <x-icon name="file-text" class="h-4 w-4 text-primary" />
                            <span>PDF računi</span>
                        </x-toggle>
                        <x-toggle name="include_fiscal_documents" checked>
                            <x-icon name="printer" class="h-4 w-4 text-primary" />
                            <span>Fiskalni dokumenti</span>
                        </x-toggle>
                        <x-toggle name="include_manifest" checked>
                            <x-icon name="file-sliders" class="h-4 w-4 text-primary" />
                            <span>CSV pregled</span>
                        </x-toggle>
                    </div>
                    @error('include_invoices')<p class="mt-2 text-xs font-bold text-[var(--color-error)]">{{ $message }}</p>@enderror
                </fieldset>
                <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black disabled:opacity-50"
                          :disabled="blank($settings->email)" x-bind:disabled="sending"
                          x-bind:aria-busy="sending">
                    <span x-show="sending" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <x-icon name="archive" class="h-4 w-4" x-show="! sending" />
                    <span x-text="sending ? 'Pripremam backup...' : 'Napravi i pošalji backup'">Napravi i pošalji backup</span>
                </x-button>
                <p x-cloak x-show="sending" role="status" class="mt-3 text-center text-xs text-[var(--color-text-dim)]">
                    Pripremam dokumente i šaljem ih na email. Ne zatvarajte aplikaciju.
                </p>
            </form>
        </x-section-block>
    </div>
@endsection
