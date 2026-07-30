@extends('layouts.app')

@section('title', 'Podešavanja')
@section('heading', 'PIN')

@section('content')
    <div class="space-y-5 max-w-md">
        <p class="text-sm text-[var(--color-text-muted)]">
            {{ $enabled
                ? 'PIN se traži pri svakom pokretanju aplikacije.'
                : 'PIN je opcionalan. Kad ga postavite, tražit će se pri svakom pokretanju.' }}
        </p>

        <x-section :title="$enabled ? 'Promijeni PIN' : 'Postavi PIN'" icon="lock">
            <form method="POST" action="{{ route('settings.pin.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                <x-field label="PIN" name="pin" type="password" inputmode="numeric" maxlength="8" autocomplete="off"
                         hint="Od 4 do 8 cifara." required />

                <x-field label="Ponovi PIN" name="pin_confirmation" type="password" inputmode="numeric"
                         maxlength="8" autocomplete="off" required />

                <x-button variant="primary" class="w-full">{{ $enabled ? 'Promijeni PIN' : 'Postavi PIN' }}</x-button>
            </form>
        </x-section>

        @if ($enabled)
            <x-section title="Ukloni PIN" icon="lock">
                <p class="text-sm text-[var(--color-text-muted)]">Aplikacija se nakon ovoga otvara bez PIN-a.</p>

                <form method="POST" action="{{ route('settings.pin.destroy') }}"
                      onsubmit="return confirm('Ukloniti PIN?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger" class="w-full">Ukloni PIN</x-button>
                </form>
            </x-section>
        @endif
    </div>
@endsection
