{{-- Sadržaj drawera sa detaljima računa; servira ga InvoiceController@show na ?partial=1. --}}
<x-detail-body :entity-name="$invoice->invoice_number" entity-icon="file-text">
    <x-slot:badges>
        <x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />
    </x-slot:badges>

    <div class="space-y-3">
        <x-section-block>
            <x-section-header icon="file-text" title="Osnovni podaci" />

            {{-- Redoslijed i boje pločica prate v1. Izostavljeni su Jezik, Izvor i
                 Ponavljanje — v2 ima jedan jezik, nema konverzije ni ponavljanja. --}}
            <x-details-grid :columns="2">
                <x-details-item icon="contact" label="Klijent" :value="$invoice->client?->name"
                                color="bg-blue-500/10 text-blue-500" />
                <x-details-item icon="calendar" label="Datum" :value="$invoice->date->format('d.m.Y.')"
                                color="bg-green-500/10 text-green-500" />
                @if ($invoice->refundInvoice)
                    <x-details-item icon="repeat" label="Storno od" :value="$invoice->refundInvoice->invoice_number"
                                    color="bg-red-500/10 text-red-500" />
                @endif
                <x-details-item icon="clock" label="Dospijeće" :value="$invoice->due_date->format('d.m.Y.')"
                                color="bg-green-500/10 text-green-500" />
                <x-details-item icon="credit-card" label="Valuta" :value="$invoice->currency"
                                color="bg-amber-500/10 text-amber-500" />
                <x-details-item icon="file-text" label="Predložak" :value="$invoice->template->label()"
                                color="bg-indigo-500/10 text-indigo-500" />
                <x-details-item icon="credit-card" label="Način plaćanja" :value="$invoice->payment_type->label()"
                                color="bg-teal-500/10 text-teal-500" />
            </x-details-grid>
        </x-section-block>

        @if ($invoice->notes)
            <div class="p-3 bg-[var(--color-border)] rounded-2xl border border-[var(--color-border-strong)]">
                <div class="flex items-center gap-2 mb-1">
                    <x-icon name="sticky-note" class="h-3 w-3 text-[var(--color-text-dim)]" />
                    <span class="text-[8px] font-black uppercase tracking-widest text-[var(--color-text-dim)]">Napomena</span>
                </div>
                <p class="text-xs text-[var(--color-text-muted)] whitespace-pre-line">{{ $invoice->notes }}</p>
            </div>
        @endif

        <x-section-block>
            <x-section-header icon="boxes" title="Stavke ({{ $invoice->items->count() }})" />

            <div class="hidden md:grid grid-cols-[minmax(0,1fr)_70px_110px_80px_120px] gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] px-2">
                <span>Stavka</span>
                <span class="text-right">Kol.</span>
                <span class="text-right">Cijena</span>
                <span class="text-right">PDV</span>
                <span class="text-right">Ukupno</span>
            </div>

            <div class="space-y-2">
                @foreach ($invoice->items as $item)
                    <div class="p-3 bg-[var(--color-border)] rounded-xl border border-[var(--color-border-strong)]">
                        <div class="md:hidden">
                            <div class="flex justify-between items-start mb-2">
                                <div>
                                    <p class="text-sm font-bold text-[var(--color-text-main)]">{{ $item->name }}</p>
                                    @if ($item->description)
                                        <p class="text-[10px] text-[var(--color-text-dim)] whitespace-pre-line">{{ $item->description }}</p>
                                    @endif
                                </div>
                                <p class="text-sm font-black text-primary">{{ $invoice->formatted($item->total) }} {{ $invoice->currency }}</p>
                            </div>
                            <div class="flex gap-4 text-[10px] text-[var(--color-text-dim)]">
                                <span>Kol: <strong class="text-[var(--color-text-muted)]">{{ $item->quantity }}</strong></span>
                                <span>Cijena: <strong class="text-[var(--color-text-muted)]">{{ $invoice->formatted($item->unit_price) }} {{ $invoice->currency }}</strong></span>
                                <span>PDV: <strong class="text-[var(--color-text-muted)]">{{ $item->tax_rate / 100 }}%</strong></span>
                            </div>
                        </div>

                        <div class="hidden md:grid grid-cols-[minmax(0,1fr)_70px_110px_80px_120px] gap-2 items-center">
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-[var(--color-text-main)]">{{ $item->name }}</p>
                                @if ($item->description)
                                    <p class="text-[10px] text-[var(--color-text-dim)] whitespace-pre-line">{{ $item->description }}</p>
                                @endif
                            </div>
                            <div class="text-xs font-bold text-[var(--color-text-muted)] text-right">{{ $item->quantity }}</div>
                            <div class="text-xs font-bold text-[var(--color-text-muted)] text-right">{{ $invoice->formatted($item->unit_price) }} {{ $invoice->currency }}</div>
                            <div class="text-xs font-bold text-[var(--color-text-muted)] text-right">{{ $item->tax_rate / 100 }}%</div>
                            <div class="text-sm font-black text-primary text-right">{{ $invoice->formatted($item->total) }} {{ $invoice->currency }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-section-block>

        <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-2xl space-y-1.5">
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">Osnovica:</span>
                <span class="font-bold text-[var(--color-text-main)]">{{ $invoice->formatted($invoice->subtotal) }} {{ $invoice->currency }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">PDV:</span>
                <span class="font-bold text-[var(--color-text-main)]">{{ $invoice->formatted($invoice->tax_total) }} {{ $invoice->currency }}</span>
            </div>
            <div class="h-[1px] bg-amber-500/20"></div>
            <div class="flex justify-between">
                <span class="text-sm font-bold text-[var(--color-text-main)]">Ukupno:</span>
                <span class="text-xl font-black text-primary tracking-tighter italic">{{ $invoice->formatted($invoice->total) }} {{ $invoice->currency }}</span>
            </div>
        </div>
    </div>

    @if ($invoice->isDeletable())
        <x-slot:actions>
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="flex-1"
                  onsubmit="return confirm('Obrisati račun {{ $invoice->invoice_number }}?')">
                @csrf @method('DELETE')
                <x-drawer-action-button tone="danger" icon="trash" label="Obriši" class="w-full" />
            </form>
            <x-drawer-action-button icon="pencil" label="Uredi" :href="route('invoices.edit', $invoice)"
                                    :x-on:click="'$data.openForm && ($event.preventDefault(), '
                                        .\App\Support\Js::call('$data.openForm', route('invoices.edit', [$invoice, 'partial' => 1]), 'Uredi račun').')'" />
        </x-slot:actions>
    @endif
</x-detail-body>
