<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Str;
use RuntimeException;
use ZipStream\ZipStream;

class BackupArchive
{
    private const MAX_RAW_ATTACHMENT_BYTES = 20 * 1024 * 1024;

    public function __construct(
        private InvoicePdfService $pdf,
        private FiscalReceiptStore $receipts,
    ) {}

    /**
     * @return array{path: string, filename: string, invoice_count: int, fiscal_document_count: int, checksum: string}
     */
    public function create(array $contents = ['invoices' => true, 'fiscal_documents' => true, 'manifest' => true]): array
    {
        if (! self::zipAvailable()) {
            throw new RuntimeException('ZIP nije dostupan na ovom uređaju.');
        }

        $directory = storage_path('app/private/backups');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Nije moguće pripremiti direktorij za backup.');
        }

        $filename = 'kalkulatron-backup-'.now()->format('Y-m-d_His').'.zip';
        $path = $directory.'/'.$filename;
        $stream = @fopen($path, 'wb');

        if ($stream === false) {
            throw new RuntimeException('Nije moguće napraviti ZIP backup.');
        }

        $backup = $this->documents($contents);
        $archive = new ZipStream(
            outputStream: $stream,
            sendHttpHeaders: false,
            outputName: null,
        );

        try {
            foreach ($backup['attachments'] as $attachment) {
                $archive->addFile($attachment['path'], $attachment['contents']);
            }

            $archive->finish();
        } finally {
            fclose($stream);
        }

        if (! is_file($path)) {
            throw new RuntimeException('ZIP backup nije napravljen.');
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'invoice_count' => $backup['invoice_count'],
            'fiscal_document_count' => $backup['fiscal_document_count'],
            'checksum' => hash_file('sha256', $path),
        ];
    }

    public static function zipAvailable(): bool
    {
        return extension_loaded('mbstring') && extension_loaded('zlib');
    }

    /** @return array{attachments: array<int, array{name: string, mime: string, contents: string}>, invoice_count: int, fiscal_document_count: int, checksum: string} */
    public function raw(array $contents): array
    {
        $backup = $this->documents($contents);
        $attachments = [];
        $totalBytes = 0;

        foreach ($backup['attachments'] as $attachment) {
            $totalBytes += strlen($attachment['contents']);

            if ($totalBytes > self::MAX_RAW_ATTACHMENT_BYTES) {
                throw new RuntimeException('Pojedinačni prilozi su preveliki za sigurno slanje emailom. Odaberite ZIP format.');
            }

            $attachments[] = [
                'name' => basename($attachment['path']),
                'mime' => $attachment['mime'],
                'contents' => $attachment['contents'],
            ];
        }

        return [
            'attachments' => $attachments,
            'invoice_count' => $backup['invoice_count'],
            'fiscal_document_count' => $backup['fiscal_document_count'],
            'checksum' => hash('sha256', implode('', array_column($attachments, 'contents'))),
        ];
    }

    /**
     * @return array{attachments: array<int, array{path: string, mime: string, contents: string}>, invoice_count: int, fiscal_document_count: int}
     */
    private function documents(array $contents): array
    {
        $attachments = [];
        $invoiceCount = 0;
        $fiscalDocumentCount = 0;
        $manifest = new BackupManifest;

        Invoice::query()
            ->with(['client', 'items', 'fiscalRecords.receipt'])
            ->lazyById()
            ->each(function (Invoice $invoice) use ($contents, &$attachments, &$fiscalDocumentCount, &$invoiceCount, &$manifest): void {
                $number = $this->safeInvoiceNumber($invoice->invoice_number);

                if ($contents['invoices']) {
                    $attachments[] = [
                        'path' => 'racuni/'.$number.'.pdf',
                        'mime' => 'application/pdf',
                        'contents' => $this->pdf->contents($invoice),
                    ];
                    $invoiceCount++;
                }

                foreach ($invoice->fiscalRecords as $record) {
                    $extension = $this->receipts->extension($record);
                    $suffix = match ($record->type->value) {
                        'copy' => '-kopija',
                        'refund' => '-refundacija',
                        default => '-original',
                    };
                    $binary = $this->receipts->binary($record);

                    if ($binary !== null && $contents['fiscal_documents']) {
                        $attachments[] = [
                            'path' => 'fiskalni/'.$number.$suffix.'.'.$extension,
                            'mime' => $this->mimeForExtension($extension),
                            'contents' => $binary,
                        ];
                        $fiscalDocumentCount++;
                    }

                    if ($contents['manifest']) {
                        $manifest->add($invoice, $record, $binary === null || ! $contents['fiscal_documents'] ? null : $extension);
                    }
                }

                if ($contents['manifest'] && $invoice->fiscalRecords->isEmpty()) {
                    $manifest->add($invoice);
                }
            });

        if ($contents['manifest']) {
            $attachments[] = [
                'path' => 'manifest.csv',
                'mime' => 'text/csv; charset=UTF-8',
                'contents' => $manifest->csv(),
            ];
        }

        return [
            'attachments' => $attachments,
            'invoice_count' => $invoiceCount,
            'fiscal_document_count' => $fiscalDocumentCount,
        ];
    }

    private function safeInvoiceNumber(string $number): string
    {
        return Str::of($number)->replace('/', '-')->replaceMatches('/[^A-Za-z0-9._-]/', '-')->value();
    }

    private function mimeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'html' => 'text/html; charset=UTF-8',
            'csv' => 'text/csv; charset=UTF-8',
            default => 'image/png',
        };
    }
}
