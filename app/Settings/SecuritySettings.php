<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Zaključavanje aplikacije. PIN se čuva kao hash; prazno znači da zaključavanja nema. */
class SecuritySettings extends Settings
{
    public ?string $pin_hash;

    public static function group(): string
    {
        return 'security';
    }
}
