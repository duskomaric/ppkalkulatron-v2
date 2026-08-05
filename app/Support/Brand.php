<?php

namespace App\Support;

use App\Settings\MenuSettings;
use Throwable;

/**
 * Boje vizuelnog identiteta.
 *
 * Glavnu boju bira korisnik u Podešavanja → Izgled i navigacija; odatle je uzimaju
 * stranica (promjenljiva `--primary-base` u zaglavlju) i crtanje ikona
 * (`php artisan app:brand-assets`). Boja izvan palete ili nedostupna podešavanja —
 * npr. prije migracija — vraćaju podrazumijevanu, pa prikaz nikad ne pukne.
 */
class Brand
{
    public const DEFAULT_COLOR = '#F59E0B';

    /** Jedina boja iz palete koja nije u krugu boja na ikoni. */
    private const NEUTRAL = '#64748B';

    /** Podloge aplikacije — prate `--color-bg` iz resources/css/app.css. */
    private const BACKGROUND = ['light' => '#F8FAFC', 'dark' => '#0B0B0F'];

    /**
     * Ponuđene boje. Sve su čitljive na bijelom tekstu, i u svijetloj i u tamnoj temi.
     *
     * @return array<string, string> heksa => naziv
     */
    public static function palette(): array
    {
        return [
            '#F59E0B' => 'Amber',
            '#F97316' => 'Narandžasta',
            '#EF4444' => 'Crvena',
            '#EC4899' => 'Ciklama',
            '#8B5CF6' => 'Ljubičasta',
            '#2563EB' => 'Plava',
            '#0EA5E9' => 'Azurna',
            '#14B8A6' => 'Tirkizna',
            '#10B981' => 'Zelena',
            '#64748B' => 'Grafitna',
        ];
    }

    /**
     * Boje palete bez neutralne, u krug — odatle se izvode boje na ikoni.
     *
     * @return list<string>
     */
    public static function wheel(): array
    {
        return array_values(array_filter(
            array_keys(self::palette()),
            fn (string $hex): bool => $hex !== self::NEUTRAL,
        ));
    }

    /**
     * Boje ikone izvedene iz izabrane: podloga u četiri boje, tipke u osam.
     *
     * Izabrana boja je uvijek prva, ostale se uzimaju u pravilnom razmaku po krugu,
     * pa svaka boja iz palete daje svoj, ali uvijek usklađen raspored.
     *
     * @return array{mesh: list<string>, keys: list<string>, display: string}
     */
    public static function iconColours(?string $selected = null): array
    {
        $wheel = self::wheel();
        $count = count($wheel);
        $start = array_search($selected ?? self::hex(), $wheel, true);

        if ($start === false) {
            // Boja izvan palete (npr. zadata pri pripremi build-a) ostaje prva.
            $wheel[0] = $selected ?? self::hex();
            $start = 0;
        }

        $at = fn (int $step): string => $wheel[($start + $step) % $count];
        $mesh = array_map($at, [0, 3, 6, 4]);

        return [
            'mesh' => $mesh,
            'keys' => array_map($at, range(0, 7)),
            'display' => $mesh[2],
        ];
    }

    /** Glavna boja u obliku `#RRGGBB`. */
    public static function hex(): string
    {
        try {
            $colour = strtoupper(trim(app(MenuSettings::class)->primary_color));
        } catch (Throwable) {
            return self::DEFAULT_COLOR;
        }

        return isset(self::palette()[$colour]) ? $colour : self::DEFAULT_COLOR;
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function rgb(): array
    {
        return self::channels(self::hex());
    }

    /** Vrijednost za `--primary-base`, koji CSS koristi i kroz `rgba()`. */
    public static function css(): string
    {
        return implode(', ', self::rgb());
    }

    /** Podloga aplikacije za zadatu temu — `light` ili `dark`. */
    public static function background(string $scheme): string
    {
        return self::BACKGROUND[$scheme] ?? self::BACKGROUND['dark'];
    }

    /** @return array{0: int, 1: int, 2: int} */
    public static function backgroundRgb(string $scheme): array
    {
        return self::channels(self::background($scheme));
    }

    /** @return array{0: int, 1: int, 2: int} */
    private static function channels(string $hex): array
    {
        return [
            (int) hexdec(substr($hex, 1, 2)),
            (int) hexdec(substr($hex, 3, 2)),
            (int) hexdec(substr($hex, 5, 2)),
        ];
    }
}
