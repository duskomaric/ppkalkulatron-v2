<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use Illuminate\Support\Collection;

class InvoiceFormData
{
    public function __construct(
        private DocumentSettings $documents,
        private FiscalSettings $fiscalSettings,
        private CurrencyConverter $converter,
    ) {}

    /**
     * Kurs koji važi na dan računa, po valuti.
     *
     * @param  Collection<int, Currency>  $currencies
     * @return array<string, string>
     */
    private function rates($currencies, $date): array
    {
        $date = $date ?: now();

        return $currencies
            ->where('is_default', false)
            ->mapWithKeys(fn (Currency $currency): array => [
                $currency->code => $this->converter->rateFor($currency->code, $date),
            ])
            ->filter()
            ->map(fn ($rate): string => (string) $rate)
            ->all();
    }

    /** @return array<string, mixed> */
    public function for(?Invoice $invoice = null): array
    {
        $currencies = Currency::orderByDesc('is_default')->orderBy('code')->get(['code', 'name', 'symbol', 'is_default']);

        return [
            'invoice' => $invoice,
            'clients' => Client::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'email', 'phone']),
            'articles' => Article::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'description', 'unit', 'tax_label', 'last_unit_price']),
            'currencies' => $currencies,
            'currencySymbols' => $currencies->pluck('symbol', 'code')->all(),
            // Kursevi na datum računa: forma uz iznos u stranoj valuti pokazuje i KM.
            'exchangeRates' => $this->rates($currencies, $invoice?->date),
            'defaultLanguage' => $this->documents->language,
            'defaultCurrency' => Currency::where('is_default', true)->value('code') ?? 'BAM',
            'defaultDueDays' => $this->documents->invoice_due_days,
            'defaultNotes' => $this->documents->invoice_notes,
            'defaultPaymentType' => $this->fiscalSettings->default_payment_type,
        ];
    }
}
