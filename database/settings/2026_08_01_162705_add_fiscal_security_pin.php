<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        // PIN sigurnosnog elementa stoji uz ostale podatke kase, da ga aplikacija
        // može sama poslati kad uređaj zatraži otključavanje.
        $this->migrator->add('fiscal.security_pin', null);
    }

    public function down(): void
    {
        $this->migrator->delete('fiscal.security_pin');
    }
};
