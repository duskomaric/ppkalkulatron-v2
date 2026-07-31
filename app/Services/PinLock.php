<?php

namespace App\Services;

use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Hash;

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

    public function verify(string $pin): bool
    {
        return $this->isEnabled() && Hash::check($pin, $this->settings->pin_hash);
    }
}
