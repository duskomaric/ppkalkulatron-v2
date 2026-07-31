<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Podaci firme koja izdaje dokumente.
 *
 * U v1 je ovo bila tabela `companies` jer je aplikacija bila multi-tenant. Ovdje
 * postoji tačno jedna firma, pa je to podešavanje a ne entitet — nema tabele sa
 * jednim redom.
 */
class CompanySettings extends Settings
{
    public string $name;

    public ?string $address;

    public ?string $city;

    public ?string $zip;

    public ?string $country;

    public ?string $phone;

    public ?string $email;

    /** JIB */
    public ?string $identification_number;

    /** PDV broj; prazno znači da firma nije u sistemu PDV-a. */
    public ?string $vat_number;

    /** Bez ovoga se PDV ne prikazuje na dokumentima. */
    public bool $is_vat_obligor;

    /** Mali preduzetnik (paušalac) — nije u sistemu PDV-a, pa dokument nosi napomenu. */
    public bool $is_small_entrepreneur;

    public ?string $small_entrepreneur_note;

    public static function group(): string
    {
        return 'company';
    }
}
