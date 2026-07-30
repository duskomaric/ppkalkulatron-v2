@props(['invoice' => null, 'clients', 'articles'])

{{--
    Cijela forma računa u jednoj komponenti: podaci, stavke i napomena.
    Stavke se dodaju i računaju u pregledniku — server na kraju sve preračuna iz cijena,
    pa ono što dođe iz forme nije izvor istine za iznose.
--}}

@php
    $isEdit = $invoice !== null;
    $oldItems = old('items', $isEdit
        ? $invoice->items->map(fn ($item) => [
            'article_id' => $item->article_id,
            'name' => $item->name,
            'unit' => $item->unit->value,
            'tax_label' => $item->tax_label,
            'quantity' => $item->quantity,
            'unit_price' => number_format($item->unit_price / 100, 2, '.', ''),
        ])->all()
        : [['article_id' => '', 'name' => '', 'unit' => 'kom', 'tax_label' => '', 'quantity' => 1, 'unit_price' => '']]);
@endphp

<form method="POST" action="{{ $isEdit ? route('invoices.update', $invoice) : route('invoices.store') }}"
      class="space-y-5" x-data="invoiceForm(@js($oldItems), @js($articles))">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <x-section title="Podaci" icon="file-text">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-field label="Klijent" name="client_id" :value="$invoice?->client_id"
                     :options="['' => '— bez klijenta —'] + $clients->pluck('name', 'id')->all()" />

            <x-field label="Način plaćanja" name="payment_type"
                     :value="$invoice?->payment_type->value ?? 'Cash'"
                     :options="\App\Enums\PaymentType::options()" required />

            <x-field label="Datum" name="date" type="date"
                     :value="$invoice?->date->format('Y-m-d') ?? now()->format('Y-m-d')" required />

            <x-field label="Rok dospijeća" name="due_date" type="date"
                     :value="$invoice?->due_date->format('Y-m-d') ?? now()->addDays(15)->format('Y-m-d')" required />
        </div>
    </x-section>

    <x-section title="Stavke" icon="box">
        <div class="space-y-3">
            <template x-for="(item, index) in items" :key="index">
                <div class="p-4 rounded-xl border border-[var(--color-border)] bg-[var(--color-bg)]/40 space-y-3">
                    <div class="flex items-start gap-3">
                        <div class="grow space-y-3">
                            <select :name="`items[${index}][article_id]`" x-model="item.article_id" @change="pickArticle(index)"
                                    class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                                <option value="">— slobodan unos —</option>
                                <template x-for="article in articles" :key="article.id">
                                    <option :value="article.id" x-text="article.name"></option>
                                </template>
                            </select>

                            <input type="text" :name="`items[${index}][name]`" x-model="item.name" placeholder="Naziv" required
                                   class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                        </div>

                        <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                class="h-10 w-10 shrink-0 rounded-xl border border-[var(--color-error)]/30 text-[var(--color-error)] flex items-center justify-center cursor-pointer">
                            <x-icon name="trash" class="h-4 w-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] ml-1">Količina</label>
                            <input type="number" min="1" step="1" :name="`items[${index}][quantity]`" x-model.number="item.quantity" required
                                   class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] ml-1">Cijena</label>
                            <input type="number" min="0" step="0.01" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" required
                                   class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] ml-1">JM</label>
                            <select :name="`items[${index}][unit]`" x-model="item.unit"
                                    class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                                @foreach (\App\Enums\Unit::options() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)] ml-1">Porez</label>
                            <select :name="`items[${index}][tax_label]`" x-model="item.tax_label"
                                    class="w-full px-4 py-3 bg-[var(--color-surface)] border border-[var(--color-border)] rounded-2xl font-bold text-sm outline-none focus:border-primary/50">
                                <option value="">—</option>
                                @foreach (config('ofs.tax_labels') as $label => $rate)
                                    <option value="{{ $label }}">{{ $label }} — {{ $rate / 100 }}%</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <p class="text-right text-xs font-bold text-[var(--color-text-muted)]">
                        Ukupno: <span class="text-sm font-black italic text-[var(--color-text-main)]" x-text="money(lineTotal(item))"></span> KM
                    </p>
                </div>
            </template>
        </div>

        <x-button type="button" variant="ghost" @click="addItem()" class="w-full">
            <x-icon name="plus" class="h-4 w-4" /> Dodaj stavku
        </x-button>

        <div class="pt-3 border-t border-[var(--color-border)] flex justify-between items-end">
            <span class="text-[10px] font-black uppercase tracking-[0.15em] text-[var(--color-text-dim)]">Ukupno za plaćanje</span>
            <span class="text-2xl font-black italic tracking-tighter"><span x-text="money(total())"></span> KM</span>
        </div>
    </x-section>

    <x-section title="Napomena" icon="sticky-note">
        <x-field label="Napomena" name="notes" rows="3" :value="$invoice?->notes" />
    </x-section>

    <div class="flex gap-3">
        <x-button variant="primary" class="grow">{{ $isEdit ? 'Sačuvaj izmjene' : 'Kreiraj račun' }}</x-button>
        <x-button variant="ghost" :href="$isEdit ? route('invoices.show', $invoice) : route('invoices.index')">Odustani</x-button>
    </div>
</form>
