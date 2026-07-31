<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('user.first_name', 'Korisnik');
        $this->migrator->add('user.last_name', '');
        $this->migrator->add('user.email', null);
    }

    public function down(): void
    {
        $this->migrator->delete('user.first_name');
        $this->migrator->delete('user.last_name');
        $this->migrator->delete('user.email');
    }
};
