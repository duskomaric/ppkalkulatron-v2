<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;

class InvoiceFormData
{
    public function __construct(
        private DocumentSettings $documents,
        private FiscalSettings $fiscalSettings,
    ) {}

    /** @return array<string, mixed> */
    public function for(?Invoice $invoice = null): array
    {
        $currencies = Currency::orderByDesc('is_default')->orderBy('code')->get(['code', 'name', 'symbol']);

        return [
            'invoice' => $invoice,
            'clients' => Client::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'email', 'phone']),
            'articles' => Article::where('is_active', true)->orderBy('name')
                ->get(['id', 'name', 'description', 'unit', 'tax_label', 'last_unit_price']),
            'currencies' => $currencies,
            'currencySymbols' => $currencies->pluck('symbol', 'code')->all(),
            'defaultLanguage' => $this->documents->language,
            'defaultCurrency' => Currency::where('is_default', true)->value('code') ?? 'BAM',
            'defaultDueDays' => $this->documents->invoice_due_days,
            'defaultNotes' => $this->documents->invoice_notes,
            'defaultPaymentType' => $this->fiscalSettings->default_payment_type,
        ];
    }
}
