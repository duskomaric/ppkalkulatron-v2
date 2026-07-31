@extends('layouts.app')
@section('title', $article ? 'Izmjena artikla' : 'Novi artikl')

@section('content')
    <x-back-link :href="route('articles.index')" />

    <div class="max-w-3xl">
        @include('articles.form-fields')
    </div>
@endsection
