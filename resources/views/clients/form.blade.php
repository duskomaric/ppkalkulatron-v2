@extends('layouts.app')
@section('title', $client ? 'Izmjena klijenta' : 'Novi klijent')

@section('content')
    <x-back-link :href="route('clients.index')" />

    <div class="max-w-3xl">
        @include('clients.form-fields')
    </div>
@endsection
