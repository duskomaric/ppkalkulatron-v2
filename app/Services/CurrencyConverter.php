<?php

namespace App\Services;

use App\Models\ExchangeRate;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Preračun u konvertibilnu marku, po v1 CurrencyConversionService.
 *
 * v1 čuva preračunate iznose u zasebnim _bam kolonama. v2 računa u trenutku
 * kad zatreba — a treba samo fiskalnom uređaju — pa nema kolona koje mogu
 * zastariti ako se kurs kasnije ispravi.
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
