@props([
    'invoice' => null,
    'clients',
    'articles',
    'currencies',
    'defaultTemplate' => 'classic',
    'defaultLanguage' => 'sr_Latn',
    'defaultCurrency' => 'BAM',
    'defaultDueDays' => 15,
    'defaultNotes' => null,
    'defaultPaymentType' => 'Cash',
])

@php
    $isEdit = $invoice !== null;
    $taxRates = \App\Models\FiscalTaxRate::basisPointsByLabel();

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

    // Poslije greške u validaciji cijena se vraća decimalno, a forma radi u fenizima.
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

    $clientOptions = $clients->map(fn ($client) => [
        'id' => $client->id,
        'name' => $client->name,
        'email' => $client->email,
        'phone' => $client->phone,
    ])->values();

    $currencyCode = old('currency', $invoice?->currency ?? $defaultCurrency);
@endphp

<form method="POST" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}"
      class="space-y-3"
      x-data="invoiceForm({
          items: @js($oldItems),
          articles: @js($articleOptions),
          clients: @js($clientOptions),
          taxRates: @js($taxRates),
          currency: @js($currencyCode),
          clientId: @js(old('client_id', $invoice?->client_id)),
          showMore: @js($errors->any()),
      })">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-form-errors />

    <x-section-block variant="card">
        <x-section-header icon="contact" title="Osnovni podaci" :help="route('help').'#racuni'" />

        {{-- Klijent se bira pretragom po nazivu, emailu ili telefonu. --}}
        <div class="space-y-1.5">
            <x-field-label required>Klijent</x-field-label>

            <div class="space-y-1.5 w-full" x-on:click.outside="clientOpen = false">
                <div class="relative">
                    <div x-on:click="clientOpen = ! clientOpen"
                         class="relative w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border rounded-xl text-left pl-10 pr-4 flex items-center justify-between gap-2 transition-all duration-300 cursor-pointer hover:border-[var(--color-border-strong)] @error('client_id') border-red-500/50 ring-red-500/10 @else border-[var(--color-border)] @enderror"
                         :class="clientOpen && 'border-primary/50 ring-2 ring-primary/10 bg-[var(--color-surface-hover)]'">
                        <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
                            <x-icon name="contact" class="h-4 w-4" />
                        </div>
                        <span class="text-sm font-bold truncate flex-1 min-w-0"
                              :class="selectedClient() ? 'text-[var(--color-text-main)]' : 'text-[var(--color-text-dim)]'"
                              x-text="selectedClient() ? selectedClient().name : 'Odaberi klijenta...'"></span>
                        <div class="flex items-center gap-1 shrink-0">
                            <button type="button" x-show="clientId" x-cloak x-on:click.stop="clientId = ''"
                                    aria-label="Ukloni klijenta"
                                    class="h-11 w-11 -mr-3 rounded-full hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer">
                                <x-icon name="x" class="h-3 w-3" />
                            </button>
                            <x-icon name="chevron-down" class="h-4 w-4 text-[var(--color-text-dim)] transition-transform"
                                    ::class="clientOpen && 'rotate-180'" />
                        </div>
                    </div>

                    <div x-show="clientOpen" x-cloak
                         class="fixed z-[1100] left-3 right-3 bottom-3 top-24 md:absolute md:z-50 md:top-full md:left-0 md:right-0 md:bottom-auto md:mt-2 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl shadow-2xl overflow-hidden animate-fade-in flex flex-col">
                        <div class="p-2 border-b border-[var(--color-border)] flex items-center gap-2">
                            <div class="relative flex-1">
                                <x-icon name="search" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--color-text-dim)]" />
                                <input type="text" x-model="clientSearch" placeholder="Pretraži..."
                                       class="w-full bg-[var(--color-border)] border-none rounded-xl text-sm font-bold text-[var(--color-text-main)] placeholder:text-[var(--color-text-dim)] pl-9 pr-4 py-2.5 outline-none focus:ring-2 focus:ring-primary/20">
                            </div>
                            <button type="button" x-on:click="clientOpen = false"
                                    class="md:hidden shrink-0 h-9 w-9 rounded-xl bg-[var(--color-border)] hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer text-[var(--color-text-dim)]">
                                <x-icon name="x" class="h-4 w-4" />
                            </button>
                        </div>

                        <div class="max-h-[calc(100vh-220px)] md:max-h-[240px] overflow-y-auto">
                            <template x-if="! matchingClients().length">
                                <div class="p-4 text-center text-[var(--color-text-dim)] text-sm font-bold">Nema rezultata</div>
                            </template>

                            <template x-for="client in matchingClients()" :key="client.id">
                                <button type="button" x-on:click="pickClient(client)"
                                        class="w-full text-left px-4 py-3 transition-all cursor-pointer"
                                        :class="String(client.id) === String(clientId)
                                            ? 'bg-primary/10 text-primary'
                                            : 'text-[var(--color-text-main)] hover:bg-[var(--color-surface-hover)]'">
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-sm font-bold" x-text="client.name"></span>
                                        <template x-if="client.email">
                                            <span class="text-[10px] text-[var(--color-text-dim)]" x-text="client.email"></span>
                                        </template>
                                    </div>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="client_id" :value="clientId">

            @error('client_id')
                <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 items-start">
            <x-form-input label="Datum" name="date" type="date" icon="calendar" required compact
                          :value="$invoice?->date->format('Y-m-d') ?? now()->format('Y-m-d')" />

            <x-form-input label="Dospijeće" name="due_date" type="date" icon="clock" required compact
                          :value="$invoice?->due_date->format('Y-m-d') ?? now()->addDays($defaultDueDays)->format('Y-m-d')" />

            <x-form-select label="Način plaćanja" name="payment_type" icon="credit-card" compact :show-placeholder="false"
                            :value="$invoice?->payment_type->value ?? $defaultPaymentType"
                            :options="\App\Enums\PaymentType::options()" />
        </div>
    </x-section-block>

    <x-section-block variant="accent">
        <x-section-toggle title="Dodatna polja" subtitle="Valuta, predložak, jezik i napomena" open="showMore" :help="route('help').'#racuni'" />

        <div x-show="showMore" x-cloak
             class="space-y-3 pt-3 mt-2 border-t-2 border-dashed border-[var(--color-page-border-subtle)]">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 items-start">
                <x-form-select label="Valuta" name="currency" icon="credit-card" :show-placeholder="false" :value="$currencyCode"
                                x-on:change="currency = $event.target.value"
                                :options="$currencies->pluck('code', 'code')->all()" />

                <x-form-select label="Jezik" name="language" icon="globe" :show-placeholder="false"
                                :value="$invoice?->language?->value ?? $defaultLanguage"
                                :options="\App\Enums\DocumentLanguage::options()" />

                <x-form-select label="Predložak" name="template" icon="file-text" :show-placeholder="false"
                               :value="$invoice?->template?->value ?? $defaultTemplate"
                               :options="\App\Enums\DocumentTemplate::options()" />
            </div>

            <div class="space-y-1.5">
                <x-field-label for="notes">Napomena</x-field-label>
                <textarea id="notes" name="notes" rows="3" placeholder="Dodatne napomene..." aria-describedby="notes-help"
                          class="w-full rounded-xl border border-[var(--color-border)] bg-[var(--color-surface)] px-4 py-3 text-sm font-bold text-[var(--color-text-main)] outline-none transition-all placeholder:text-[var(--color-text-dim)] focus:border-primary focus:ring-1 focus:ring-primary resize-none">{{ old('notes', $invoice?->notes ?? ($isEdit ? null : $defaultNotes)) }}</textarea>

                @if (! $isEdit)
                    <p id="notes-help" class="text-xs font-medium text-[var(--color-text-dim)]">
                        @if (filled($defaultNotes))
                            Zadana napomena iz Podešavanja je unesena iznad i možete je izmijeniti samo za ovaj račun.
                        @else
                            Zadana napomena nije postavljena. <a href="{{ route('settings.general.edit') }}" class="font-bold text-primary hover:underline">Postavite je u Podešavanjima</a> za sljedeće račune.
                        @endif
                    </p>
                @endif

                @error('notes')
                    <p class="text-[11px] font-bold text-[var(--color-error)]">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-section-block>

    <x-section-block variant="card">
        <x-section-header icon="boxes" title="Stavke" :help="route('help').'#racuni'" />

        <template x-for="(item, index) in items" :key="index">
            <div class="bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl p-3 space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[9px] font-black uppercase tracking-widest text-[var(--color-text-dim)]"
                          x-text="'#' + (index + 1)"></span>
                    <button type="button" x-on:click="removeItem(index)" aria-label="Ukloni stavku"
                            class="h-11 w-11 rounded-lg bg-red-500/10 text-red-500 flex items-center justify-center hover:bg-red-500 hover:text-white transition-all cursor-pointer">
                        <x-icon name="trash" class="h-3.5 w-3.5" />
                    </button>
                </div>

                <div class="flex flex-col md:flex-row md:items-end gap-2">
                    <div class="flex-1 min-w-0 space-y-1.5 w-full" x-on:click.outside="item.open = false">
                        <div class="relative">
                            <div x-on:click="item.open = ! item.open"
                                 class="relative w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-left pl-10 pr-4 flex items-center justify-between gap-2 transition-all duration-300 cursor-pointer hover:border-[var(--color-border-strong)]"
                                 :class="item.open && 'border-primary/50 ring-2 ring-primary/10 bg-[var(--color-surface-hover)]'">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)] pointer-events-none">
                                    <x-icon name="boxes" class="h-4 w-4" />
                                </div>
                                <span class="text-sm font-bold truncate flex-1 min-w-0"
                                      :class="selectedArticle(item) ? 'text-[var(--color-text-main)]' : 'text-[var(--color-text-dim)]'"
                                      x-text="selectedArticle(item) ? selectedArticle(item).name : 'Odaberi artikal...'"></span>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button type="button" x-show="item.article_id" x-cloak x-on:click.stop="clearArticle(item)"
                                            aria-label="Ukloni artikal"
                                            class="h-11 w-11 -mr-3 rounded-full hover:bg-red-500/20 hover:text-red-500 flex items-center justify-center transition-all cursor-pointer">
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
                                    <template x-if="! matchingArticles(item).length">
                                        <div class="p-4 text-center text-[var(--color-text-dim)] text-sm font-bold">Nema rezultata</div>
                                    </template>

                                    <template x-for="article in matchingArticles(item)" :key="article.id">
                                        <button type="button" x-on:click="pickArticle(item, article)"
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
                        <div class="w-24 flex flex-col gap-1 group">
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
                            <x-field-label>Cijena</x-field-label>
                            <div class="relative">
                                <div class="absolute left-3 top-1/2 -translate-y-1/2 text-[var(--color-text-dim)]">
                                    <x-icon name="currency-euro" class="h-4 w-4" />
                                </div>
                                <input type="text" inputmode="numeric" :value="money(item.unit_price)"
                                       x-on:input="typePrice(item, $event)" x-on:focus="$event.target.select()"
                                       class="w-full h-[44px] min-h-[44px] bg-[var(--color-surface)] border border-[var(--color-border)] rounded-xl text-[var(--color-text-main)] font-bold text-sm py-2.5 pr-12 pl-10 outline-none focus:border-primary/50 focus:ring-2 focus:ring-primary/10 transition-all text-right">
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-xs font-bold text-[var(--color-text-dim)]" x-text="currency"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Naziv, jedinica i porez idu sa artikla; server ih traži, korisnik ih ne unosi. --}}
                <input type="hidden" :name="`items[${index}][article_id]`" :value="item.article_id">
                <input type="hidden" :name="`items[${index}][name]`" :value="item.name">
                <input type="hidden" :name="`items[${index}][description]`" :value="item.description">
                <input type="hidden" :name="`items[${index}][unit]`" :value="item.unit">
                <input type="hidden" :name="`items[${index}][tax_label]`" :value="item.tax_label">
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

        <button type="button" x-on:click="addItem()"
                class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl border border-dashed border-primary/40 bg-primary/5 text-primary hover:border-primary/60 hover:bg-primary/10 transition-colors cursor-pointer">
            <x-icon name="plus" class="h-4 w-4" />
            <span class="text-[11px] font-bold">Dodaj stavku</span>
        </button>
    </x-section-block>

    @error('items')
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">{{ $message }}</p>
    @enderror
    @error('items.0.name')
        <p class="text-[11px] font-bold text-[var(--color-error)] ml-1">Svaka stavka mora imati odabran artikal.</p>
    @enderror

    <div class="flex items-start gap-3 p-3 bg-primary/5 border border-primary/20 rounded-2xl">
        <div class="h-9 w-9 bg-primary/10 text-primary rounded-lg flex items-center justify-center shrink-0">
            <x-icon name="file-text" class="h-5 w-5" />
        </div>
        <div class="flex-1 min-w-0 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">Osnovica:</span>
                <span class="font-bold text-[var(--color-text-main)]" x-text="money(subtotal()) + ' ' + currency"></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-[var(--color-text-dim)]">PDV:</span>
                <span class="font-bold text-[var(--color-text-main)]" x-text="money(taxTotal()) + ' ' + currency"></span>
            </div>
            <div class="h-[1px] bg-primary/20"></div>
            <div class="flex justify-between items-center">
                <span class="text-sm font-bold text-[var(--color-text-main)]">Ukupno</span>
                <span class="text-xl font-black text-primary tracking-tighter italic" x-text="money(total()) + ' ' + currency"></span>
            </div>
        </div>
    </div>

    <x-form-actions :label="$isEdit ? 'Sačuvaj izmjene' : 'Kreiraj račun'"
                    :cancel="$isEdit ? route('invoices.show', $invoice) : route('invoices.index')" />
</form>
