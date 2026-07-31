@extends('layouts.app')

@section('title', 'Izmjena računa')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('content')
    <x-back-link :href="route('invoices.show', $invoice)" />

    @include('invoices.form')
@endsection
