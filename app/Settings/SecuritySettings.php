<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Zaključavanje aplikacije. PIN se čuva kao hash; prazno znači da zaključavanja nema. */
class SecuritySettings extends Settings
{
    public ?string $pin_hash;

    /** Minuti neaktivnosti poslije kojih se zaključava; 0 znači nikad. */
    public int $auto_lock_minutes;

    public static function group(): string
    {
        return 'security';
    }
}
