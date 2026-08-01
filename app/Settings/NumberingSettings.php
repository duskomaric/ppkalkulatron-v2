<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Numeracija dokumenata.
 *
 * Sljedeći broj se izvodi iz samih dokumenata, ne iz brojača — zato ovdje nema
 * `last_number`.
 */
class NumberingSettings extends Settings
{
    public bool $reset_yearly;

    public int $pad_zeros;

    public string $invoice_prefix;

    public int $invoice_starting_number;

    public static function group(): string
    {
        return 'numbering';
    }
}
