@props(['invoices'])

@if ($invoices->isEmpty())
    <x-empty-state icon="x" title="Nema pronađenih računa"
                   :action="route('invoices.create')" action-label="Novi račun" />
@else
    {{-- Telefon --}}
    <div class="md:hidden space-y-3">
        @foreach ($invoices as $invoice)
            <x-entity-card :href="route('invoices.show', $invoice)">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-icon name="hash" class="w-3 h-3 text-primary shrink-0" />
                        <span class="text-base font-black tracking-tighter italic leading-none group-hover:text-primary transition-colors truncate">
                            {{ $invoice->invoice_number }}
                        </span>
                    </div>
                    <x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />@if ($invoice->refundInvoice?->status === \App\Enums\InvoiceStatus::Refunded)<x-status-badge label="Poništen" color="red" class="ml-1" />@endif @if ($invoice->imported_at)<x-status-badge label="Uvezen" color="blue" class="ml-1" />@endif
                </div>

                <div class="flex items-center gap-2">
                    <x-icon name="contact" class="w-3.5 h-3.5 text-[var(--color-text-dim)] shrink-0" />
                    <span class="text-xs font-bold text-[var(--color-text-muted)] tracking-tight truncate">
                        {{ $invoice->client?->name ?? 'Nepoznat klijent' }}
                    </span>
                </div>

                @if ($invoice->originalInvoice)
                    <div class="flex items-center gap-2">
                        <x-icon name="repeat" class="w-3 h-3 text-red-500 shrink-0" />
                        <span class="text-[10px] font-bold text-[var(--color-text-dim)] tracking-tight truncate">
                            Storno od: {{ $invoice->originalInvoice->invoice_number }}
                        </span>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <x-icon name="credit-card" class="w-3 h-3 text-[var(--color-text-dim)] shrink-0" />
                    <span class="text-[10px] font-bold text-[var(--color-text-muted)]">{{ $invoice->payment_type->label() }}</span>
                </div>

                <div class="h-px w-full bg-[var(--color-border)]"></div>

                <div class="flex justify-between items-end">
                    <div class="flex gap-4">
                        <x-meta-item icon="calendar" label="Datum" :value="$invoice->date->format('d.m.Y.')" />
                        <x-meta-item icon="clock" label="Dospijeće" :value="$invoice->due_date->format('d.m.Y.')" />
                    </div>
                    <p class="text-lg font-black tracking-tighter italic">
                        {{ $invoice->formatted($invoice->total) }} {{ $invoice->currencySymbol() }}
                    </p>
                </div>
            </x-entity-card>
        @endforeach
    </div>

    {{-- Desktop --}}
    <x-list-header grid="grid-cols-[minmax(0,1.6fr)_0.6fr_0.7fr_0.7fr_0.7fr_0.7fr]" :columns="[
        ['label' => 'Račun / Klijent'],
        ['label' => 'Status'],
        ['label' => 'Datum'],
        ['label' => 'Dospijeće'],
        ['label' => 'Plaćanje'],
        ['label' => 'Ukupno', 'align' => 'right'],
    ]" />

    <div class="hidden md:block space-y-3">
        @foreach ($invoices as $invoice)
            <x-entity-card :href="route('invoices.show', $invoice)">
                <div class="grid grid-cols-[minmax(0,1.6fr)_0.6fr_0.7fr_0.7fr_0.7fr_0.7fr] gap-3 items-center">
                    <div class="flex items-center gap-3 min-w-0">
                        <span class="h-10 w-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0">
                            <x-icon name="file-text" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 min-w-0">
                                <x-icon name="hash" class="w-3 h-3 text-primary shrink-0" />
                                <span class="text-sm font-black tracking-tighter italic truncate group-hover:text-primary transition-colors">
                                    {{ $invoice->invoice_number }}
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-1 text-xs font-bold text-[var(--color-text-muted)] min-w-0">
                                <x-icon name="contact" class="w-3.5 h-3.5 text-[var(--color-text-dim)] shrink-0" />
                                <span class="truncate">{{ $invoice->client?->name ?? 'Nepoznat klijent' }}</span>
                            </div>

                            @if ($invoice->originalInvoice)
                                <div class="flex items-center gap-1.5 mt-1 text-[10px] font-bold text-[var(--color-text-dim)] min-w-0">
                                    <x-icon name="repeat" class="w-3 h-3 text-red-500 shrink-0" />
                                    <span class="truncate">Storno od: {{ $invoice->originalInvoice->invoice_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div><x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />@if ($invoice->refundInvoice?->status === \App\Enums\InvoiceStatus::Refunded)<x-status-badge label="Poništen" color="red" class="ml-1" />@endif @if ($invoice->imported_at)<x-status-badge label="Uvezen" color="blue" class="ml-1" />@endif</div>

                    <div class="flex items-center gap-1 text-xs font-bold text-[var(--color-text-muted)]">
                        <x-icon name="calendar" class="w-3 h-3 text-[var(--color-text-dim)]" />
                        <span>{{ $invoice->date->format('d.m.Y.') }}</span>
                    </div>

                    <div class="flex items-center gap-1 text-xs font-bold text-[var(--color-text-muted)]">
                        <x-icon name="clock" class="w-3 h-3 text-[var(--color-text-dim)]" />
                        <span>{{ $invoice->due_date->format('d.m.Y.') }}</span>
                    </div>

                    <div class="flex items-center gap-1 text-xs font-bold text-[var(--color-text-muted)] min-w-0">
                        <x-icon name="credit-card" class="w-3 h-3 text-[var(--color-text-dim)] shrink-0" />
                        <span class="truncate">{{ $invoice->payment_type->label() }}</span>
                    </div>

                    <p class="text-right text-lg font-black tracking-tighter italic">
                        {{ $invoice->formatted($invoice->total) }} {{ $invoice->currencySymbol() }}
                    </p>
                </div>
            </x-entity-card>
        @endforeach
    </div>

    <div class="mt-6">{{ $invoices->links() }}</div>
@endif
