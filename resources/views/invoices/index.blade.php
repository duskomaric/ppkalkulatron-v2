@extends('layouts.app')
@section('title', 'Računi')

@section('heading')
    <span>Računi</span>
@endsection

@section('actions')
    @unless ($setup->shouldShow())
        <x-create-button label="Novi račun" :href="route('invoices.create')" />
    @endunless
@endsection

@section('content')
    {{-- Dok aplikacija nije podešena, koraci su korisniji od prazne liste. --}}
    @if ($setup->shouldShow())
        <x-setup-guide :setup="$setup" />
    @else
        <div>
            <x-invoices.filters :filters="$filters" :years="$years" :active-filters="$activeFilters" />
            <x-invoices.list :invoices="$invoices" />
        </div>
    @endif
@endsection
