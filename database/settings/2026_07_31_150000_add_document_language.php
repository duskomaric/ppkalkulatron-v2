<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('document.language', 'sr_Latn');
    }

    public function down(): void
    {
        $this->migrator->delete('document.language');
    }
};
