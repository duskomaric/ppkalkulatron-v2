<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Podaci o korisniku aplikacije.
 *
 * v2 nema naloge — prijava je samo PIN — pa ono što v1 drži na korisničkom nalogu
 * ovdje stoji u podešavanjima. Koristi se za pozdrav u zaglavlju i za ime
 * blagajnika kad ono nije posebno podešeno.
 */
class UserSettings extends Settings
{
    public string $first_name;

    public string $last_name;

    public ?string $email;

    public static function group(): string
    {
        return 'user';
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function initials(): string
    {
        $initials = mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1);

        return mb_strtoupper(trim($initials)) ?: 'PK';
    }
}
