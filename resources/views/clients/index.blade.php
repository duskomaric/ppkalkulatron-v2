@extends('layouts.app')
@section('title', 'Klijenti')

@section('actions')
    <x-create-button label="Novi klijent" x-on:click="$dispatch('open-entity-form')" />
@endsection

@section('content')
    <div x-data="entityIndex()"
         x-on:open-entity-form.window="openForm({{ \App\Support\Js::from(route('clients.create', ['partial' => 1])) }}, 'Novi klijent')">
        <x-search-bar :value="$q" placeholder="Pretraga po nazivu…" />

        <div data-entity-list>
            @if ($clients->isEmpty())
                <x-empty-state icon="contact" title="Nema pronađenih klijenata" />
            @else
                {{-- Kolone kao u v1: klijent, status, email, telefon, lokacija. --}}
                <x-list-header grid="grid-cols-[minmax(0,1.3fr)_0.5fr_0.9fr_0.7fr_0.8fr]" :columns="[
                    ['label' => 'Klijent'], ['label' => 'Status'], ['label' => 'Email'],
                    ['label' => 'Telefon'], ['label' => 'Lokacija'],
                ]" />

                <div class="space-y-3">
                    @foreach ($clients as $client)
                        <x-entity-card :href="route('clients.edit', $client)"
                                       :x-on:click.prevent="\App\Support\Js::call('openForm', route('clients.edit', [$client, 'partial' => 1]), 'Izmjena klijenta')">
                            <div class="md:grid md:grid-cols-[minmax(0,1.3fr)_0.5fr_0.9fr_0.7fr_0.8fr] md:gap-3 md:items-center flex flex-col gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <x-icon name="contact" class="h-5 w-5" />
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $client->name }}</p>
                                        @if ($client->vat_id)
                                            <p class="text-[11px] font-bold text-[var(--color-text-dim)] truncate">JIB: {{ $client->vat_id }}</p>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <x-status-badge :label="$client->is_active ? 'Aktivan' : 'Neaktivan'"
                                                    :color="$client->is_active ? 'green' : 'gray'" />
                                </div>

                                <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--color-text-muted)] min-w-0">
                                    <x-icon name="mail" class="w-3 h-3 text-[var(--color-text-dim)] shrink-0" />
                                    <span class="truncate">{{ $client->email ?: '—' }}</span>
                                </div>

                                <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--color-text-muted)] min-w-0">
                                    <x-icon name="phone" class="w-3 h-3 text-[var(--color-text-dim)] shrink-0" />
                                    <span class="truncate">{{ $client->phone ?: '—' }}</span>
                                </div>

                                <div class="flex items-center gap-1.5 text-xs font-bold text-[var(--color-text-muted)] min-w-0">
                                    <x-icon name="map-pin" class="w-3 h-3 text-[var(--color-text-dim)] shrink-0" />
                                    <span class="truncate">{{ $client->city ?: '—' }}</span>
                                </div>
                            </div>
                        </x-entity-card>
                    @endforeach
                </div>

                <div class="mt-6">{{ $clients->links() }}</div>
            @endif
        </div>

        <x-entity-form-drawer />
    </div>
@endsection
