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
                <x-empty-state icon="x" title="Nema pronađenih klijenata" />
            @else
                <x-list-header grid="grid-cols-[minmax(0,1.3fr)_0.5fr_0.9fr_0.7fr_0.8fr]" :columns="[
                    ['label' => 'Klijent'], ['label' => 'Status'], ['label' => 'Email'],
                    ['label' => 'Telefon'], ['label' => 'Lokacija'],
                ]" />

                <div class="space-y-4 md:space-y-3">
                    @foreach ($clients as $client)
                        @php($status = ['label' => $client->is_active ? 'Aktivan' : 'Neaktivan', 'color' => $client->is_active ? 'green' : 'gray'])

                        <x-responsive-entity-card :href="route('clients.edit', $client)"
                                                  :x-on:click.prevent="\App\Support\Js::call('openForm', route('clients.edit', [$client, 'partial' => 1]), 'Izmjena klijenta')">
                            <x-slot:mobile>
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <x-icon name="contact" class="w-3 h-3 text-primary shrink-0" />
                                        <span class="text-base font-black tracking-tighter italic leading-none truncate group-hover:text-primary transition-colors">
                                            {{ $client->name }}
                                        </span>
                                    </div>
                                    <x-status-badge :label="$status['label']" :color="$status['color']" />
                                </div>

                                <div class="h-[1px] w-full bg-[var(--color-border)]"></div>

                                <div class="flex justify-between items-end gap-3">
                                    <div class="flex gap-4 min-w-0">
                                        @if ($client->email)
                                            <x-meta-item icon="mail" label="Email" :value="$client->email"
                                                         value-class="truncate max-w-[150px]" />
                                        @endif
                                        @if ($client->phone)
                                            <x-meta-item icon="phone" label="Telefon" :value="$client->phone" />
                                        @endif
                                    </div>

                                    @if ($client->address || $client->city)
                                        <x-meta-item icon="map-pin" label="Lokacija" class="items-end text-right shrink-0"
                                                     value-class="text-[var(--color-text-main)] tracking-tight italic leading-none">
                                            @if ($client->zip)
                                                <span class="text-primary text-[9px] not-italic opacity-70 mr-1">{{ $client->zip }}</span>
                                            @endif
                                            {{ $client->city }}
                                        </x-meta-item>
                                    @endif
                                </div>
                            </x-slot:mobile>

                            <x-slot:desktop>
                                <div class="grid grid-cols-[minmax(0,1.3fr)_0.5fr_0.9fr_0.7fr_0.8fr] gap-3 items-center">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                            <x-icon name="contact" class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">
                                                {{ $client->name }}
                                            </p>
                                            @if ($client->address || $client->city)
                                                <p class="text-xs font-bold text-[var(--color-text-muted)] truncate">
                                                    {{ $client->address }}{{ $client->city ? ' · '.$client->city : '' }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div><x-status-badge :label="$status['label']" :color="$status['color']" /></div>

                                    <div class="text-xs font-bold text-[var(--color-text-muted)] truncate">{{ $client->email ?: '—' }}</div>
                                    <div class="text-xs font-bold text-[var(--color-text-muted)]">{{ $client->phone ?: '—' }}</div>

                                    <div class="text-xs font-bold text-[var(--color-text-muted)] truncate">
                                        @if ($client->city)
                                            @if ($client->zip)
                                                <span class="text-primary text-[9px] not-italic opacity-70 mr-1">{{ $client->zip }}</span>
                                            @endif
                                            {{ $client->city }}
                                        @else
                                            —
                                        @endif
                                    </div>
                                </div>
                            </x-slot:desktop>
                        </x-responsive-entity-card>
                    @endforeach
                </div>

                <div class="mt-6">{{ $clients->links() }}</div>
            @endif
        </div>

        <x-entity-form-drawer />
    </div>
@endsection
