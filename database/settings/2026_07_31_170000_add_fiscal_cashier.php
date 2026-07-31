<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('fiscal.cashier', 'Prodavac');
    }

    public function down(): void
    {
        $this->migrator->delete('fiscal.cashier');
    }
};
