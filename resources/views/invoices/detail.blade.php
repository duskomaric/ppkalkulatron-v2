{{-- Detalji računa koriste se na punoj stranici i u standardnom Laravel toku. --}}
<x-detail-body :entity-name="$invoice->invoice_number" entity-icon="file-text">
    <x-slot:badges>
        @if ($invoice->imported_at)
            <x-status-badge label="Uvezen" color="blue" />
        @endif
        @if ($invoice->refundInvoice?->status === \App\Enums\InvoiceStatus::Refunded)
            <x-status-badge label="Poništen" color="red" />
        @endif
        <x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />
    </x-slot:badges>

    <div class="space-y-3">
        <x-section-block>
            <x-section-header icon="file-text" title="Osnovni podaci" />

            <x-details-grid :columns="2">
                <x-details-item icon="contact" label="Klijent" :value="$invoice->client?->name"
                                color="bg-blue-500/10 text-blue-500" />
                <x-details-item icon="globe" label="Jezik" :value="$invoice->language->label()"
                                color="bg-purple-500/10 text-purple-500" />
                <x-details-item icon="calendar" label="Datum" :value="$invoice->date->format('d.m.Y.')"
                                color="bg-green-500/10 text-green-500" />
                @if ($invoice->originalInvoice)
                    <x-details-item icon="repeat" label="Storno od" :value="$invoice->originalInvoice->invoice_number"
                                    color="bg-red-500/10 text-red-500" />
                @endif
                @if ($invoice->refundInvoice)
                    <x-details-item icon="repeat" label="Storno račun" :value="$invoice->refundInvoice->invoice_number"
                                    color="bg-red-500/10 text-red-500" />
                @endif
                <x-details-item icon="clock" label="Dospijeće" :value="$invoice->due_date->format('d.m.Y.')"
                                color="bg-green-500/10 text-green-500" />
                <x-details-item icon="credit-card" label="Valuta" :value="$invoice->currencySymbol()"
                                color="bg-amber-500/10 text-amber-500" />
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
                                <p class="text-sm font-black text-primary">{{ $invoice->formatted($item->total) }} {{ $invoice->currencySymbol() }}</p>
                            </div>
                            <div class="flex gap-4 text-[10px] text-[var(--color-text-dim)]">
                                <span>Kol: <strong class="text-[var(--color-text-muted)]">{{ $item->quantity }}</strong></span>
                                <span>Cijena: <strong class="text-[var(--color-text-muted)]">{{ $invoice->formatted($item->unit_price) }} {{ $invoice->currencySymbol() }}</strong></span>
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
                            <div class="text-xs font-bold text-[var(--color-text-muted)] text-right">{{ $invoice->formatted($item->unit_price) }} {{ $invoice->currencySymbol() }}</div>
                            <div class="text-xs font-bold text-[var(--color-text-muted)] text-right">{{ $item->tax_rate / 100 }}%</div>
                            <div class="text-sm font-black text-primary text-right">{{ $invoice->formatted($item->total) }} {{ $invoice->currencySymbol() }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-section-block>

        <div class="p-3 bg-primary/5 border border-primary/20 rounded-2xl space-y-1.5">
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">Osnovica:</span>
                <span class="font-bold text-[var(--color-text-main)]">{{ $invoice->formatted($invoice->subtotal) }} {{ $invoice->currencySymbol() }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">PDV:</span>
                <span class="font-bold text-[var(--color-text-main)]">{{ $invoice->formatted($invoice->tax_total) }} {{ $invoice->currencySymbol() }}</span>
            </div>
            <div class="h-[1px] bg-primary/20"></div>
            <div class="flex justify-between">
                <span class="text-sm font-bold text-[var(--color-text-main)]">Ukupno:</span>
                <span class="text-xl font-black text-primary tracking-tighter italic">{{ $invoice->formatted($invoice->total) }} {{ $invoice->currencySymbol() }}</span>
            </div>

            {{-- Kasi iznosi idu u KM, pa se uz stranu valutu pokazuje i preračun. --}}
            @if ($bam = $invoice->bamEquivalent())
                <p class="text-right text-[11px] font-bold {{ $bam['total'] === null ? 'text-amber-500' : 'text-primary' }}">
                    @if ($bam['total'] === null)
                        Kurs za {{ $invoice->currency }} nije preuzet — račun se ne može fiskalizovati
                    @else
                        ≈ {{ $invoice->formatted($bam['total']) }} KM · kurs {{ rtrim(rtrim(number_format((float) $bam['rate'], 6, ',', '.'), '0'), ',') }}
                    @endif
                </p>
            @endif
        </div>
        <div class="flex gap-2">
            <a href="{{ route('invoices.pdf', $invoice) }}"
               x-on:click="if (@js(getenv('JUMP_BRIDGE_PORT') !== false || isMobile())) { $event.preventDefault(); $data.preparePdf(@js(route('invoices.pdf', $invoice, false)), @js(app(\App\Services\InvoicePdfService::class)->filename($invoice)), true); }"
               class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-primary/30 bg-primary/10 text-primary font-bold text-sm hover:bg-primary/20 transition-all cursor-pointer min-h-[44px]">
                <x-icon name="file-text" class="h-4 w-4" />
                <span x-text="pdfPreparing ? 'Pripremam PDF...' : (pdfFile ? 'Ponovo podijeli PDF' : 'Preuzmi PDF')">Preuzmi PDF</span>
            </a>

            @php
                $emailDefaults = [
                    'url' => route('invoices.email', $invoice, false),
                    'to' => $invoice->client?->email ?? '',
                    'subject' => 'Račun '.$invoice->invoice_number,
                    'body' => "Poštovani,\n\nU prilogu vam šaljemo račun {$invoice->invoice_number}.\n\nS poštovanjem",
                    'receipts' => $invoice->fiscalRecords->map(fn ($record) => [
                        'id' => $record->id,
                        'type_label' => $record->type->label(),
                    ])->values(),
                ];
            @endphp

            <button type="button" :disabled="! $data.openEmail"
                    x-on:click="{{ \App\Support\Js::call('$data.openEmail', $emailDefaults) }}"
                    class="flex-1 flex items-center justify-center gap-2 py-3 px-4 rounded-xl border-2 border-blue-500/30 bg-blue-500/10 text-blue-500 font-bold text-sm hover:bg-blue-500/20 transition-all cursor-pointer min-h-[44px] disabled:opacity-50">
                <x-icon name="mail" class="h-4 w-4" /> Pošalji mail
            </button>
        </div>

        @include('invoices.fiscal')
    </div>

    <x-slot:actions>
        @if ($invoice->isDeletable())
            <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" class="flex-1"
                  data-confirm="Obrisati račun {{ $invoice->invoice_number }}?">
                @csrf @method('DELETE')
                <x-drawer-action-button tone="danger" icon="trash" label="Obriši" class="w-full" />
            </form>
        @endif

        {{-- Fiskalizovan račun se smije dopuniti, ali samo uz svjesnu potvrdu. --}}
        <x-drawer-action-button icon="pencil" label="Uredi" :href="route('invoices.edit', $invoice, false)"
                                :confirm="$invoice->fiscalRecords->isNotEmpty()
                                    ? 'Račun '.$invoice->invoice_number.' je već fiskalizovan. Izmjena ne mijenja fiskalni račun kod Poreske uprave. Nastaviti?'
                                    : null" />
    </x-slot:actions>
</x-detail-body>
