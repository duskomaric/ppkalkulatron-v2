<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Preračun u konvertibilnu marku prema kursu koji važi na datum računa.
 */
class CurrencyConverter
{
    /** Kurs za taj datum, ili posljednji raniji. */
    public function toBam(int $pfening, string $currency, CarbonInterface|string $date): int
    {
        if (strtoupper($currency) === 'BAM') {
            return $pfening;
        }

        $rate = ExchangeRate::query()
            ->where('currency', strtoupper($currency))
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->value('rate_to_bam');

        if ($rate === null) {
            throw new RuntimeException(
                "Nema kursa za {$currency} na dan ".
                ($date instanceof CarbonInterface ? $date->toDateString() : $date).
                ' (ni ranije). Unesite kurs u podešavanjima valuta.'
            );
        }

        return (int) round($pfening * (float) $rate);
    }
}
