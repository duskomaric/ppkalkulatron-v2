@extends('layouts.app')

@section('title', 'Otključaj')
@section('body-class', 'center')

@section('content')
    <h1>ppKalkulatron</h1>
    <p class="sub">Unesite PIN za nastavak.</p>

    <form method="POST" action="{{ route('unlock.store') }}">
        @csrf

        <label for="pin">PIN</label>
        <input
            id="pin"
            name="pin"
            type="password"
            class="pin"
            inputmode="numeric"
            autocomplete="off"
            autofocus
            maxlength="8"
            placeholder="••••"
            @disabled($lockedForSeconds > 0)
        >

        @error('pin')<p class="err">{{ $message }}</p>@enderror

        @if ($lockedForSeconds > 0)
            <p class="err">Zaključano još {{ $lockedForSeconds }} s.</p>
        @elseif ($attemptsLeft < \App\Services\PinLock::MAX_ATTEMPTS)
            <p class="err">Preostalo pokušaja: {{ $attemptsLeft }}.</p>
        @endif

        <button type="submit" @disabled($lockedForSeconds > 0)>Otključaj</button>
    </form>
@endsection
