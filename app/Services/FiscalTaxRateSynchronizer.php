<?php

namespace App\Services;

use App\Models\FiscalTaxRate;
use Illuminate\Support\Carbon;
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
        $current = (array) ($status['currentTaxRates'] ?? []);
        $currentGroupId = $current['groupId'] ?? null;
        $groups = (array) ($status['allTaxRates'] ?? []);

        if (! collect($groups)->contains(fn (array $group): bool => ($group['groupId'] ?? null) === $currentGroupId)) {
            $groups[] = $current;
        }

        $now = now();
        $entries = collect($groups)
            ->filter(fn (array $group): bool => isset($group['groupId']))
            ->flatMap(fn (array $group) => $this->entries($group, $currentGroupId, $now))
            ->unique(fn (array $entry): string => $entry['group_id'].'|'.$entry['label'])
            ->values();

        $currentEntries = $entries->where('is_current', true);

        if ($currentEntries->isEmpty()) {
            throw new RuntimeException('Fiskalni uređaj nije poslao trenutno važeće poreske stope.');
        }

        DB::transaction(function () use ($entries): void {
            FiscalTaxRate::query()->update(['is_current' => false]);

            FiscalTaxRate::upsert(
                $entries->all(),
                ['group_id', 'label'],
                ['rate', 'category_name', 'category_type', 'valid_from', 'is_current', 'synced_at', 'updated_at'],
            );
        });

        return [
            'count' => $currentEntries->count(),
            'device_serial_number' => $status['deviceSerialNumber'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $group
     * @return array<int, array<string, mixed>>
     */
    private function entries(array $group, mixed $currentGroupId, Carbon $now): array
    {
        return collect((array) ($group['taxCategories'] ?? []))
            ->flatMap(function (array $category) use ($group, $currentGroupId, $now): array {
                return collect((array) ($category['taxRates'] ?? []))
                    ->filter(fn (array $rate): bool => filled($rate['label'] ?? null) && isset($rate['rate']))
                    ->map(fn (array $rate): array => [
                        'label' => (string) $rate['label'],
                        'rate' => $rate['rate'],
                        'category_name' => (string) ($category['name'] ?? '—'),
                        'group_id' => (int) $group['groupId'],
                        'category_type' => isset($category['categoryType']) ? (int) $category['categoryType'] : null,
                        'valid_from' => isset($group['validFrom']) ? Carbon::parse($group['validFrom']) : null,
                        'is_current' => (int) $group['groupId'] === (int) $currentGroupId,
                        'synced_at' => $now,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all();
            })->all();
    }
}
