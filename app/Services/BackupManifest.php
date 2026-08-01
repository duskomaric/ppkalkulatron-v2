<?php

namespace App\Services;

use App\Models\FiscalRecord;
use App\Models\Invoice;

class BackupManifest
{
    /** @var array<int, array<int, string>> */
    private array $rows = [[
        'broj_racuna', 'datum', 'kupac', 'iznos_km', 'status', 'fiskalni_broj', 'tip_fiskalnog_dokumenta', 'format', 'url_za_provjeru',
    ]];

    public function add(Invoice $invoice, ?FiscalRecord $record = null, ?string $extension = null): void
    {
        $this->rows[] = [
            $invoice->invoice_number,
            $invoice->date->format('Y-m-d'),
            $invoice->client?->name ?? '',
            number_format($invoice->total / 100, 2, '.', ''),
            $invoice->status->value,
            $record?->fiscal_invoice_number ?? '',
            $record?->type->value ?? '',
            $extension ?? '',
            $record?->verification_url ?? '',
        ];
    }

    public function csv(): string
    {
        $stream = fopen('php://temp', 'r+');

        foreach ($this->rows as $row) {
            fputcsv($stream, $row, ';', '"', '');
        }

        rewind($stream);
        $contents = stream_get_contents($stream) ?: '';
        fclose($stream);

        return "\xEF\xBB\xBF".$contents;
    }
}
