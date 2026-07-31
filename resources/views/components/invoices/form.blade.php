@props(['invoice' => null, 'clients', 'articles'])

{{--
    Forma računa prati v1 DocumentFormFields + InvoiceItemRow: pretraživi izbor
    klijenta i artikla, dodatna polja pod prekidačem, i po redu stavke prikaz
    osnovice i poreza. Iznosi se u pregledniku drže u fenizima, a serveru idu
    kao decimalni broj — server ih ionako preračuna.
--}}

@php
    $isEdit = $invoice !== null;
    $currency = $invoice?->currency ?? 'BAM';
    $taxRates = \App\Models\TaxRate::basisPointsByLabel();

    $oldItems = old('items', $isEdit
        ? $invoice->items->map(fn ($item) => [
            'article_id' => $item->article_id,
            'name' => $item->name,
            'description' => $item->description,
            'unit' => $item->unit->value,
            'tax_label' => $item->tax_label,
            'tax_rate' => $item->tax_rate,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
        ])->all()
        : []);

    // Nakon greške u validaciji cijena se vraća kao decimalni broj, a forma radi u fenizima.
    if (old('items')) {
        $oldItems = array_map(fn ($item) => $item + [
            'unit_price' => (int) round(((float) ($item['unit_price'] ?? 0)) * 100),
            'tax_rate' => $taxRates[$item['tax_label'] ?? ''] ?? 0,
        ], $oldItems);
    }

    $articleOptions = $articles->map(fn ($article) => [
        'id' => $article->id,
        'name' => $article->name,
        'description' => $article->description,
        'unit' => $article->unit->value,
        'tax_label' => $article->tax_label,
        'tax_rate' => $taxRates[$article->tax_label] ?? 0,
        'last_unit_price' => $article->last_unit_price,
    ])->values();
@endphp

