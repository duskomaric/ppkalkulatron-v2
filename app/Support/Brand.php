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
