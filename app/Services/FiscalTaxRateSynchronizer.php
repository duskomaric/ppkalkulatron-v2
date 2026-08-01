<?php

namespace App\Services;

use App\Models\FiscalTaxRate;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class FiscalTaxRateSynchronizer
{
    public function __construct(private OFSService $ofs) {}

    /**
     * Provjerava kasu i osvježava lokalni katalog bez kojeg nema unosa artikala
     * ni računa. Sve oznake se čuvaju doslovno, uključujući ćirilicu.
     *
     * @return array{count: int, device_serial_number: ?string}
     */
    public function syncFromDevice(): array
    {
        $attention = $this->ofs->testAttention();

        if (! $attention->successful()) {
            throw new RuntimeException("Fiskalni uređaj nije dostupan (HTTP {$attention->status()}).");
        }

        $status = $this->ofs->getStatus();

        if (! $status->successful()) {
            throw new RuntimeException("Fiskalni uređaj nije dostupan (HTTP {$status->status()}).");
        }

        return $this->sync((array) $status->json());
    }

    /**
     * @param  array<string, mixed>  $status
     * @return array{count: int, device_serial_number: ?string}
     */
    public function sync(array $status): array
    {
        $entries = $this->entries((array) ($status['currentTaxRates'] ?? []));

        if ($entries === []) {
            throw new RuntimeException('Fiskalni uređaj nije poslao trenutno važeće poreske stope.');
        }

        DB::transaction(function () use ($entries): void {
            FiscalTaxRate::query()->delete();
            FiscalTaxRate::query()->insert($entries);
        });

        return [
            'count' => count($entries),
            'device_serial_number' => $status['deviceSerialNumber'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $currentRates
     * @return array<int, array<string, mixed>>
     */
    private function entries(array $currentRates): array
    {
        $now = now();

        return collect((array) ($currentRates['taxCategories'] ?? []))
            ->flatMap(function (array $category) use ($now): array {
                return collect((array) ($category['taxRates'] ?? []))
                    ->filter(fn (array $rate): bool => filled($rate['label'] ?? null) && isset($rate['rate']))
                    ->map(fn (array $rate): array => [
                        'label' => (string) $rate['label'],
                        'rate' => $rate['rate'],
                        'category_name' => (string) ($category['name'] ?? '—'),
                        'category_type' => isset($category['categoryType']) ? (int) $category['categoryType'] : null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();
            })->unique('label')->values()->all();
    }
}
