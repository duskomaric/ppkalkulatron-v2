@extends('layouts.app')

@section('title', 'ppKalkulatron')

@section('content')
    <nav>
        <a href="{{ route('home') }}">Početna</a>
        <a href="{{ route('settings.pin.edit') }}">PIN</a>
        @if ($pinEnabled)
            <form method="POST" action="{{ route('unlock.destroy') }}" style="margin-left:auto">
                @csrf
                <button type="submit" class="ghost" style="padding:4px 12px;font-size:13px;width:auto">Zaključaj</button>
            </form>
        @endif
    </nav>

    <h1>ppKalkulatron v2</h1>
    <p class="sub">Laravel {{ app()->version() }} · NativePHP</p>

    <div class="card">
        <h2>Zaključavanje</h2>
        <p>
            {{ $pinEnabled
                ? 'PIN je podešen — traži se pri svakom pokretanju.'
                : 'PIN nije podešen. Aplikacija se otvara bez zaključavanja.' }}
        </p>
        <a href="{{ route('settings.pin.edit') }}">
            <button type="button" class="ghost">{{ $pinEnabled ? 'Promijeni PIN' : 'Postavi PIN' }}</button>
        </a>
    </div>
@endsection
