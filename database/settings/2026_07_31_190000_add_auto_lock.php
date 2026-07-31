<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('security.auto_lock_minutes', 15);
    }

    public function down(): void
    {
        $this->migrator->delete('security.auto_lock_minutes');
    }
};
