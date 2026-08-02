<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Podrazumijevane vrijednosti na novim dokumentima. */
class DocumentSettings extends Settings
{
    /** Vrijednost je jedan od App\Enums\DocumentTemplate predložaka. */
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
