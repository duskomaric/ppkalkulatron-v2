@props(['filters', 'years', 'activeFilters'])

{{--
    Cijela filter sekcija računa, po v1: traka sa dugmetom za filtere, godinom i
    pretragom; panel koji se otvara ispod; i traka aktivnih filtera sa resetom.
--}}

@php
    $statusOptions = ['' => 'Status: Svi'] + collect(\App\Enums\InvoiceStatus::cases())
        ->mapWithKeys(fn ($status) => [$status->value => 'Status: '.$status->label()])
        ->all();

    $paymentOptions = ['' => 'Plaćanje: Svi'] + collect(\App\Enums\PaymentType::cases())
        ->mapWithKeys(fn ($type) => [$type->value => 'Plaćanje: '.$type->label()])
        ->all();
@endphp

<div class="space-y-3 mb-4" x-data="{ filtersOpen: {{ collect($filters)->except('year')->filter()->isNotEmpty() ? 'true' : 'false' }} }">
    <form method="GET">
        <input type="hidden" name="year" value="{{ $filters['year'] }}">

        {{-- Na uskom ekranu pretraga ide u svoj red: uz dugmad joj ostane 133px. --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3">
            <div class="flex items-center gap-2">
                <x-filter-button state="filtersOpen" />
                <x-year-button :year="$filters['year']" />
            </div>

            <div class="w-full sm:w-[320px]">
                <x-filter-search :value="$filters['q']" placeholder="Pretraži račune…" />
            </div>
        </div>

        <div x-cloak x-show="filtersOpen" class="mt-3 p-4 rounded-2xl border border-[var(--color-border-strong)] bg-[var(--color-surface)]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Status</span>
                    <x-filter-pill-select name="status" :value="$filters['status']" :options="$statusOptions" />
                </div>

                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Plaćanje</span>
                    <x-filter-pill-select name="payment_type" :value="$filters['payment_type']" :options="$paymentOptions" />
                </div>

                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Datum kreiranja — Od</span>
                    <x-filter-date name="created_from" :value="$filters['created_from']" />
                </div>

                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Datum kreiranja — Do</span>
                    <x-filter-date name="created_to" :value="$filters['created_to']" />
                </div>
            </div>
        </div>
    </form>

    <x-active-filters :filters="$activeFilters" :reset-url="route('invoices.index', ['year' => $filters['year']])" />
    <x-year-drawer :years="$years" :selected="$filters['year']" />
</div>
