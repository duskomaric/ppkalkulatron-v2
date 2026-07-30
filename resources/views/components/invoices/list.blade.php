@props(['invoices'])

{{--
    Cijela lista računa. Raspored i sadržaj kartice prate v1: na telefonu kartica sa
    brojem, statusom, klijentom, načinom plaćanja i podnožjem sa datumima i ukupnim
    iznosom; na desktopu ista mreža kolona kao u v1.
--}}

@if ($invoices->isEmpty())
    <x-empty-state icon="file-text" title="Nema računa" :action="route('invoices.create')" action-label="Kreiraj prvi račun" />
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
                    <x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />
                </div>

                <div class="flex items-center gap-2">
                    <x-icon name="contact" class="w-3.5 h-3.5 text-[var(--color-text-dim)] shrink-0" />
                    <span class="text-xs font-bold text-[var(--color-text-muted)] tracking-tight truncate">
                        {{ $invoice->client?->name ?? 'Nepoznat klijent' }}
                    </span>
                </div>

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
                        {{ $invoice->formatted($invoice->total) }} {{ $invoice->currency }}
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
                            <p class="text-xs font-bold text-[var(--color-text-muted)] truncate">
                                {{ $invoice->client?->name ?? 'Nepoznat klijent' }}
                            </p>
                        </div>
                    </div>

                    <div><x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" /></div>

                    <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $invoice->date->format('d.m.Y.') }}</span>
                    <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $invoice->due_date->format('d.m.Y.') }}</span>
                    <span class="text-xs font-bold text-[var(--color-text-muted)] truncate">{{ $invoice->payment_type->label() }}</span>

                    <span class="text-base font-black tracking-tighter italic text-right">
                        {{ $invoice->formatted($invoice->total) }} {{ $invoice->currency }}
                    </span>
                </div>
            </x-entity-card>
        @endforeach
    </div>

    <div class="mt-6">{{ $invoices->links() }}</div>
@endif
