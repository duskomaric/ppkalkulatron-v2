<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Podrazumijevane vrijednosti na novim dokumentima. */
class DocumentSettings extends Settings
{
    /** classic | modern | minimal | standard */
    public string $template;

    public int $invoice_due_days;

    public int $proforma_due_days;

    public int $quote_valid_days;

    public ?string $invoice_notes;

    public ?string $proforma_notes;

    public ?string $quote_notes;

    public static function group(): string
    {
        return 'document';
    }
}
