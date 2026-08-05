<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // Boja se bira u Podešavanja → Izgled i navigacija; podrazumijevana je amber.
        $this->migrator->add('menu.primary_color', '#F59E0B');
    }

    public function down(): void
    {
        $this->migrator->delete('menu.primary_color');
    }
};
