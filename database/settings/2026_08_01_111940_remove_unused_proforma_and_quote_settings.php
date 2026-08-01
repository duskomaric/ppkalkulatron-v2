<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach ([
            'numbering.proforma_prefix',
            'numbering.proforma_starting_number',
            'numbering.quote_prefix',
            'numbering.quote_starting_number',
            'document.proforma_due_days',
            'document.quote_valid_days',
            'document.proforma_notes',
            'document.quote_notes',
        ] as $setting) {
            $this->migrator->deleteIfExists($setting);
        }
    }
};
