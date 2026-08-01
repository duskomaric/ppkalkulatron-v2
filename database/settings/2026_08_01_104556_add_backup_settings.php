<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('backup.email', null);
        $this->migrator->add('backup.last_backup_at', null);
        $this->migrator->add('backup.last_backup_invoice_count', 0);
        $this->migrator->add('backup.last_backup_fiscal_document_count', 0);
        $this->migrator->add('backup.last_backup_checksum', null);
    }
};
