<?php

use App\Services\TemporaryDemoBuildSettings;
use App\Settings\BackupSettings;
use App\Settings\CompanySettings;
use App\Settings\DiagnosticsSettings;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;
use App\Settings\MenuSettings;
use App\Settings\NumberingSettings;
use App\Settings\SecuritySettings;
use App\Settings\UserSettings;

/**
 * Svojstvo u settings klasi bez svoje vrijednosti u bazi ruši aplikaciju čim je neko
 * učita — najprije demo migraciju na `migrate:fresh`. Ovo to hvata odmah.
 */
it('svaka settings klasa ima sve svoje vrijednosti u bazi', function (string $class): void {
    $settings = app($class);

    foreach ((new ReflectionClass($class))->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
        expect(fn () => $settings->{$property->getName()})->not->toThrow(Throwable::class);
    }
})->with([
    BackupSettings::class,
    CompanySettings::class,
    DiagnosticsSettings::class,
    DocumentSettings::class,
    FiscalSettings::class,
    MailSettings::class,
    MenuSettings::class,
    NumberingSettings::class,
    SecuritySettings::class,
    UserSettings::class,
]);

it('demo podešavanja se upišu na svježoj bazi', function (): void {
    // Migracija koja ovo radi preskače testove, pa se poziva direktno — kao na uređaju.
    expect(app(TemporaryDemoBuildSettings::class)->seedIfPristine())->toBeTrue()
        ->and(app(FiscalSettings::class)->base_url)->not->toBeEmpty();
});
