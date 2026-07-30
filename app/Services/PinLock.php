<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Hash;

/**
 * PIN zaključavanje aplikacije, namjerno jednostavno.
 *
 * PIN je opcionalan: prvi put kad se aplikacija pokrene nije podešen i ulazi se
 * direktno u račune. Kad se podesi u podešavanjima, traži se pri pokretanju.
 *
 * Čuva se kao hash — jedina stvar koja nije "samo PIN", ali čuvati ga kao tekst
 * na telefonu nema opravdanja.
 */
class PinLock
{
    public const SESSION_KEY = 'pin_unlocked';

    private const HASH = 'pin.hash';

    public function isEnabled(): bool
    {
        return AppSetting::get(self::HASH) !== null;
    }

    public function set(string $pin): void
    {
        AppSetting::set(self::HASH, Hash::make($pin));
    }

    public function disable(): void
    {
        AppSetting::forget(self::HASH);
    }

    public function verify(string $pin): bool
    {
        $hash = AppSetting::get(self::HASH);

        return $hash !== null && Hash::check($pin, $hash);
    }
}
