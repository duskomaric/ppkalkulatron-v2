<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Kursna lista Centralne banke BiH.
 *
 * Odgovor nosi i datum liste koja je stvarno vraćena: za vikend, praznik ili datum
 * u budućnosti banka vrati posljednju objavljenu listu, pa se datum uzima iz
 * odgovora, a ne iz upita. Kursevi se objavljuju kao „Middle" za `Units` jedinica
 * valute (npr. 100 JPY), a aplikacija računa sa kursom za jednu jedinicu.
 */
class ExchangeRateProvider
{
    private const URL = 'https://www.cbbh.ba/CurrencyExchange/GetJson';

    private const TIMEOUT_SECONDS = 8;

    /** Kursevi se drže na osam decimala zbog valuta koje se kotiraju na 100 jedinica. */
    private const SCALE = 8;

    public function __construct(private Diagnostics $diagnostics) {}

    /**
     * @return array{date: CarbonImmutable, number: ?int, rates: array<string, string>}
     */
    public function fetch(?CarbonInterface $date = null): array
    {
        $date ??= CarbonImmutable::today();

        $response = Http::timeout(self::TIMEOUT_SECONDS)
            ->acceptJson()
            ->get(self::URL, ['date' => $date->format('m/d/Y').' 00:00:00']);

        if (! $response->successful()) {
            throw new RuntimeException('Kursna lista nije dostupna (status '.$response->status().').');
        }

        $items = $response->json('CurrencyExchangeItems');

        if (! is_array($items) || $items === []) {
            throw new RuntimeException('Kursna lista je prazna.');
        }

        $rates = [];

        foreach ($items as $item) {
            $code = strtoupper(trim((string) ($item['AlphaCode'] ?? '')));
            $middle = (float) ($item['Middle'] ?? 0);
            $units = max(1, (int) ($item['Units'] ?? 1));

            if (strlen($code) === 3 && $middle > 0) {
                $rates[$code] = number_format($middle / $units, self::SCALE, '.', '');
            }
        }

        if ($rates === []) {
            throw new RuntimeException('Kursna lista nema nijedan upotrebljiv kurs.');
        }

        $listDate = CarbonImmutable::parse((string) $response->json('Date', $date->toDateString()));

        $this->diagnostics->debug('Kursna lista preuzeta', [
            'rate_date' => $listDate->toDateString(),
            'number' => $response->json('Number'),
            'currencies' => count($rates),
        ]);

        return [
            'date' => $listDate,
            'number' => $response->json('Number') === null ? null : (int) $response->json('Number'),
            'rates' => $rates,
        ];
    }
}
