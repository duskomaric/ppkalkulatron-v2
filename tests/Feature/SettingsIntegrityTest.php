<?php

use App\Models\Article;
use App\Models\Client;
use App\Services\SetupProgress;
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

it('demo podaci popune sve korake početnog podešavanja', function (): void {
    unlocked()->post(route('setup.demo'))->assertSessionHas('status');

    $setup = app(SetupProgress::class);
    $steps = collect($setup->steps())->keyBy('key');

    expect(app(CompanySettings::class)->city)->toBe('Banja Luka')
        ->and(app(FiscalSettings::class)->base_url)->toBe('https://pos.ofs.ba')
        ->and($steps['device']['done'])->toBeTrue()
        ->and($steps['company']['done'])->toBeTrue()
        ->and($steps['bank_account']['done'])->toBeTrue()
        ->and($steps['article']['done'])->toBeTrue()
        ->and($steps['client']['done'])->toBeTrue()
        // Stope javlja sama kasa, pa taj korak ostaje na korisniku.
        ->and(Article::count())->toBe(4)
        ->and(Client::count())->toBe(3);
});

it('demo podaci ne prepisuju zatečeno stanje', function (): void {
    unlocked()->post(route('setup.demo'))->assertSessionHas('status');

    unlocked()->post(route('setup.demo'))
        ->assertSessionHas('error', 'Demo podaci se upisuju samo u praznu aplikaciju. Prvo uradite reset.');
});
