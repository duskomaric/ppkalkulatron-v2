<?php

namespace App\Services;

use App\Models\FiscalReceiptImage;
use App\Models\FiscalRecord;
use Illuminate\Http\Response;

/**
 * Računi koje je fiskalni uređaj vratio.
 *
 * Preneseno iz v1, bez rezervnog čitanja sa diska: v2 nikad nije pisao račune u
 * datoteke, sve što postoji je u bazi.
 */
class FiscalReceiptStore
{
    /** Iz OFS odgovora izvuci sadržaj računa i njegovu ekstenziju. */
    public function extractFrom(array $responseData): ?array
    {
        $candidates = [
            'invoiceImagePngBase64' => 'png',
            'invoiceImagePdfBase64' => 'pdf',
            'invoiceImageHtmlBase64' => 'html',
        ];

        foreach ($candidates as $field => $extension) {
            $content = $responseData[$field] ?? null;

            if (is_string($content) && $content !== '') {
                $binary = base64_decode($content, true);

                if ($binary !== false && $binary !== '') {
                    return ['binary' => $binary, 'extension' => $extension];
                }
            }
        }

        foreach (['invoiceImageHtml', 'invoiceHtml', 'receiptHtml'] as $field) {
            $html = $responseData[$field] ?? null;

            if (is_string($html) && trim($html) !== '') {
                return ['binary' => $html, 'extension' => 'html'];
            }
        }

        return null;
    }

    /** Sačuvaj račun uz zapis; zamjenjuje raniji ako ga je bilo. */
    public function store(FiscalRecord $record, string $binary, string $extension = 'png'): FiscalReceiptImage
    {
        return FiscalReceiptImage::updateOrCreate(
            ['fiscal_record_id' => $record->id],
            ['extension' => $extension, 'contents' => base64_encode($binary)],
        );
    }

    public function has(FiscalRecord $record): bool
    {
        return $this->binary($record) !== null;
    }

    public function binary(FiscalRecord $record): ?string
    {
        $contents = $record->receiptImage?->contents;

        if (! $contents) {
            return null;
        }

        $binary = base64_decode($contents, true);

        return ($binary === false || $binary === '') ? null : $binary;
    }

    public function extension(FiscalRecord $record): string
    {
        return strtolower($record->receiptImage?->extension ?: 'png');
    }

    public function mime(FiscalRecord $record): string
    {
        return $this->mimeForExtension($this->extension($record));
    }

    public function mimeForExtension(string $extension): string
    {
        return match (strtolower($extension)) {
            'pdf' => 'application/pdf',
            'html', 'htm' => 'text/html; charset=UTF-8',
            default => 'image/png',
        };
    }

    /** Račun kakav preglednik može prikazati u modalu. */
    public function response(FiscalRecord $record): Response
    {
        $binary = $this->binary($record);

        abort_if($binary === null, 404, 'Slika fiskalnog računa nije dostupna.');

        return response($binary, 200, [
            'Content-Type' => $this->mime($record),
            'Content-Disposition' => 'inline; filename="fiskalni-racun-'.$record->id.'.'.$this->extension($record).'"',
        ]);
    }
}
