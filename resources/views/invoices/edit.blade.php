@extends('layouts.app')

@section('title', 'Izmjena računa')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('content')
    <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center gap-2 text-sm font-bold text-[var(--color-text-dim)] hover:text-primary transition-colors mb-5">
        <x-icon name="arrow-left" class="h-4 w-4" /> Nazad
    </a>

    <x-invoices.form :invoice="$invoice" :clients="$clients" :articles="$articles" />
@endsection
