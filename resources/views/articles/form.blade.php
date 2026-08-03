@extends('layouts.app')
@section('title', $article ? 'Izmjena artikla' : 'Novi artikl')

@section('content')
    <x-back-link :href="route('articles.index')" />

    <div>
        @include('articles.form-fields')
    </div>
@endsection
