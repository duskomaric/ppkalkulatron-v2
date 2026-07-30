@extends('layouts.app')

@section('title', 'PIN')

@section('content')
    <nav>
        <a href="{{ route('home') }}">Početna</a>
        <a href="{{ route('settings.pin.edit') }}">PIN</a>
    </nav>

    <h1>{{ $enabled ? 'Promijeni PIN' : 'Postavi PIN' }}</h1>
    <p class="sub">
        {{ $enabled
            ? 'PIN se traži pri svakom pokretanju aplikacije.'
            : 'PIN je opcionalan. Kad ga postavite, tražit će se pri svakom pokretanju.' }}
    </p>

    <div class="card">
        <form method="POST" action="{{ route('settings.pin.update') }}">
            @csrf
            @method('PUT')

            @if ($enabled)
                <label for="current_pin">Trenutni PIN</label>
                <input id="current_pin" name="current_pin" type="password" class="pin" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="••••">
                @error('current_pin')<p class="err">{{ $message }}</p>@enderror
            @endif

            <label for="pin">{{ $enabled ? 'Novi PIN' : 'PIN' }}</label>
            <input id="pin" name="pin" type="password" class="pin" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="••••" @if (! $enabled) autofocus @endif>
            @error('pin')<p class="err">{{ $message }}</p>@enderror

            <label for="pin_confirmation">Ponovi PIN</label>
            <input id="pin_confirmation" name="pin_confirmation" type="password" class="pin" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="••••">

            <button type="submit">{{ $enabled ? 'Promijeni PIN' : 'Postavi PIN' }}</button>
        </form>
    </div>

    @if ($enabled)
        <div class="card">
            <h2>Ukloni PIN</h2>
            <p>Aplikacija se nakon ovoga otvara bez zaključavanja.</p>

            <form method="POST" action="{{ route('settings.pin.destroy') }}">
                @csrf
                @method('DELETE')

                <label for="current_pin_remove">Trenutni PIN</label>
                <input id="current_pin_remove" name="current_pin" type="password" class="pin" inputmode="numeric" autocomplete="off" maxlength="8" placeholder="••••">

                <button type="submit" class="danger">Ukloni PIN</button>
            </form>
        </div>
    @endif
@endsection
