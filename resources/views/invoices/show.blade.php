@extends('layouts.app')

@section('title', 'Račun')
@section('heading', 'Račun '.$invoice->invoice_number)

@section('actions')
    <x-status-badge :status="$invoice->status" />
@endsection

@section('content')
    <a href="{{ route('invoices.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[var(--color-text-dim)] hover:text-primary transition-colors mb-5">
        <x-icon name="arrow-left" class="h-4 w-4" /> Nazad
    </a>

    <div class="space-y-5">
        <x-section title="Podaci" icon="file-text">
            <dl class="grid grid-cols-2 gap-x-4 gap-y-3">
                @foreach ([
                    ['Klijent', $invoice->client?->name ?? 'Bez klijenta'],
                    ['Način plaćanja', $invoice->payment_type->label()],
                    ['Datum', $invoice->date->format('d.m.Y.')],
                    ['Rok dospijeća', $invoice->due_date->format('d.m.Y.')],
                ] as [$label, $value])
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">{{ $label }}</dt>
                        <dd class="text-sm font-bold mt-0.5">{{ $value }}</dd>
                    </div>
                @endforeach

                @if ($invoice->client?->vat_id)
                    <div>
                        <dt class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">JIB kupca</dt>
                        <dd class="text-sm font-bold mt-0.5">{{ $invoice->client->vat_id }}</dd>
                    </div>
                @endif
            </dl>
        </x-section>

        <x-section title="Stavke ({{ $invoice->items->count() }})" icon="box">
            <div class="space-y-2">
                @foreach ($invoice->items as $item)
                    <div class="flex items-start justify-between gap-4 p-3 rounded-xl bg-[var(--color-bg)]/40">
                        <div class="min-w-0">
                            <p class="text-sm font-bold truncate">{{ $item->name }}</p>
                            @if ($item->description)
                                <p class="text-[11px] text-[var(--color-text-dim)] whitespace-pre-line">{{ $item->description }}</p>
                            @endif
                            <p class="text-[11px] text-[var(--color-text-muted)] mt-0.5">
                                {{ $item->quantity }} {{ $item->unit->label() }} × {{ $invoice->formatted($item->unit_price) }}
                                @if ($item->tax_label)
                                    <span class="ml-1 text-[var(--color-text-dim)]">({{ $item->tax_label }} {{ $item->tax_rate / 100 }}%)</span>
                                @endif
                            </p>
                        </div>
                        <p class="text-sm font-black italic tracking-tight shrink-0">{{ $invoice->formatted($item->total) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="pt-3 border-t border-[var(--color-border)] space-y-1.5">
                <div class="flex justify-between text-xs font-bold text-[var(--color-text-muted)]">
                    <span>Osnovica</span><span>{{ $invoice->formatted($invoice->subtotal) }} {{ $invoice->currency }}</span>
                </div>
                <div class="flex justify-between text-xs font-bold text-[var(--color-text-muted)]">
                    <span>Porez</span><span>{{ $invoice->formatted($invoice->tax_total) }} {{ $invoice->currency }}</span>
                </div>
                <div class="flex justify-between items-end pt-2 border-t border-[var(--color-border)]">
                    <span class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">Ukupno</span>
                    <span class="text-2xl font-black italic tracking-tighter">{{ $invoice->formatted($invoice->total) }} {{ $invoice->currency }}</span>
                </div>
            </div>
        </x-section>

        @if ($invoice->notes)
            <x-section title="Napomena" icon="sticky-note">
                <p class="text-sm text-[var(--color-text-muted)] whitespace-pre-line">{{ $invoice->notes }}</p>
            </x-section>
        @endif

        <div class="flex flex-wrap gap-3">
            @if ($invoice->isDeletable())
                <x-button variant="ghost" :href="route('invoices.edit', $invoice)">
                    <x-icon name="pencil" class="h-4 w-4" /> Izmijeni
                </x-button>

                <form method="POST" action="{{ route('invoices.destroy', $invoice) }}"
                      onsubmit="return confirm('Obrisati račun {{ $invoice->invoice_number }}?')">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger">
                        <x-icon name="trash" class="h-4 w-4" /> Obriši
                    </x-button>
                </form>
            @else
                <p class="text-xs text-[var(--color-text-dim)]">Fiskalizovan račun se ne može mijenjati ni brisati.</p>
            @endif
        </div>
    </div>
@endsection
