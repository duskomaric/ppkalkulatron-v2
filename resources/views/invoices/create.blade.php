@extends('layouts.app')

@section('title', 'Novi račun')

@section('content')
    <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[var(--color-text-dim)] hover:text-primary transition-colors mb-5">
        <x-icon name="arrow-left" class="h-4 w-4" /> Nazad
    </a>

    <x-invoices.form :clients="$clients" :articles="$articles" />
@endsection
