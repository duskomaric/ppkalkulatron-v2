<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Services\ExchangeRateUpdater;
use Illuminate\Console\Command;

/**
 * Preuzimanje kursne liste Centralne banke BiH.
 *
 * Na uređaju se lista preuzima sama, kad se otvore računi. Ova komanda služi za
 * ručno preuzimanje i za instalacije gdje postoji raspoređivač poslova.
 */
class FetchExchangeRatesCommand extends Command
{
    protected $signature = 'app:exchange-rates {--force : Preuzmi i kad je lista već provjerena danas}';

    protected $description = 'Preuzmi kursnu listu Centralne banke BiH';

    public function handle(ExchangeRateUpdater $updater): int
    {
        $result = $this->option('force') ? $updater->refresh() : $updater->refreshIfStale();

        $this->components->twoColumnDetail('stanje', $result['label']);
        $this->components->twoColumnDetail('datum liste', $result['rate_date'] ?? '—');
        $this->components->twoColumnDetail('sačuvano kurseva', (string) $result['updated']);

        if ($result['rate_date']) {
            foreach (ExchangeRate::query()->whereDate('rate_date', $result['rate_date'])->orderBy('currency')->get() as $rate) {
                $this->components->twoColumnDetail($rate->currency, rtrim(rtrim((string) $rate->rate_to_bam, '0'), '.').' KM');
            }
        }

        return $result['state'] === 'unavailable' ? self::FAILURE : self::SUCCESS;
    }
}
