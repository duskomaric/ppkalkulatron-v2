@extends('layouts.app')
@section('title', 'Klijenti')
@section('actions')<x-create-button :href="route('clients.create')" label="Novi klijent" />@endsection

@section('content')
    <x-search-bar :value="$q" placeholder="Pretraga po nazivu…" />

    @if ($clients->isEmpty())
        <x-empty-state icon="contact" title="Nema klijenata" :action="route('clients.create')" action-label="Dodaj prvog klijenta" />
    @else
        <x-list-header grid="grid-cols-[minmax(0,1.6fr)_0.8fr_0.8fr_0.6fr]" :columns="[
            ['label' => 'Klijent'], ['label' => 'Grad'], ['label' => 'JIB'], ['label' => 'Status'],
        ]" />

        <div class="space-y-3">
            @foreach ($clients as $client)
                <x-entity-card :href="route('clients.edit', $client)">
                    <div class="md:grid md:grid-cols-[minmax(0,1.6fr)_0.8fr_0.8fr_0.6fr] md:gap-3 md:items-center flex flex-col gap-2">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <x-icon name="contact" class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $client->name }}</p>
                                <p class="text-[11px] font-bold text-[var(--color-text-dim)] truncate">{{ $client->email ?: '—' }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $client->city ?: '—' }}</span>
                        <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $client->vat_id ?: '—' }}</span>
                        <div><x-status-badge :label="$client->is_active ? 'Aktivan' : 'Neaktivan'" :color="$client->is_active ? 'green' : 'gray'" /></div>
                    </div>
                </x-entity-card>
            @endforeach
        </div>

        <div class="mt-6">{{ $clients->links() }}</div>
    @endif
@endsection
