@php
    $headTone = match ($invoice->status->value) {
        'refunded' => 'bg-red-500/10 text-red-500',
        'fiscalized' => 'bg-emerald-500/10 text-emerald-500',
        default => 'bg-amber-500/10 text-amber-500',
    };
@endphp

<div class="mt-6 pt-4 border-t-2 border-dashed border-[var(--color-border)]">
    <div class="mb-3 flex items-center justify-between gap-2">
        <div class="flex items-center gap-2">
            <div class="h-7 w-7 {{ $headTone }} rounded-lg flex items-center justify-center shrink-0">
                <x-icon name="file-text" class="h-4 w-4" />
            </div>
            <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">
                Fiskalizacija (OFS ESIR)
            </p>
        </div>
        <a href="{{ route('help') }}#fiskalizacija" title="Pomoć za fiskalizaciju"
           class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-[var(--color-border)] text-[var(--color-text-dim)] transition-colors hover:border-[var(--color-primary)] hover:text-[var(--color-primary)]">
            <x-icon name="info" class="h-4 w-4" />
        </a>
    </div>

    <div class="p-4 rounded-2xl border border-[var(--color-border-strong)] bg-[var(--color-surface)] space-y-3">
        <div class="flex items-start justify-between gap-3">
            <p class="text-sm font-black text-[var(--color-text-main)] mt-1">{{ $invoice->status->label() }}</p>
            <x-status-badge :label="$invoice->status->label()" :color="$invoice->status->badgeColor()" />
        </div>

        <div class="h-px bg-[var(--color-border)]"></div>

        @if ($invoice->refundInvoice)
            {{-- Blok, ne @php(...): inline oblik bi ovdje otvorio raw blok do prvog @endphp niže u fajlu. --}}
            @php
                $refundFiscalized = $invoice->refundInvoice->status === \App\Enums\InvoiceStatus::Refunded;
            @endphp
            <div class="rounded-xl border-2 border-dashed p-3 {{ $refundFiscalized ? 'border-red-500/30 bg-red-500/5' : 'border-amber-500/30 bg-amber-500/5' }}">
                <p class="text-[11px] font-bold {{ $refundFiscalized ? 'text-red-600 dark:text-red-400' : 'text-amber-600 dark:text-amber-400' }}">
                    @if ($refundFiscalized)
                        Račun je poništen stornom {{ $invoice->refundInvoice->invoice_number }}.
                    @else
                        Storno {{ $invoice->refundInvoice->invoice_number }} je kreiran, ali još nije fiskalizovan — račun važi dok se storno ne fiskalizuje.
                    @endif
                </p>
                <a href="{{ route('invoices.show', $invoice->refundInvoice) }}" class="mt-1 inline-flex text-[11px] font-black text-primary hover:underline">
                    Otvori storno
                </a>
            </div>
        @endif

        @forelse ($invoice->fiscalRecords as $record)
            @php
                [$box, $text, $accent] = match ($record->type->value) {
                    'original' => ['bg-emerald-500/10 border-emerald-500/30', 'text-emerald-600', 'bg-emerald-500'],
                    'copy' => ['bg-blue-500/10 border-blue-500/30', 'text-blue-600', 'bg-blue-500'],
                    default => ['bg-red-500/10 border-red-500/30', 'text-red-600', 'bg-red-500'],
                };
            @endphp

            <div class="relative rounded-xl border-2 {{ $box }} transition-all overflow-hidden">
                <div class="p-3 sm:px-4 flex flex-col sm:flex-row justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 mb-1.5">
                            <span class="text-[9px] font-black uppercase tracking-widest px-1.5 py-0.5 rounded text-white {{ $accent }} shrink-0">
                                {{ $record->type->label() }}
                            </span>
                            <span class="text-sm font-bold text-[var(--color-text-main)] truncate leading-none">
                                {{ $record->fiscal_invoice_number ?: '—' }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-[var(--color-text-dim)]">
                            @if ($record->fiscalized_at)
                                <span class="flex items-center gap-1 shrink-0">
                                    <span class="opacity-60 text-[9px] uppercase font-bold tracking-tighter">Vrijeme:</span>
                                    {{ $record->fiscalized_at->format('d.m.Y. H:i') }}
                                </span>
                            @endif
                            @if ($record->fiscal_counter !== null)
                                <span class="flex items-center gap-1 shrink-0">
                                    <span class="opacity-60 text-[9px] uppercase font-bold tracking-tighter">Brojač:</span>
                                    {{ $record->fiscal_counter }}
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($record->verification_url || $record->receipt)
                        <div class="flex items-center gap-2 sm:self-center border-t sm:border-t-0 pt-2 sm:pt-0 border-black/5">
                            @if ($record->verification_url)
                                <a href="{{ $record->verification_url }}" title="Provjeri kod Poreske uprave"
                                   class="cursor-pointer flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white shadow-sm border border-black/5 hover:scale-[1.02] active:scale-[0.98] transition-all {{ $text }}">
                                    <x-icon name="external-link" class="h-5 w-5" />
                                    <span class="text-[10px] font-black uppercase">Provjeri</span>
                                </a>
                            @endif

                            @if ($record->receipt)
                                <button type="button" title="Prikaži fiskalni dokument"
                                        x-on:click="{{ \App\Support\Js::call('$data.openReceipt', route('invoices.receipt', $record, false), match (strtolower($record->receipt?->extension ?: 'png')) { 'pdf' => 'pdf', 'html', 'htm' => 'html', default => 'image' }, $record->verification_url, getenv('JUMP_BRIDGE_PORT') !== false || isMobile()) }}"
                                        class="cursor-pointer flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-white shadow-sm border border-black/5 hover:scale-[1.02] active:scale-[0.98] transition-all {{ $text }}">
                                    <x-icon name="image" class="h-5 w-5" />
                                    <span class="text-[10px] font-black uppercase">Prikaži</span>
                                </button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-4 rounded-xl border-2 border-dashed border-[var(--color-text-dim)]/20 text-center bg-[var(--color-text-dim)]/5">
                <p class="text-[11px] font-bold text-[var(--color-text-dim)]">Račun još nije fiskalizovan.</p>
            </div>
        @endforelse

        <div class="flex flex-wrap gap-2 pt-1">
            @if ($invoice->status === \App\Enums\InvoiceStatus::Created)
                <x-fiscal-health-indicator :health="$fiscalHealth" :url="route('checks', [], false)" />
                <button type="button"
                        x-on:click="{{ \App\Support\Js::call('$data.fiscalAction', route('invoices.fiscalize', $invoice, false), 'Fiskalizovati račun '.$invoice->invoice_number.'?') }}"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 font-black text-[11px] uppercase tracking-[0.15em] hover:bg-emerald-500 hover:text-white transition-all cursor-pointer">
                    <x-icon name="check" class="h-4 w-4" /> Fiskalizuj
                </button>
            @endif

            @if ($invoice->originalFiscalRecord())
                <button type="button"
                        x-on:click="{{ \App\Support\Js::call('$data.fiscalAction', route('invoices.fiscal-copy', $invoice, false), 'Odštampati kopiju računa?') }}"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-blue-500/10 text-blue-500 border border-blue-500/20 font-black text-[11px] uppercase tracking-[0.15em] hover:bg-blue-500 hover:text-white transition-all cursor-pointer">
                    <x-icon name="file-text" class="h-4 w-4" /> Kopija
                </button>
            @endif

            @if ($invoice->status === \App\Enums\InvoiceStatus::Fiscalized && ! $invoice->refund_invoice_id && ! $invoice->originalInvoice)
                <button type="button"
                        x-on:click="{{ \App\Support\Js::call('$data.fiscalAction', route('invoices.create-refund', $invoice, false), 'Kreirati storno računa '.$invoice->invoice_number.'?') }}"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 font-black text-[11px] uppercase tracking-[0.15em] hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                    <x-icon name="repeat" class="h-4 w-4" /> Kreiraj storno
                </button>
            @endif

            @if ($invoice->originalInvoice && $invoice->status === \App\Enums\InvoiceStatus::RefundCreated)
                <button type="button"
                        x-on:click="{{ \App\Support\Js::call('$data.fiscalAction', route('invoices.fiscal-refund', $invoice, false), 'Fiskalizovati storno?') }}"
                        class="flex-1 min-w-[140px] inline-flex items-center justify-center gap-2 py-3 rounded-xl bg-red-500/10 text-red-500 border border-red-500/20 font-black text-[11px] uppercase tracking-[0.15em] hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                    <x-icon name="repeat" class="h-4 w-4" /> Fiskalizuj storno
                </button>
            @endif
        </div>
    </div>
</div>
