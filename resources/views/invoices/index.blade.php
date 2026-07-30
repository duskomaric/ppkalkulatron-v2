@extends('layouts.app')
@section('title', 'Računi')
@section('actions')<x-create-button :href="route('invoices.create')" label="Novi račun" />@endsection

@section('content')
    <div x-data="{ yearDrawer: false }">
        <x-invoices.filters :filters="$filters" :years="$years" :active-filters="$activeFilters" />
        <x-invoices.list :invoices="$invoices" />
    </div>
@endsection
