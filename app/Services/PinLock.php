<?php

namespace App\Services;

use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Hash;
use Native\Mobile\Runtime;

/**
 * PIN zaključavanje aplikacije, namjerno jednostavno.
 *
 * Opcionalno je: dok nije podešen, ulazi se direktno. Kad se podesi, traži se pri
 * pokretanju. Čuva se kao hash — jedino odstupanje od „samo PIN", jer čuvati ga kao
 * tekst na telefonu nema opravdanja.
 */
class PinLock
{
    public const SESSION_KEY = 'pin_unlocked';

    /** Vrijeme posljednje aktivnosti; po njemu se mjeri automatsko zaključavanje. */
    public const SEEN_KEY = 'pin_last_seen';

    /** Oznaka procesa u kojem je otključano. */
    public const BOOT_KEY = 'pin_boot';

    /**
     * Nova vrijednost pri svakom pokretanju procesa.
     *
     * Na uređaju jedan PHP proces služi sve zahtjeve, pa ovo pouzdano razlikuje
     * „ista sesija, isto pokretanje" od „ista sesija, aplikacija je restartovana".
     */
    private static ?string $boot = null;

    public static function boot(): string
    {
        return self::$boot ??= bin2hex(random_bytes(8));
    }

    /**
     * Da li oznaka procesa uopšte nešto znači.
     *
     * Ima smisla samo u trajnom NativePHP procesu, gdje jedan PHP servira sve
     * zahtjeve. Pod php-fpm ili ugrađenim serverom svaki zahtjev je novi proces,
     * pa bi provjera zaključala korisnika na prvom kliku poslije unosa PIN-a.
     */
    public static function tracksProcess(): bool
    {
        return (new \ReflectionClass(Runtime::class))
            ->getStaticPropertyValue('booted', false) === true;
    }

    public function __construct(private SecuritySettings $settings) {}

    public function isEnabled(): bool
    {
        return filled($this->settings->pin_hash);
    }

    public function set(string $pin): void
    {
        $this->settings->pin_hash = Hash::make($pin);
        $this->settings->save();
    }

    public function disable(): void
    {
        $this->settings->pin_hash = null;
        $this->settings->save();
    }

    /** Obilježi sesiju otključanom u ovom pokretanju aplikacije. */
    public function markUnlocked(): void
    {
        session()->put([
            self::SESSION_KEY => true,
            self::BOOT_KEY => self::boot(),
            self::SEEN_KEY => now(),
        ]);
    }

    public function autoLockMinutes(): int
    {
        return max(0, $this->settings->auto_lock_minutes);
    }

    public function verify(string $pin): bool
    {
        return $this->isEnabled() && Hash::check($pin, $this->settings->pin_hash);
    }
}
