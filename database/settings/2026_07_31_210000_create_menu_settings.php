<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('menu.menu_modules', ['invoices', 'clients', 'articles']);
    }

    public function down(): void
    {
        $this->migrator->delete('menu.menu_modules');
    }
};
