@extends('layouts.app')

@section('title', 'Računi')

@section('actions')
    <x-button variant="primary" :href="route('invoices.create')">
        <x-icon name="plus" class="h-4 w-4" /> Novi račun
    </x-button>
@endsection

@section('content')
    <form method="GET" class="mb-6">
        <div class="relative">
            <x-icon name="search" class="h-4 w-4 absolute left-4 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]" />
            <input type="search" name="q" value="{{ $q }}" placeholder="Pretraga po broju ili klijentu…"
                   class="w-full pl-11 pr-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50 focus:ring-4 focus:ring-primary/10 transition-all">
        </div>
    </form>

    <x-invoices.list :invoices="$invoices" />
@endsection
