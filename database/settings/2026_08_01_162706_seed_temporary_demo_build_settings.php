<?php

use App\Services\TemporaryDemoBuildSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        app(TemporaryDemoBuildSettings::class)->seedIfPristine();
    }

    public function down(): void
    {
        // Ne brišemo podatke: korisnik ih je mogao izmijeniti nakon prvog pokretanja.
    }
};
