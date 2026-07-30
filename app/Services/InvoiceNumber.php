<?php

namespace App\Services;

use App\Models\Invoice;

/**
 * Broj računa se izvodi iz samih računa, ne iz brojača.
 *
 * U v1 je brojač bio jedini izvor istine i razilazio se sa stvarnošću: brisanje
 * računa nije ispravno oslobađalo broj. Ovdje je pravilo jedno — sljedeći broj je
 * najveći iskorišteni + 1, a ako računa nema, počinje od 1. Brisanje time samo
 * radi, bez ijedne dodatne linije.
 */
class InvoiceNumber
{
    public function next(?int $year = null): string
    {
        $year ??= (int) date('Y');

        $max = 0;

        foreach (Invoice::query()->pluck('invoice_number') as $number) {
            $parsed = $this->parse((string) $number);

            if ($parsed !== null && $parsed['year'] === $year && $parsed['number'] > $max) {
                $max = $parsed['number'];
            }
        }

        return $this->format($max + 1, $year);
    }

    public function format(int $number, int $year): string
    {
        return str_pad((string) $number, 4, '0', STR_PAD_LEFT).'/'.$year;
    }

    /** @return array{number: int, year: int}|null */
    public function parse(string $formatted): ?array
    {
        if (! preg_match('#^(\d+)/(\d{4})$#', trim($formatted), $matches)) {
            return null;
        }

        return ['number' => (int) $matches[1], 'year' => (int) $matches[2]];
    }
}
