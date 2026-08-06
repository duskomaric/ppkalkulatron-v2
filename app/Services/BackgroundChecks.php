<?php

namespace App\Services;

/**
 * Provjere koje aplikacija radi sama, dok korisnik radi svoje.
 *
 * Upakovana aplikacija nema raspoređivač poslova, pa se ono što bi inače radio
 * dnevni zadatak pokreće kad se otvori ekran: status fiskalne kase (svakih minut)
 * i kursna lista (jednom dnevno). Sve ide kroz jedan zahtjev, da stranica ne
 * otvara po jedan poziv za svaku provjeru.
 */
class BackgroundChecks
{
    public function __construct(
        private FiscalDeviceHealth $health,
        private ExchangeRateUpdater $rates,
    ) {}

    /**
     * Zatečeno stanje, bez ijednog poziva prema mreži — za prvo iscrtavanje stranice.
     *
     * @return array{fiscal: array<string, mixed>, rates: array<string, mixed>}
     */
    public function current(): array
    {
        return [
            'fiscal' => $this->health->current(),
            'rates' => $this->rates->current(),
        ];
    }

    /**
     * Osvježava ono čemu je isteklo vrijeme; svaka provjera zna svoj ritam.
     *
     * @return array{fiscal: array<string, mixed>, rates: array<string, mixed>}
     */
    public function refresh(): array
    {
        return [
            'fiscal' => $this->health->refreshIfStale(),
            'rates' => $this->rates->refreshIfStale(),
        ];
    }
}
