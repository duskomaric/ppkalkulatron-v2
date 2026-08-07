<?php

namespace App\Services;

use App\Settings\CompanySettings;
use RuntimeException;

/**
 * Podaci firme sa sertifikata fiskalne kase.
 *
 * Isti podaci stoje na svakom fiskalnom računu, pa nema smisla da se prepisuju
 * ručno — kasa ih vraća kroz `/api/certificate`. Popunjava se samo ono što je
 * prazno ili se razlikuje; ostalo (email, telefon, PDV broj) kasa ne zna.
 */
class CompanyProfileImporter
{
    public function __construct(
        private OFSService $ofs,
        private CompanySettings $company,
        private Diagnostics $diagnostics,
    ) {}

    /**
     * @return array{changed: array<string, string>, valid_to: ?string, serial_number: ?string}
     */
    public function import(): array
    {
        $response = $this->ofs->getCertificate();

        if (! $response->successful()) {
            throw new RuntimeException('Kasa nije vratila podatke firme. Provjerite vezu, pa pokušajte ponovo.');
        }

        $data = $response->json() ?? [];
        $name = trim((string) ($data['name'] ?? ''));

        if ($name === '') {
            throw new RuntimeException('Kasa nije vratila naziv firme.');
        }

        $incoming = array_filter([
            'name' => $name,
            'address' => trim((string) ($data['address'] ?? '')),
            'city' => trim((string) ($data['city'] ?? '')),
            'country' => trim((string) ($data['country'] ?? '')),
            // JIB na sertifikatu zna imati vodeći razmak.
            'identification_number' => trim((string) ($data['tin'] ?? '')),
        ], fn (string $value): bool => $value !== '');

        $changed = [];

        foreach ($incoming as $field => $value) {
            if ((string) $this->company->{$field} !== $value) {
                $changed[$field] = $value;
                $this->company->{$field} = $value;
            }
        }

        if ($changed !== []) {
            $this->company->save();
        }

        $this->diagnostics->debug('Podaci firme preuzeti sa kase', ['fields' => array_keys($changed)]);

        return [
            'changed' => $changed,
            'valid_to' => $data['validTo'] ?? null,
            'serial_number' => $data['serialNumber'] ?? null,
        ];
    }
}
