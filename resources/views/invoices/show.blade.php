@extends('layouts.app')

@section('title', 'Račun')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <div class="max-w-3xl" x-data>
        @include('invoices.detail')
    </div>
@endsection
