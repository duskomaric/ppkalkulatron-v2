<?php

namespace App\Services;

use App\Settings\FiscalSettings;
use Throwable;

/**
 * Otključavanje sigurnosnog elementa sačuvanim PIN-om.
 *
 * Kasa traži PIN kad god se kartica izvadi i vrati ili nakon restarta uređaja, a po
 * dokumentaciji je odgovor uvijek isti: pošalji PIN na /api/pin pa nastavi. Kad je
 * PIN sačuvan uz ostale podatke kase, aplikacija to radi sama.
 */
class FiscalPinUnlocker
{
    /** Kod koji uređaj vraća kad prihvati PIN. */
    public const ACCEPTED = '0100';

    public function __construct(
        private FiscalSettings $settings,
        private OFSService $ofs,
        private Diagnostics $diagnostics,
    ) {}

    /** Sigurnosni element je u samom uređaju, pa PIN ima smisla samo za lokalnu kasu. */
    public function hasStoredPin(): bool
    {
        return $this->settings->device_mode === 'local' && filled($this->settings->security_pin);
    }

    /**
     * Pokušaj otključavanja sačuvanim PIN-om.
     *
     * @return bool `false` kad PIN nije sačuvan, kad ga uređaj nije prihvatio ili
     *              kad kasa nije dostupna — pozivalac tada ostaje pri „traži PIN".
     */
    public function unlock(): bool
    {
        if (! $this->hasStoredPin()) {
            return false;
        }

        $pin = trim((string) $this->settings->security_pin);

        try {
            $response = $this->ofs->enterPin($pin);
        } catch (Throwable $exception) {
            $this->diagnostics->error('Automatsko otključavanje kase nije uspjelo', [
                'reason' => 'device_unreachable',
            ]);

            return false;
        }

        $code = trim($response->body(), " \t\n\r\0\x0B\"");
        $accepted = $response->successful() && $code === self::ACCEPTED;

        if (! $accepted) {
            $this->diagnostics->error('Kasa nije prihvatila sačuvani PIN', ['code' => $code]);
        }

        return $accepted;
    }
}
