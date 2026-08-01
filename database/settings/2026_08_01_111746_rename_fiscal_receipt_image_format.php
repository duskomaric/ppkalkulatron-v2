<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->rename('fiscal.receipt_image_format', 'fiscal.receipt_document_format');
    }

    public function down(): void
    {
        $this->migrator->rename('fiscal.receipt_document_format', 'fiscal.receipt_image_format');
    }
};
