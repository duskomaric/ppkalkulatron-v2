<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('company.is_small_entrepreneur', false);
        $this->migrator->add('company.small_entrepreneur_note', 'Mali preduzetnik — nije u sistemu PDV-a.');
    }

    public function down(): void
    {
        $this->migrator->delete('company.is_small_entrepreneur');
        $this->migrator->delete('company.small_entrepreneur_note');
    }
};
