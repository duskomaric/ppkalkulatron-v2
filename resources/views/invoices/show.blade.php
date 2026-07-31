@extends('layouts.app')

@section('title', 'Račun')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('content')
    <x-back-link :href="route('invoices.index')" />

    {{--
        `invoiceActions` je isti Alpine opis koji koristi i lista. Bez njega su
        fiskalni dugmići iz partiala detalja samo javljali grešku u konzoli, jer
        `$data.fiscalAction` ovdje ne bi postojao.
    --}}
    <div class="max-w-3xl" x-data="invoiceActions()">
        @include('invoices.detail')

        <x-email-modal />
        <x-receipt-modal />
        <x-confirm-modal />
    </div>
@endsection
