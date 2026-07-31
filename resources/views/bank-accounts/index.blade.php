@extends('layouts.app')
@section('title', 'Bankovni računi')

@section('actions')
    <x-create-button label="Novi račun" x-on:click="$dispatch('open-entity-form')" />
@endsection

@section('content')
    {{-- v1 ovdje nema tabelu nego mrežu kartica sa olovkom u uglu. --}}
    <div x-data="entityIndex()"
         x-on:open-entity-form.window="openForm({{ \App\Support\Js::from(route('bank-accounts.create', ['partial' => 1])) }}, 'Novi bankovni račun')">
        <div data-entity-list>
            @if ($accounts->isEmpty())
                <x-empty-state icon="credit-card" title="Nema bankovnih računa" />
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 animate-fade-in">
                    @foreach ($accounts as $account)
                        {{-- Cijela kartica je meta za dodir; olovka od 32px je premala na telefonu. --}}
                    <a href="{{ route('bank-accounts.edit', $account) }}"
                       x-on:click.prevent="{{ \App\Support\Js::call('openForm', route('bank-accounts.edit', [$account, 'partial' => 1]), 'Izmjena bankovnog računa') }}"
                       class="block bg-[var(--color-surface)] border border-[var(--color-border)] p-5 rounded-2xl relative group shadow-sm hover:shadow-md hover:border-primary/40 transition-all">
                            <div class="flex justify-between items-start mb-2 pr-12">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-[var(--color-text-main)] text-lg truncate">{{ $account->bank_name }}</h4>
                                    <p class="text-sm text-[var(--color-text-dim)] font-mono mt-1 tracking-wide">{{ $account->account_number }}</p>
                                </div>

                                @if ($account->show_on_documents)
                                    <span class="text-primary text-xs font-medium shrink-0" title="Prikazuje se na PDF dokumentima">
                                        Na dokumentima
                                    </span>
                                @endif
                            </div>

                            @if ($account->swift)
                                <div class="flex gap-4 text-xs font-bold text-[var(--color-text-muted)] mt-4 pt-4 border-t border-[var(--color-border)]">
                                    <span>SWIFT: {{ $account->swift }}</span>
                                </div>
                            @endif
                    </a>
                    @endforeach
                </div>
            @endif
        </div>

        <x-entity-form-drawer />
    </div>
@endsection
