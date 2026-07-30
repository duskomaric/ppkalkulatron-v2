<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Hash;

/**
 * PIN zaključavanje aplikacije.
 *
 * PIN je opcionalan: prvi put kad se aplikacija pokrene nije podešen i ulazi se direktno.
 * Kad se podesi u postavkama, traži se pri svakom pokretanju.
 *
 * PIN se čuva kao hash. Brojač neuspjelih pokušaja i vrijeme zaključavanja idu u bazu,
 * ne u sesiju — inače bi se zaključavanje obišlo gašenjem aplikacije.
 */
class PinLock
{
    public const SESSION_KEY = 'pin_unlocked_at';

    private const HASH = 'pin.hash';

    private const FAILED = 'pin.failed_attempts';

    private const LOCKED_UNTIL = 'pin.locked_until';

    /** Nakon ovoliko promašaja se zaključava. */
    public const MAX_ATTEMPTS = 5;

    /** Na ovoliko sekundi. */
    public const LOCKOUT_SECONDS = 60;

    public function isEnabled(): bool
    {
        return AppSetting::get(self::HASH) !== null;
    }

    public function set(string $pin): void
    {
        AppSetting::set(self::HASH, Hash::make($pin));
        $this->clearFailures();
    }

    public function disable(): void
    {
        AppSetting::forget(self::HASH);
        $this->clearFailures();
    }

    /** Provjeri PIN i vodi brojač promašaja. Ne provjerava zaključanost — vidi secondsUntilUnlock(). */
    public function verify(string $pin): bool
    {
        $hash = AppSetting::get(self::HASH);

        if ($hash === null) {
            return false;
        }

        if (! Hash::check($pin, $hash)) {
            $this->recordFailure();

            return false;
        }

        $this->clearFailures();

        return true;
    }

    /** Koliko sekundi je ostalo zaključavanja, 0 ako nije zaključano. */
    public function secondsUntilUnlock(): int
    {
        $until = AppSetting::get(self::LOCKED_UNTIL);

        if ($until === null) {
            return 0;
        }

        return max(0, (int) $until - time());
    }

    public function isLockedOut(): bool
    {
        return $this->secondsUntilUnlock() > 0;
    }

    public function attemptsLeft(): int
    {
        return max(0, self::MAX_ATTEMPTS - (int) AppSetting::get(self::FAILED, 0));
    }

    private function recordFailure(): void
    {
        $failed = (int) AppSetting::get(self::FAILED, 0) + 1;
        AppSetting::set(self::FAILED, $failed);

        if ($failed >= self::MAX_ATTEMPTS) {
            AppSetting::set(self::LOCKED_UNTIL, time() + self::LOCKOUT_SECONDS);
            AppSetting::set(self::FAILED, 0);
        }
    }

    private function clearFailures(): void
    {
        AppSetting::forget(self::FAILED);
        AppSetting::forget(self::LOCKED_UNTIL);
    }
}
