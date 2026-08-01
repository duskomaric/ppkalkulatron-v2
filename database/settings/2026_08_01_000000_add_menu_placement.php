<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Raspored menija: šta ide u donji meni, šta u drawer „Dokumenti“ i koliko
 * stavki meni najviše nosi.
 *
 * Ove dvije osobine moraju doći zasebnom migracijom, a ne dopunom one koja je
 * napravila `menu.menu_modules`. Migracije podešavanja se pamte po imenu fajla,
 * pa se već pokrenuta nikada ne pokreće ponovo — telefon sa 0.4.0 bi poslije
 * ažuriranja ostao bez ove dvije vrijednosti, a `MenuSettings` puca na prvoj
 * stranici jer navigaciju gradi na svakom zahtjevu.
 *
 * `exists()` je tu zbog baza koje su ovo već dobile dopunjenom migracijom, u
 * razvoju — bez provjere bi `add()` prijavio da osobina već postoji.
 */
return new class extends SettingsMigration
{
    private const DEFAULTS = [
        'menu.drawer_modules' => ['bank-accounts', 'currencies'],
        'menu.max_menu_items' => 4,
    ];

    public function up(): void
    {
        foreach (self::DEFAULTS as $property => $default) {
            if (! $this->migrator->exists($property)) {
                $this->migrator->add($property, $default);
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::DEFAULTS) as $property) {
            $this->migrator->deleteIfExists($property);
        }
    }
};
