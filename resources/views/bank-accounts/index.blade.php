@extends('layouts.app')
@section('title', 'Bankovni računi')

@section('actions')
    <x-create-button label="Novi račun" x-on:click="$dispatch('open-entity-form')" />
@endsection

@section('content')
    <div x-data="entityIndex()"
         x-on:open-entity-form.window="openForm({{ \App\Support\Js::from(route('bank-accounts.create', ['partial' => 1])) }}, 'Novi bankovni račun')">
        <div data-entity-list>
            @if ($accounts->isEmpty())
                <x-empty-state icon="credit-card" title="Nema bankovnih računa" />
            @else
                <x-list-header grid="grid-cols-[minmax(0,1.4fr)_1fr_0.7fr_0.8fr]" :columns="[
                    ['label' => 'Banka'], ['label' => 'Broj računa'], ['label' => 'SWIFT'], ['label' => 'Prikaz'],
                ]" />

                <div class="space-y-3">
                    @foreach ($accounts as $account)
                        <x-entity-card :href="route('bank-accounts.edit', $account)"
                                       :x-on:click.prevent="\App\Support\Js::call('openForm', route('bank-accounts.edit', [$account, 'partial' => 1]), 'Izmjena bankovnog računa')">
                            <div class="md:grid md:grid-cols-[minmax(0,1.4fr)_1fr_0.7fr_0.8fr] md:gap-3 md:items-center flex flex-col gap-2">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                        <x-icon name="credit-card" class="h-5 w-5" />
                                    </span>
                                    <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">
                                        {{ $account->bank_name }}
                                    </p>
                                </div>

                                <span class="text-xs font-bold text-[var(--color-text-muted)] tabular-nums truncate">
                                    {{ $account->account_number }}
                                </span>

                                <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $account->swift ?: '—' }}</span>

                                <div>
                                    <x-status-badge :label="$account->show_on_documents ? 'Na dokumentima' : 'Skriven'"
                                                    :color="$account->show_on_documents ? 'green' : 'gray'" />
                                </div>
                            </div>
                        </x-entity-card>
                    @endforeach
                </div>
            @endif
        </div>

        <x-entity-form-drawer />
    </div>
@endsection
