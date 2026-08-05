<?php

use App\Services\TemporaryDemoBuildSettings;
use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Nove vrijednosti podešavanja moraju imati raniji datum od ove migracije: ona
 * učitava cijele settings klase, pa vrijednost koja još nije upisana ruši migraciju.
 */
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
