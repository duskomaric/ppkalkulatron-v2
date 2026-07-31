<?php

namespace App\Support;

/**
 * Iznos slovima, na srpskom (ijekavica).
 *
 * Predlošci su ovo ranije tražili od `NumberFormatter`, ali PHP koji NativePHP
 * pakuje na uređaj ide **bez ICU-a** (`nativephp.lock` → `php.icu: false`), pa
 * bi na telefonu ista faktura ispisala „330" umjesto „trista trideset". Zato se
 * računa ovdje i radi svuda isto.
 */
class SpelledAmount
{
    private const ONES = [
        'nula', 'jedan', 'dva', 'tri', 'četiri', 'pet', 'šest', 'sedam', 'osam', 'devet',
        'deset', 'jedanaest', 'dvanaest', 'trinaest', 'četrnaest', 'petnaest',
        'šesnaest', 'sedamnaest', 'osamnaest', 'devetnaest',
    ];

    private const TENS = [
        2 => 'dvadeset', 3 => 'trideset', 4 => 'četrdeset', 5 => 'pedeset',
        6 => 'šezdeset', 7 => 'sedamdeset', 8 => 'osamdeset', 9 => 'devedeset',
    ];

    private const HUNDREDS = [
        1 => 'sto', 2 => 'dvjesto', 3 => 'trista', 4 => 'četiristo', 5 => 'petsto',
        6 => 'šeststo', 7 => 'sedamsto', 8 => 'osamsto', 9 => 'devetsto',
    ];

    public static function of(int $number): string
    {
        if ($number < 0) {
            return 'minus '.self::of(-$number);
        }

        if ($number < 1000) {
            return self::underThousand($number) ?: 'nula';
        }

        if ($number < 1_000_000) {
            return trim(self::thousands(intdiv($number, 1000)).' '.self::underThousand($number % 1000));
        }

        if ($number < 1_000_000_000) {
            $remainder = $number % 1_000_000;

            return trim(self::millions(intdiv($number, 1_000_000)).($remainder ? ' '.self::of($remainder) : ''));
        }

        // Preko milijarde nema smisla na fakturi — vrati cifre.
        return number_format($number, 0, ',', '.');
    }

    private static function underThousand(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $words = [];

        if ($number >= 100) {
            $words[] = self::HUNDREDS[intdiv($number, 100)];
            $number %= 100;
        }

        if ($number >= 20) {
            $words[] = self::TENS[intdiv($number, 10)];
            $number %= 10;
        }

        if ($number > 0) {
            $words[] = self::ONES[$number];
        }

        return implode(' ', $words);
    }

    /** Hiljada je ženskog roda: jedna, dvije — i „hiljadu" kad je tačno jedna. */
    private static function thousands(int $count): string
    {
        if ($count === 1) {
            return 'hiljadu';
        }

        $words = self::feminine(self::underThousand($count));
        $last = $count % 100;
        $lastDigit = $count % 10;

        $noun = ($last < 11 || $last > 14) && $lastDigit >= 2 && $lastDigit <= 4 ? 'hiljade' : 'hiljada';

        return $words.' '.$noun;
    }

    private static function millions(int $count): string
    {
        $last = $count % 100;
        $lastDigit = $count % 10;

        $noun = match (true) {
            ($last < 11 || $last > 14) && $lastDigit === 1 => 'milion',
            ($last < 11 || $last > 14) && $lastDigit >= 2 && $lastDigit <= 4 => 'miliona',
            default => 'miliona',
        };

        return self::underThousand($count).' '.$noun;
    }

    private static function feminine(string $words): string
    {
        return preg_replace(['/\bjedan\b/u', '/\bdva\b/u'], ['jedna', 'dvije'], $words);
    }
}
