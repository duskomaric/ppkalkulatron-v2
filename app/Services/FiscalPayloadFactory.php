<?php

namespace App\Services;

use App\Models\FiscalRecord;
use App\Models\Invoice;
use App\Models\TaxRate;
use App\Settings\FiscalSettings;
use RuntimeException;

class FiscalPayloadFactory
{
    public function __construct(
        private FiscalSettings $settings,
        private CurrencyConverter $converter,
        private Diagnostics $diagnostics,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function create(
        Invoice $invoice,
        string $transactionType,
        string $invoiceType,
        ?FiscalRecord $referent,
        ?string $buyerId,
    ): array {
        $items = $this->items($invoice);
        $layout = $this->settings->receipt_layout;

        $payload = [
            'print' => $this->settings->print_receipt,
            'renderReceiptImage' => true,
            'receiptImageFormat' => $this->documentFormat($layout),
            'receiptLayout' => $layout,
            'receiptHeaderTextLines' => $this->settings->receipt_header_text_lines,
            'invoiceRequest' => [
                'invoiceType' => $invoiceType,
                'transactionType' => $transactionType,
                'payment' => [[
                    'amount' => (float) array_sum(array_column($items, 'totalAmount')),
                    'paymentType' => $invoice->payment_type?->value ?: $this->settings->default_payment_type,
                ]],
                'items' => $items,
                'cashier' => $this->settings->cashier ?: 'Prodavac',
            ],
        ];

        if ($referent?->fiscal_invoice_number) {
            $payload['invoiceRequest']['referentDocumentNumber'] = $referent->fiscal_invoice_number;
            $payload['invoiceRequest']['referentDocumentDT'] = $referent->fiscalized_at?->format('c');
        }

        if ($buyerId !== null) {
            $payload['invoiceRequest']['buyerId'] = $buyerId;
        }

        return $payload;
    }

    /**
     * @return array<int, array{name: string, gtin: string, quantity: float, unitPrice: float, totalAmount: float, labels: array<int, string>}>
     */
    private function items(Invoice $invoice): array
    {
        $toBam = fn (int $pfening): int => $this->converter->toBam($pfening, $invoice->currency, $invoice->date);
        $zeroRateLabel = null;

        return $invoice->items->map(function ($item) use ($toBam, &$zeroRateLabel): array {
            $barcode = preg_replace('/\D/', '', (string) $item->article?->gtin);
            $gtin = strlen($barcode) >= 8
                ? substr($barcode, 0, 14)
                : substr(str_pad((string) ($item->article_id ?? $item->id), 8, '0', STR_PAD_LEFT), 0, 14);
            $total = abs($toBam($item->total));

            return [
                'name' => $item->name.' / '.$item->unit->value,
                'gtin' => $gtin,
                'quantity' => (float) $item->quantity,
                'unitPrice' => $total / max(1, (int) $item->quantity) / 100,
                'totalAmount' => $total / 100,
                'labels' => [$item->tax_label ?: ($zeroRateLabel ??= $this->zeroRateLabel())],
            ];
        })->all();
    }

    private function zeroRateLabel(): string
    {
        $label = TaxRate::query()->where('rate', 0)->orderBy('label')->value('label');

        if ($label === null) {
            throw new RuntimeException(
                'Stavka je bez poreske oznake, a uređaj ne prijavljuje nijednu nultu stopu. '.
                'Dodijelite poresku oznaku stavci prije fiskalizacije.'
            );
        }

        return $label;
    }

    private function documentFormat(string $layout): string
    {
        $format = $this->settings->receipt_document_format;
        $allowed = $this->settings->allowedDocumentFormats();

        if (in_array($format, $allowed, true)) {
            return $format;
        }

        $this->diagnostics->error('Format fiskalnog dokumenta nije podržan za raspored', [
            'layout' => $layout, 'configured' => $format, 'used' => $allowed[0],
        ]);

        return $allowed[0];
    }
}
