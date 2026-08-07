<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** Stanje početnog podešavanja aplikacije. */
class SetupSettings extends Settings
{
    /** Korisnik je sam sklonio vodič, pa se više ne nameće na računima. */
    public bool $onboarding_dismissed;

    public static function group(): string
    {
        return 'setup';
    }
}
