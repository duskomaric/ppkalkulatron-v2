@extends('layouts.app')
@section('title', 'Računi')

@section('heading')
    <span>Računi</span>
@endsection

@section('actions')
    <x-create-button label="Novi račun" :href="route('invoices.create')" />
@endsection

@section('content')
    <div>
        <x-invoices.filters :filters="$filters" :years="$years" :active-filters="$activeFilters" />
        <x-invoices.list :invoices="$invoices" />
    </div>
@endsection
