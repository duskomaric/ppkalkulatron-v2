@extends('layouts.app')
@section('title', 'Bankovni računi')
@section('actions')<x-create-button :href="route('bank-accounts.create')" label="Novi račun" />@endsection

@section('content')
    @if ($accounts->isEmpty())
        <x-empty-state icon="credit-card" title="Nema bankovnih računa" :action="route('bank-accounts.create')"
                       action-label="Dodaj prvi račun" />
    @else
        <x-list-header grid="grid-cols-[minmax(0,1.4fr)_1fr_0.7fr_0.7fr]" :columns="[
            ['label' => 'Banka'], ['label' => 'Broj računa'], ['label' => 'SWIFT'], ['label' => 'Na dokumentima'],
        ]" />

        <div class="space-y-3">
            @foreach ($accounts as $account)
                <x-entity-card :href="route('bank-accounts.edit', $account)">
                    <div class="md:grid md:grid-cols-[minmax(0,1.4fr)_1fr_0.7fr_0.7fr] md:gap-3 md:items-center flex flex-col gap-2">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                                <x-icon name="credit-card" class="h-5 w-5" />
                            </span>
                            <p class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">{{ $account->bank_name }}</p>
                        </div>
                        <span class="text-xs font-bold text-[var(--color-text-muted)] tabular-nums">{{ $account->account_number }}</span>
                        <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $account->swift ?: '—' }}</span>
                        <div>
                            <x-status-badge :label="$account->show_on_documents ? 'Prikazuje se' : 'Skriven'"
                                            :color="$account->show_on_documents ? 'green' : 'gray'" />
                        </div>
                    </div>
                </x-entity-card>
            @endforeach
        </div>
    @endif
@endsection
