@extends('layouts.app')
@section('title', 'Backup aplikacije')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <div class="space-y-8 animate-fade-in">
        <x-section-block variant="card">
            <x-section-header icon="archive" title="Napravi backup"
                              subtitle="Puna kopija: računi, klijenti, artikli, podešavanja i fiskalni dokumenti."
                              :help="route('help').'#backup-aplikacije'" />

            <x-note>
                Backup sadrži i pristupne podatke: ključ i PIN fiskalne kase te lozinku mail servera.
                Čuvajte ga kao što biste čuvali te podatke.
            </x-note>

            <form method="GET" action="{{ route('settings.database.download') }}" x-data="databaseBackup" x-on:submit="start()">
                <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black"
                          x-bind:disabled="preparing" x-bind:aria-busy="preparing">
                    <span x-show="preparing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                    <x-icon name="archive" class="h-4 w-4" x-show="! preparing" />
                    <span x-text="preparing ? 'Pripremam backup...' : @js(isMobile() ? 'Napravi i podijeli backup' : 'Napravi i preuzmi backup')">Napravi backup</span>
                </x-button>
            </form>

            <p class="text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                Za dokumente koje šaljete knjigovođi koristite
                <a href="{{ route('settings.backup.edit') }}" class="font-bold text-primary hover:underline">arhivu na email</a> —
                ona nosi PDF-ove i fiskalne dokumente, ali se iz nje aplikacija ne vraća.
            </p>
        </x-section-block>

        <x-section-block variant="card">
            <x-section-header icon="repeat" title="Vraćanje iz backupa"
                              subtitle="Podaci u aplikaciji se zamjenjuju onima iz backupa." :help="route('help').'#backup-aplikacije'" />

            @if (\App\Services\DatabaseBackup::restoreAvailable())
                <p class="text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                    Odaberite backup napravljen u ovoj aplikaciji. Zatečeno stanje se prije zamjene sačuva na uređaju,
                    a nakon vraćanja se prijavljujete PIN-om iz vraćenog backupa. Veliki backup se šalje u dijelovima,
                    pa ne zatvarajte aplikaciju dok prenos traje.
                </p>

                <div class="space-y-3" x-data="databaseRestore({ url: @js(route('settings.database.restore')), chunkBytes: {{ \App\Services\DatabaseBackup::uploadChunkBytes() }} })">
                    <input type="file" accept=".zip,application/zip" x-ref="archive" x-on:change="error = ''"
                           x-bind:disabled="sending"
                           class="w-full cursor-pointer rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-3 text-sm font-bold text-[var(--color-text-main)] file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-1.5 file:text-xs file:font-black file:text-primary">

                    <p x-cloak x-show="error" x-text="error" class="text-xs font-bold text-[var(--color-error)]"></p>

                    <div x-cloak x-show="sending" role="status" class="space-y-1.5">
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-[var(--color-surface-hover)]">
                            <div class="h-full rounded-full bg-primary transition-all" x-bind:style="`width: ${progress}%`"></div>
                        </div>
                        <p class="text-center text-xs text-[var(--color-text-dim)]"
                           x-text="restoring ? 'Vraćam podatke iz backupa...' : `Šaljem backup... ${progress}%`"></p>
                    </div>

                    <x-button variant="ghost" type="button" x-on:click="confirm()" x-bind:disabled="sending"
                              class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black disabled:opacity-50">
                        Vrati podatke iz backupa
                    </x-button>
                </div>
            @else
                <x-note>
                    Ovaj uređaj ne podržava raspakivanje backupa, pa se vraćanje ovdje ne može uraditi.
                </x-note>
            @endif
        </x-section-block>

        <x-section-block variant="card" class="border-red-500/30">
            <x-section-header icon="trash" title="Reset aplikacije"
                              subtitle="Briše sve podatke i podešavanja — aplikacija kreće kao nova." />

            <p class="text-[11px] leading-relaxed text-[var(--color-text-dim)]">
                Nestaju računi, klijenti, artikli, fiskalni zapisi i dokumenti, podaci kompanije, kase i maila.
                Ovo se ne može poništiti — ako vam podaci mogu zatrebati, prvo napravite backup.
            </p>

            <form method="POST" action="{{ route('settings.database.reset') }}"
                  data-confirm="Obrisati sve podatke i podešavanja? Aplikacija kreće kao nova i povratka nema.">
                @csrf
                <x-button variant="ghost" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black !border-red-500/30 !text-red-500 hover:!bg-red-500 hover:!text-white">
                    <x-icon name="trash" class="h-4 w-4" /> Resetuj aplikaciju
                </x-button>
            </form>
        </x-section-block>

    </div>
@endsection
