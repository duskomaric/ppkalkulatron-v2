<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** OFS fiskalni uređaj — cloud ili lokalni ESIR. */
class FiscalSettings extends Settings
{
    public string $base_url;

    public ?string $api_key;

    public ?string $serial_number;

    public ?string $pac;

    /** cloud | local */
    public string $device_mode;

    /** Veleprodaja: buyerId dobija prefiks "VP:". */
    public bool $wholesale;

    /** Slip = termalni, Invoice = A4. */
    public string $receipt_layout;

    /** Png | Pdf | Html. Invoice layout ne podržava PNG. */
    public string $receipt_document_format;

    public bool $print_receipt;

    public array $receipt_header_text_lines;

    public string $default_payment_type;

    /** Ime operatera koje se šalje fiskalnom uređaju. */
    public string $cashier;

    public static function group(): string
    {
        return 'fiscal';
    }

    /** Formati koje layout stvarno ume da iscrta. */
    public function allowedDocumentFormats(): array
    {
        return $this->receipt_layout === 'Invoice' ? ['Pdf', 'Html'] : ['Png', 'Pdf', 'Html'];
    }
}
