@extends('layouts.app')
@section('title', $currency ? 'Izmjena valute' : 'Nova valuta')

@section('content')
    <x-back-link :href="route('currencies.index')" />

    <div class="max-w-3xl">
        @include('currencies.form-fields')
    </div>
@endsection
