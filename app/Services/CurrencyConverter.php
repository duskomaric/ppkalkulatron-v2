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
    /** Kurs koji važi na taj datum: onaj sa tog dana, ili posljednji raniji. */
    public function rateFor(string $currency, CarbonInterface|string $date): ?string
    {
        if (strtoupper($currency) === 'BAM') {
            return null;
        }

        $rate = ExchangeRate::query()
            ->where('currency', strtoupper($currency))
            ->whereDate('rate_date', '<=', $date)
            ->orderByDesc('rate_date')
            ->value('rate_to_bam');

        return $rate === null ? null : (string) $rate;
    }

    /** Kurs za taj datum, ili posljednji raniji. */
    public function toBam(int $pfening, string $currency, CarbonInterface|string $date): int
    {
        if (strtoupper($currency) === 'BAM') {
            return $pfening;
        }

        $rate = $this->rateFor($currency, $date);

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
