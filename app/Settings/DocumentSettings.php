<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Podrazumijevane vrijednosti na novim dokumentima. */
class DocumentSettings extends Settings
{
    /** classic | modern | minimal | standard | programmer */
    public string $template;

    /** Vrijednost iz App\Enums\DocumentLanguage. */
    public string $language;

    public int $invoice_due_days;

    public ?string $invoice_notes;

    public static function group(): string
    {
        return 'document';
    }
}
