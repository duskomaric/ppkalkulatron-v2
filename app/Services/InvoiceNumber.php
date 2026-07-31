<?php

namespace App\Services;

use App\Models\Invoice;
use App\Settings\NumberingSettings;

/**
 * Broj računa se izvodi iz samih računa, ne iz brojača.
 *
 * U v1 je brojač bio jedini izvor istine i razilazio se sa stvarnošću: brisanje
 * računa nije ispravno oslobađalo broj. Ovdje je pravilo jedno — sljedeći broj je
 * najveći iskorišteni + 1. Brisanje time samo radi, bez ijedne dodatne linije.
 *
 * Podešavanja numeracije se poštuju: početni broj postavlja pod, broj nula i
 * prefiks određuju oblik, a isključen godišnji reset znači da brojanje ide dalje
 * kroz godine umjesto da počne od jedan svakog januara.
 */
class InvoiceNumber
{
    public function __construct(private NumberingSettings $settings) {}

    public function next(?int $year = null): string
    {
        $year ??= (int) date('Y');

        $max = max($this->highestUsed($year), $this->settings->invoice_starting_number - 1);

        return $this->format($max + 1, $year);
    }

    public function format(int $number, int $year): string
    {
        $padded = str_pad((string) $number, max(1, $this->settings->pad_zeros), '0', STR_PAD_LEFT);

        return $this->settings->invoice_prefix.$padded.'/'.$year;
    }

    /** @return array{number: int, year: int}|null */
    public function parse(string $formatted): ?array
    {
        $prefix = preg_quote($this->settings->invoice_prefix, '#');

        if (! preg_match("#^{$prefix}(\d+)/(\d{4})$#", trim($formatted), $matches)) {
            return null;
        }

        return ['number' => (int) $matches[1], 'year' => (int) $matches[2]];
    }

    /**
     * Najveći iskorišten broj. Uz godišnji reset gleda se samo tražena godina;
     * bez njega se broji kroz sve godine, pa numeracija nastavlja gdje je stala.
     */
    private function highestUsed(int $year): int
    {
        $max = 0;

        foreach (Invoice::query()->pluck('invoice_number') as $number) {
            $parsed = $this->parse((string) $number);

            if ($parsed === null) {
                continue;
            }

            if ($this->settings->reset_yearly && $parsed['year'] !== $year) {
                continue;
            }

            $max = max($max, $parsed['number']);
        }

        return $max;
    }
}
