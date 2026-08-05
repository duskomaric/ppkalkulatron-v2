@extends('layouts.app')
@section('title', 'PIN')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    {{-- Isti raspored kao ostala podešavanja: kartica po sekciji, x-form-* polja. --}}
    <div class="space-y-8 animate-fade-in">
        <form method="POST" action="{{ route('settings.pin.update') }}">
            @csrf
            @method('PUT')

            <x-section-block variant="card">
                <x-section-header icon="lock" :title="$enabled ? 'Promijeni PIN' : 'Postavi PIN'"
                                  :help="route('help').'#pin'" />

                <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                    {{ $enabled
                        ? 'PIN se traži pri svakom pokretanju aplikacije i poslije zaključavanja.'
                        : 'PIN je opcionalan. Kad ga postavite, traži se pri pokretanju aplikacije.' }}
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-form-input label="PIN" name="pin" type="password" inputmode="numeric" maxlength="4"
                                  autocomplete="off" required hint="Četiri cifre." />
                    <x-form-input label="Ponovi PIN" name="pin_confirmation" type="password" inputmode="numeric"
                                  maxlength="4" autocomplete="off" required />
                </div>

                <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                    {{ $enabled ? 'Promijeni PIN' : 'Postavi PIN' }}
                </x-button>
            </x-section-block>
        </form>

        @if ($enabled)
            <form method="POST" action="{{ route('settings.pin.update-lock') }}">
                @csrf
                @method('PUT')

                <x-section-block variant="card">
                    <x-section-header icon="clock" title="Automatsko zaključavanje" :help="route('help').'#pin'" />

                    <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                        Aplikacija se sama zaključava kad se ostavi otvorena, i kad se telefon zaključa
                        pa vrati. Nula znači bez automatskog zaključavanja.
                    </p>

                    <x-form-select label="Zaključaj poslije" name="auto_lock_minutes" :value="$autoLockMinutes" required
                                   :options="[
                                       0 => 'Nikad',
                                       1 => '1 minut',
                                       5 => '5 minuta',
                                       15 => '15 minuta',
                                       30 => '30 minuta',
                                       60 => '1 sat',
                                   ]" />

                    <x-button variant="primary" class="w-full !py-3.5 !text-[11px] !font-black !uppercase !tracking-[0.2em]">
                        Sačuvaj izmjene
                    </x-button>
                </x-section-block>
            </form>

            <form method="POST" action="{{ route('settings.pin.destroy') }}" data-confirm="Ukloniti PIN?">
                @csrf
                @method('DELETE')

                <x-section-block variant="card">
                    <x-section-header icon="lock" title="Ukloni PIN" :help="route('help').'#pin'" />

                    <p class="text-[11px] text-[var(--color-text-dim)] pl-1 leading-relaxed">
                        Aplikacija se nakon ovoga otvara bez PIN-a.
                    </p>

                    <x-button variant="danger" class="w-full !py-3.5">Ukloni PIN</x-button>
                </x-section-block>
            </form>
        @endif
    </div>
@endsection
