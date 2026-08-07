@extends('layouts.app')
@section('title', 'Početno podešavanje')

@section('content')
    <x-back-link :href="route('invoices.index')" />

    <x-setup-guide :setup="$setup" :dismissible="false" />

    @if ($setup->isDismissed() && ! $setup->isComplete())
        <form method="POST" action="{{ route('setup.restore') }}" class="mt-4">
            @csrf
            <x-button variant="ghost" class="w-full !py-3 !text-[11px] !uppercase !tracking-[0.2em] !font-black">
                Vrati vodič na račune
            </x-button>
        </form>
    @endif
@endsection
