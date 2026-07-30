@extends('layouts.app')
@section('title', 'Računi')
@section('actions')<x-create-button :href="route('invoices.create')" label="Novi račun" />@endsection

@section('content')
    <x-search-bar :value="$q" placeholder="Pretraga po broju ili klijentu…" />
    <x-invoices.list :invoices="$invoices" />
@endsection
