@extends('layouts.app')

@section('title', 'Račun')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('content')
    <x-back-link :href="route('invoices.index')" />

    {{-- Alpine je potreban samo za native plugin akcije na detalju računa. --}}
    <div class="max-w-3xl" x-data="invoiceActions()">
        @include('invoices.detail')

        <x-email-modal />
        <x-receipt-modal />
    </div>
@endsection
