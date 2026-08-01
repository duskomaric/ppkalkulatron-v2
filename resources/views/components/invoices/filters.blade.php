@props(['filters', 'years', 'activeFilters'])

@php
    $statusOptions = ['' => 'Status: Svi'] + collect(\App\Enums\InvoiceStatus::cases())
        ->mapWithKeys(fn ($status) => [$status->value => 'Status: '.$status->label()])
        ->all();

    $paymentOptions = ['' => 'Plaćanje: Svi'] + collect(\App\Enums\PaymentType::cases())
        ->mapWithKeys(fn ($type) => [$type->value => 'Plaćanje: '.$type->label()])
        ->all();
@endphp

<div class="space-y-3 mb-4" x-data="{ filtersOpen: {{ collect($filters)->except('year')->filter()->isNotEmpty() ? 'true' : 'false' }}, yearDrawer: false }">
    <form method="GET">
        <input type="hidden" name="year" value="{{ $filters['year'] }}">

        {{-- Pretraga se namjerno skuplja da filter, godina i traženje stanu u isti red. --}}
        <div class="flex items-center gap-2">
            <x-filter-button state="filtersOpen" />
            <x-year-button :year="$filters['year']" />

            <div class="min-w-0 grow sm:ml-auto sm:w-[320px] sm:grow-0">
                <x-filter-search :value="$filters['q']" placeholder="Pretraži račune…" />
            </div>
        </div>

        <div x-cloak x-show="filtersOpen" class="mt-3 p-4 rounded-2xl border border-[var(--color-border-strong)] bg-[var(--color-surface)]">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Status</span>
                    <x-form-select variant="filter" name="status" :value="$filters['status']" :options="$statusOptions" auto-submit />
                </div>

                <div class="flex flex-col gap-1.5 min-w-0">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Plaćanje</span>
                    <x-form-select variant="filter" name="payment_type" :value="$filters['payment_type']" :options="$paymentOptions" auto-submit />
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
