<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    /**
     * Pet minuta je bilo prekratko.
     *
     * Kucanje u formi ne šalje zahtjeve, pa se brojač neaktivnosti ne osvježava —
     * zaključavanje je udaralo usred unosa računa. Mijenja se samo zatečena
     * vrijednost 5; ako je korisnik izabrao nešto drugo, ostaje njegovo.
     */
    public function up(): void
    {
        $this->migrator->update('security.auto_lock_minutes', fn ($minutes) => $minutes === 5 ? 15 : $minutes);
    }

    public function down(): void
    {
        $this->migrator->update('security.auto_lock_minutes', fn ($minutes) => $minutes === 15 ? 5 : $minutes);
    }
};
