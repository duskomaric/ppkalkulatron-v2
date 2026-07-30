@props(['invoices'])

{{--
    Cijela lista računa u jednoj komponenti: kartice na telefonu, mreža na desktopu.
    Isti raspored kolona kao v1 — Račun/Klijent, Status, Datum, Dospijeće, Plaćanje, Ukupno.
--}}

@if ($invoices->isEmpty())
    <div class="p-10 rounded-2xl border-2 border-dashed border-[var(--color-text-dim)]/20 text-center bg-[var(--color-text-dim)]/5">
        <x-icon name="file-text" class="h-8 w-8 mx-auto mb-3 text-[var(--color-text-dim)]" />
        <p class="text-[10px] font-black uppercase tracking-[0.2em] text-[var(--color-text-dim)]">Nema računa</p>
        <a href="{{ route('invoices.create') }}" class="inline-block mt-4 text-xs font-bold text-primary">Kreiraj prvi račun</a>
    </div>
@else
    {{-- Telefon --}}
    <div class="md:hidden space-y-3">
        @foreach ($invoices as $invoice)
            <a href="{{ route('invoices.show', $invoice) }}"
               class="group block p-4 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-hover)] hover:border-primary/30 transition-all space-y-2.5">
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 min-w-0">
                        <x-icon name="hash" class="w-3 h-3 text-primary shrink-0" />
                        <span class="text-base font-black tracking-tighter italic leading-none group-hover:text-primary transition-colors truncate">
                            {{ $invoice->invoice_number }}
                        </span>
                    </div>
                    <x-status-badge :status="$invoice->status" />
                </div>

                <div class="flex items-center gap-2">
                    <x-icon name="user" class="w-3.5 h-3.5 text-[var(--color-text-dim)] shrink-0" />
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
            </a>
        @endforeach
    </div>

    {{-- Desktop: zaglavlje --}}
    <div class="hidden md:grid grid-cols-[minmax(0,1.6fr)_0.6fr_0.7fr_0.7fr_0.7fr_0.7fr] gap-3 px-4 pb-2 mb-1">
        @foreach (['Račun / Klijent', 'Status', 'Datum', 'Dospijeće', 'Plaćanje'] as $column)
            <span class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">{{ $column }}</span>
        @endforeach
        <span class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] text-right">Ukupno</span>
    </div>

    {{-- Desktop: redovi --}}
    <div class="hidden md:block space-y-3">
        @foreach ($invoices as $invoice)
            <a href="{{ route('invoices.show', $invoice) }}"
               class="group block p-4 rounded-2xl border border-[var(--color-border)] bg-[var(--color-surface)] hover:bg-[var(--color-surface-hover)] hover:border-primary/30 transition-all">
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

                    <div><x-status-badge :status="$invoice->status" /></div>

                    <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $invoice->date->format('d.m.Y.') }}</span>
                    <span class="text-xs font-bold text-[var(--color-text-muted)]">{{ $invoice->due_date->format('d.m.Y.') }}</span>
                    <span class="text-xs font-bold text-[var(--color-text-muted)] truncate">{{ $invoice->payment_type->label() }}</span>

                    <span class="text-base font-black tracking-tighter italic text-right">
                        {{ $invoice->formatted($invoice->total) }} {{ $invoice->currency }}
                    </span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="mt-6">{{ $invoices->links() }}</div>
@endif
