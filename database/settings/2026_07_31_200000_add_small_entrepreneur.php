<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.is_small_entrepreneur', false);
    }

    public function down(): void
    {
        $this->migrator->delete('company.is_small_entrepreneur');
    }
};
