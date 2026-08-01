<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('diagnostics.email', null);
        $this->migrator->add('diagnostics.detailed_until', null);
        $this->migrator->add('diagnostics.last_sent_at', null);
    }
};
