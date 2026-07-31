@extends('layouts.app')

@section('title', 'Novi račun')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    @include('invoices.form')
@endsection
