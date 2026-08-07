<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('setup.onboarding_dismissed', false);
    }

    public function down(): void
    {
        $this->migrator->delete('setup.onboarding_dismissed');
    }
};
