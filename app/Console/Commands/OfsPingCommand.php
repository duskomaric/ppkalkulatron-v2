<?php

namespace App\Console\Commands;

use App\Services\OFSService;
use App\Settings\FiscalSettings;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Brza provjera da li je ESIR dostupan i šta prijavljuje.
 *
 * Radi i protiv prave kase i protiv tools/mock-esir.php.
 */
class OfsPingCommand extends Command
{
    protected $signature = 'ofs:ping
        {--url= : Base URL uređaja, npr. http://192.168.31.102:3566}
        {--key= : API ključ}
        {--serial= : Serijski broj (samo cloud)}
        {--pac= : PAK (samo cloud)}';

    protected $description = 'Pozovi /api/attention i /api/status na fiskalnom uređaju';

    public function handle(): int
    {
        $ofs = new OFSService(
            baseUrl: $this->option('url') ?: null,
            apiKey: $this->option('key') ?: null,
            serialNumber: $this->option('serial') ?: null,
            pac: $this->option('pac') ?: null,
        );

        $this->line('Uređaj: '.($this->option('url') ?: app(FiscalSettings::class)->base_url));
        $this->newLine();

        try {
            $attention = $ofs->testAttention();
        } catch (\Throwable $e) {
            $this->error('Uređaj nije dostupan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->line('attention → HTTP '.$attention->status());
        $this->line('  '.Str::limit($attention->body(), 200));

        if (! $attention->successful()) {
            return self::FAILURE;
        }

        $status = $ofs->getStatus();
        $this->newLine();
        $this->line('status → HTTP '.$status->status());

        if (! $status->successful()) {
            return self::SUCCESS;
        }

        $gsc = array_map('strval', (array) $status->json('gsc', []));
        $this->line('  uid: '.($status->json('uid') ?? '-'));
        $this->line('  gsc: '.(($gsc === []) ? '(prazno — cloud uređaj ili ne traži PIN)' : implode(', ', $gsc)));

        if (in_array('1500', $gsc, true)) {
            $this->warn('  Uređaj traži PIN sigurnosnog elementa.');
        }

        // Poreske oznake dolaze sa uređaja — v1 ih je hardkodirao, v2 ih čita odavde.
        $categories = (array) $status->json('currentTaxRates.taxCategories', []);
        $labels = [];
        foreach ($categories as $category) {
            foreach ((array) ($category['taxRates'] ?? []) as $rate) {
                $labels[] = ($rate['label'] ?? '?').' = '.($rate['rate'] ?? '?').'%';
            }
        }

        $this->line('  poreske oznake: '.($labels ? implode(', ', $labels) : '-'));

        return self::SUCCESS;
    }
}