<form method="POST" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}"
      class="space-y-4"
      x-data="invoiceForm(@js($oldItems), @js($articleOptions), @js($taxRates), @js($currency))">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-section-block>
        <x-section-header icon="file-text" title="Osnovni podaci" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <x-field label="Klijent" name="client_id" :value="$invoice?->client_id"
                     :options="['' => '— bez klijenta —'] + $clients->pluck('name', 'id')->all()" />

            <x-field label="Način plaćanja" name="payment_type" :value="$invoice?->payment_type->value ?? 'Cash'"
                     :options="\App\Enums\PaymentType::options()" required />

            <x-field label="Datum" name="date" type="date"
                     :value="$invoice?->date->format('Y-m-d') ?? now()->format('Y-m-d')" required />

            <x-field label="Rok dospijeća" name="due_date" type="date"
                     :value="$invoice?->due_date->format('Y-m-d') ?? now()->addDays(15)->format('Y-m-d')" required />
        </div>
    </x-section-block>

    <x-section-toggle title="Dodatna polja" subtitle="Napomena na dokumentu" open="extraOpen" />

    <div x-show="extraOpen" x-cloak class="space-y-3">
        <x-field label="Napomena" name="notes" rows="3" :value="$invoice?->notes" />
    </div>

    <x-section-block>
        <x-section-header icon="boxes" title="Stavke" />

        <div class="space-y-2">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-3 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]"
                              x-text="'#' + (index + 1)"></span>
                        <button type="button" x-on:click="removeItem(index)"
                                class="h-7 w-7 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                        </button>
                    </div>

                    <div class="flex flex-col md:flex-row md:items-end gap-2">
                        {{-- Pretraživi izbor artikla; slobodan unos ostaje moguć preko naziva ispod. --}}
                        <div class="flex-1 min-w-0 space-y-1.5 w-full" x-on:click.outside="item.open = false">
                            <div class="relative">
                                <div x-on:click="item.open = ! item.open"
                                     class="relative w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-left pl-10 pr-4 flex items-center justify-between gap-2 transition-all duration-300 cursor-pointer hover:border-[var(--color-border-strong)]"
                                     :class="item.open && 'border-primary/50 ring-2 ring-primary/10 bg-[var(--color-surface-hover)]'">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
                                        <x-icon name="boxes" class="h-4 w-4" />
                                    </div>
                                    <span class="text-sm font-bold truncate flex-1 min-w-0"
                                          :class="selected(item) ? 'text-[var(--color-text-main)]' : 'text-[var(--color-text-dim)]'"
                                          x-text="selected(item) ? selected(item).name : 'Odaberi artikal...'"></span>
                                    <div class="flex items-center gap-1 shrink-0">
                                        <button type="button" x-show="item.article_id" x-cloak
                                                x-on:click.stop="clear(item)"
                                                class="h-5 w-5 rounded-full bg-[var(--color-border)] hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer">
                                            <x-icon name="x" class="h-3 w-3" />
                                        </button>
                                        <x-icon name="chevron-down" class="h-4 w-4 text-[var(--color-text-dim)] transition-transform"
                                                ::class="item.open && 'rotate-180'" />
                                    </div>
                                </div>

                                <div x-show="item.open" x-cloak
                                     class="fixed z-[1100] left-3 right-3 bottom-3 top-24 md:absolute md:z-50 md:top-full md:left-0 md:right-0 md:bottom-auto md:mt-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-2xl overflow-hidden animate-fade-in flex flex-col">
                                    <div class="p-2 border-b border-[var(--color-border)] flex items-center gap-2">
                                        <div class="relative flex-1">
                                            <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--color-text-dim)]" />
                                            <input type="text" x-model="item.search" placeholder="Pretraži..."
                                                   class="w-full bg-[var(--color-border)] border-none rounded-xl text-sm font-bold text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] pl-9 pr-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20">
                                        </div>
                                        <button type="button" x-on:click="item.open = false"
                                                class="md:hidden shrink-0 h-9 w-9 rounded-xl bg-[var(--color-border)] hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer text-[var(--color-text-dim)]">
                                            <x-icon name="x" class="h-4 w-4" />
                                        </button>
                                    </div>

                                    <div class="max-h-[calc(100vh-220px)] md:max-h-[240px] overflow-y-auto">
                                        <template x-if="! matches(item).length">
                                            <div class="p-4 text-center text-[var(--color-text-dim)] text-sm font-bold">Nema rezultata</div>
                                        </template>

                                        <template x-for="article in matches(item)" :key="article.id">
                                            <button type="button" x-on:click="pick(item, article)"
                                                    class="w-full text-left px-4 py-3 transition-all cursor-pointer"
                                                    :class="String(article.id) === String(item.article_id)
                                                        ? 'bg-primary/10 text-primary'
                                                        : 'text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)]'">
                                                <div class="flex flex-col gap-0.5">
                                                    <div class="flex items-center gap-2 flex-wrap">
                                                        <span class="text-sm font-bold" x-text="article.name"></span>
                                                        <template x-if="article.tax_label">
                                                            <span class="inline-flex px-2 py-0.5 rounded-md bg-primary/15 text-primary text-[10px] font-black uppercase tracking-wider shrink-0"
                                                                  x-text="'PDV ' + article.tax_label + ' (' + (article.tax_rate / 100) + '%)'"></span>
                                                        </template>
                                                    </div>
                                                    <template x-if="article.description">
                                                        <span class="text-[10px] text-[var(--color-text-dim)] truncate" x-text="article.description"></span>
                                                    </template>
                                                    <div class="flex gap-2 text-[10px]">
                                                        <span class="font-bold text-primary"
                                                              x-text="money(article.last_unit_price) + ' ' + currency"></span>
                                                    </div>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-row gap-2 md:flex-shrink-0 items-end">
                            <div class="w-24 flex flex-col gap-1">
                                <label class="text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1 shrink-0">Kol.</label>
                                <div class="relative">
                                    <div class="absolute left-2 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]">
                                        <x-icon name="boxes" class="h-3.5 w-3.5" />
                                    </div>
                                    <input type="text" inputmode="numeric" :value="item.quantity"
                                           x-on:input="typeQuantity(item, $event)" x-on:blur="fixQuantity(item, $event)"
                                           class="w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg text-[var(--color-text-main)] font-bold text-sm pl-8 pr-2 py-2 outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition-all">
                                </div>
                            </div>

                            <div class="flex-1 min-w-[90px] md:min-w-[100px] space-y-1.5">
                                <label class="block text-[11px] font-black uppercase tracking-[0.15em] text-[var(--color-text-muted)] ml-1">Cijena</label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]">
                                        <x-icon name="credit-card" class="h-4 w-4" />
                                    </div>
                                    <input type="text" inputmode="numeric" :value="money(item.unit_price)"
                                           x-on:input="typePrice(item, $event)" x-on:focus="$event.target.select()"
                                           class="w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] font-bold text-sm py-2.5 pr-12 pl-10 outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition-all text-right">
                                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[var(--color-text-dim)]" x-text="currency"></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                        <input type="text" :name="`items[${index}][name]`" x-model="item.name" placeholder="Naziv" required
                               class="md:col-span-1 w-full h-[44px] px-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg font-bold text-sm outline-none focus:border-primary/50">

                        <select :name="`items[${index}][unit]`" x-model="item.unit"
                                class="w-full h-[44px] px-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg font-bold text-sm outline-none focus:border-primary/50">
                            @foreach (\App\Enums\Unit::options() as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <select :name="`items[${index}][tax_label]`" x-model="item.tax_label" x-on:change="syncRate(item)"
                                class="w-full h-[44px] px-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-lg font-bold text-sm outline-none focus:border-primary/50">
                            <option value="">Bez poreza</option>
                            @foreach (\App\Models\TaxRate::orderBy('label')->get() as $taxRate)
                                <option value="{{ $taxRate->label }}">PDV {{ $taxRate->label }} ({{ $taxRate->rate }}%)</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Serveru cijena ide kao decimalni broj; u formi je feninzima. --}}
                    <input type="hidden" :name="`items[${index}][article_id]`" :value="item.article_id">
                    <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                    <input type="hidden" :name="`items[${index}][unit_price]`" :value="(item.unit_price / 100).toFixed(2)">

                    <div class="pt-1.5 border-t border-[var(--color-border)] flex justify-between items-center gap-2">
                        <div class="flex gap-2 text-[9px] text-[var(--color-text-dim)] flex-wrap">
                            <span>Osnovica: <strong class="text-[var(--color-text-main)]" x-text="money(lineBase(item))"></strong></span>
                            <span>PDV: <strong class="text-[var(--color-text-main)]" x-text="money(lineTax(item))"></strong></span>
                        </div>
                        <p class="text-base font-black text-primary tracking-tighter italic shrink-0"
                           x-text="money(lineTotal(item)) + ' ' + currency"></p>
                    </div>
                </div>
            </template>
        </div>

        <button type="button" x-on:click="addItem()"
                class="w-full py-3 rounded-xl border-2 border-dashed border-primary/30 bg-primary/5 text-primary font-black text-[11px] uppercase tracking-[0.15em] hover:bg-primary/10 transition-all flex items-center justify-center gap-2 cursor-pointer">
            <x-icon name="plus" class="h-4 w-4" /> Dodaj stavku
        </button>
    </x-section-block>

    <div class="p-3 bg-primary/10 border border-primary/20 rounded-2xl space-y-1.5">
        <div class="flex justify-between text-sm">
            <span class="text-[var(--color-text-dim)]">Osnovica:</span>
            <span class="font-bold text-[var(--color-text-main)]" x-text="money(subtotal()) + ' ' + currency"></span>
        </div>
        <div class="flex justify-between text-sm">
            <span class="text-[var(--color-text-dim)]">PDV:</span>
            <span class="font-bold text-[var(--color-text-main)]" x-text="money(taxTotal()) + ' ' + currency"></span>
        </div>
        <div class="h-[1px] bg-primary/20"></div>
        <div class="flex justify-between">
            <span class="text-sm font-bold text-[var(--color-text-main)]">Ukupno:</span>
            <span class="text-xl font-black text-primary tracking-tighter italic" x-text="money(total()) + ' ' + currency"></span>
        </div>
    </div>

    @error('items')
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
    @enderror

    <div class="flex flex-col gap-2 pt-2">
        <button type="submit"
                class="w-full py-3.5 bg-primary text-white rounded-xl font-black text-[11px] uppercase tracking-[0.2em] shadow-glow-primary hover:scale-[1.02] active:scale-95 transition-all flex items-center justify-center gap-2 cursor-pointer">
            {{ $isEdit ? 'Sačuvaj izmjene' : 'Kreiraj račun' }}
        </button>
        <x-drawer-secondary-button label="Odustani"
                                   x-on:click="window.location = @js($isEdit ? route('invoices.show', $invoice) : route('invoices.index'))" />
    </div>
</form>
