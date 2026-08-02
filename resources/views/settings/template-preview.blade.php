@extends('layouts.app')

@section('title', 'Pregled predloška')

@section('content')
    <x-back-link :href="route('settings.general.edit')" />
    <section class="template-full-preview">
        <div><p class="text-xs font-bold uppercase tracking-widest text-primary">Pregled predloška</p><h1 class="mt-1 text-xl font-black text-[var(--color-text-main)]">{{ $template->label() }}</h1><p class="mt-1 text-sm text-[var(--color-text-dim)]">Ovo je stvarni A4 dizajn sa oglednim podacima.</p></div>
        <div class="template-full-preview-stage"><iframe src="{{ $previewUrl }}" title="{{ $template->label() }}" class="template-full-preview-frame"></iframe></div>
    </section>
@endsection
