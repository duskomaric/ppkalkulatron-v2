<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Ranije je ova migracija punila aplikaciju demo podacima internog builda.
 *
 * Radila je i pri resetu, jer `migrate:fresh` ponovo pokreće settings migracije —
 * pa se demo firma, testna kasa i PIN vraćali baš kad ih korisnik briše. Sada je
 * to svjestan potez, dugmetom u Podešavanja → Backup aplikacije.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
