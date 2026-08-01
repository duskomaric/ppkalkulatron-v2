<?php

namespace App\Settings;

use Illuminate\Support\Carbon;
use Spatie\LaravelSettings\Settings;

class BackupSettings extends Settings
{
    public ?string $email;

    public ?Carbon $last_backup_at;

    public int $last_backup_invoice_count;

    public int $last_backup_fiscal_document_count;

    public ?string $last_backup_checksum;

    public static function group(): string
    {
        return 'backup';
    }
}
