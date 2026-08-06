<?php

namespace App\Services;

use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Dnevno preuzimanje kursne liste za valute koje aplikacija koristi.
 *
 * Fiskalnoj kasi iznosi uvijek idu u KM, pa račun u stranoj valuti bez kursa ne
 * može biti fiskalizovan. Upakovana aplikacija nema raspoređivač poslova, pa se
 * lista preuzima kad se otvori ekran sa računima — jednom dnevno, uz ostale
 * provjere koje se tada rade.
 */
class ExchangeRateUpdater
{
    private const CACHE_KEY = 'exchange-rates-check';

    public function __construct(
        private ExchangeRateProvider $provider,
        private Diagnostics $diagnostics,
    ) {}

    /** @return array{state: string, label: string, rate_date: ?string, checked_at: ?string, updated: int} */
    public function current(): array
    {
        if ($this->foreignCurrencies() === []) {
            return $this->state('off', 'Nema stranih valuta', null, null);
        }

        $checked = Cache::get(self::CACHE_KEY);

        if (! is_array($checked)) {
            return $this->state('unknown', 'Kursevi nisu provjereni', $this->latestRateDate(), null);
        }

        return $this->state(
            $checked['state'],
            $checked['label'],
            $checked['rate_date'] ?? $this->latestRateDate(),
            $checked['checked_at'] ?? null,
            $checked['updated'] ?? 0,
        );
    }

    /** Lista se objavljuje jednom dnevno, pa se i provjerava jednom dnevno. */
    public function refreshIfStale(): array
    {
        $current = $this->current();

        if ($current['state'] === 'off') {
            return $current;
        }

        $checkedToday = $current['checked_at'] !== null
            && now()->parse($current['checked_at'])->isSameDay(now());

        return $checkedToday ? $current : $this->refresh();
    }

    /** @return array{state: string, label: string, rate_date: ?string, checked_at: ?string, updated: int} */
    public function refresh(): array
    {
        $currencies = $this->foreignCurrencies();

        if ($currencies === []) {
            return $this->state('off', 'Nema stranih valuta', null, null);
        }

        try {
            $list = $this->provider->fetch();
        } catch (Throwable $exception) {
            $this->diagnostics->error('Kursna lista nije preuzeta', ['reason' => $exception->getMessage()]);

            // Zatečeni kursevi ostaju; preračun radi sa posljednjim poznatim.
            return $this->remember('unavailable', 'Kursna lista nije dostupna', $this->latestRateDate(), 0);
        }

        $updated = 0;

        foreach ($currencies as $code) {
            if (! isset($list['rates'][$code])) {
                continue;
            }

            // whereDate, ne updateOrCreate: na SQLite-u kolona datuma nosi i vrijeme.
            $rate = ExchangeRate::query()
                ->where('currency', $code)
                ->whereDate('rate_date', $list['date']->toDateString())
                ->first() ?? new ExchangeRate(['currency' => $code, 'rate_date' => $list['date']->toDateString()]);

            $rate->rate_to_bam = $list['rates'][$code];
            $rate->save();
            $updated++;
        }

        $missing = array_values(array_diff($currencies, array_keys($list['rates'])));

        if ($missing !== []) {
            $this->diagnostics->error('Kursna lista nema kurs za valutu', ['currencies' => $missing]);
        }

        return $this->remember(
            'ok',
            'Kursevi sa '.$list['date']->format('d.m.Y.'),
            $list['date']->toDateString(),
            $updated,
        );
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Valute koje traže kurs — sve osim podrazumijevane.
     *
     * @return list<string>
     */
    private function foreignCurrencies(): array
    {
        return Currency::query()
            ->where('is_default', false)
            ->pluck('code')
            ->map(fn (string $code): string => strtoupper($code))
            ->reject(fn (string $code): bool => $code === 'BAM')
            ->values()
            ->all();
    }

    private function latestRateDate(): ?string
    {
        $date = ExchangeRate::query()->max('rate_date');

        return $date ? now()->parse($date)->toDateString() : null;
    }

    /** @return array{state: string, label: string, rate_date: ?string, checked_at: ?string, updated: int} */
    private function remember(string $state, string $label, ?string $rateDate, int $updated): array
    {
        $checked = $this->state($state, $label, $rateDate, now()->toIso8601String(), $updated);

        // Neuspjeh se pamti kraće, da se pokušaj ponovi kad se mreža vrati.
        Cache::put(self::CACHE_KEY, $checked, $state === 'ok' ? now()->endOfDay() : now()->addMinutes(30));

        return $checked;
    }

    /** @return array{state: string, label: string, rate_date: ?string, checked_at: ?string, updated: int} */
    private function state(string $state, string $label, ?string $rateDate, ?string $checkedAt, int $updated = 0): array
    {
        return [
            'state' => $state,
            'label' => $label,
            'rate_date' => $rateDate,
            'checked_at' => $checkedAt,
            'updated' => $updated,
        ];
    }
}
