@extends('layouts.app')
@section('title', $account ? 'Izmjena bankovnog računa' : 'Novi bankovni račun')

@section('content')
    <x-back-link :href="route('bank-accounts.index')" />

    <div>
        @include('bank-accounts.form-fields')
    </div>
@endsection
