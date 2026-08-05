<?php

namespace App\Services;

use App\Settings\FiscalSettings;
use Illuminate\Support\Facades\Cache;
use Throwable;

class FiscalDeviceHealth
{
    private const CACHE_KEY = 'fiscal-device-health';

    private const FRESH_FOR_SECONDS = 60;

    private const PROBE_TIMEOUT_SECONDS = 4;

    public function __construct(
        private FiscalSettings $settings,
        private OFSService $ofs,
        private FiscalPinUnlocker $pin,
    ) {}

    /** @return array{state: string, label: string, checked_at: ?string, is_stale: bool} */
    public function current(): array
    {
        $health = Cache::get(self::CACHE_KEY);

        if (! is_array($health) || ($health['signature'] ?? null) !== $this->settingsSignature()) {
            return $this->unknown();
        }

        return $this->responseFor($health, false);
    }

    /** @return array{state: string, label: string, checked_at: ?string, is_stale: bool} */
    public function refreshIfStale(): array
    {
        $current = $this->current();

        return $current['is_stale'] ? $this->refresh() : $current;
    }

    /** @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    public function refresh(): array
    {
        try {
            $attention = $this->ofs->testAttention(self::PROBE_TIMEOUT_SECONDS);

            if (! $attention->successful()) {
                return $this->remember('unavailable', 'Uređaj nije dostupan');
            }

            $status = $this->ofs->getStatus(self::PROBE_TIMEOUT_SECONDS);

            if (! $status->successful()) {
                return $this->remember('unavailable', 'Uređaj nije dostupan');
            }

            if ($this->needsPin($status->json('gsc'))) {
                // Sačuvan PIN znači da korisnik ne mora ništa raditi kad kasa
                // zatraži otključavanje — pošalje se i status se provjeri ponovo.
                if (! $this->pin->unlock()) {
                    return $this->remember('pin_required', 'Potreban PIN uređaja');
                }

                $status = $this->ofs->getStatus(self::PROBE_TIMEOUT_SECONDS);

                if (! $status->successful() || $this->needsPin($status->json('gsc'))) {
                    return $this->remember('pin_required', 'Potreban PIN uređaja');
                }
            }

            return $this->remember('ready', 'Uređaj povezan');
        } catch (Throwable) {
            return $this->remember('unavailable', 'Uređaj nije dostupan');
        }
    }

    /** GSC 1500: sigurnosni element traži PIN. */
    private function needsPin(mixed $gsc): bool
    {
        return in_array('1500', array_map('strval', (array) ($gsc ?? [])), true);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    public function markReady(): array
    {
        return $this->remember('ready', 'Uređaj povezan');
    }

    /** @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    public function markPinRequired(): array
    {
        return $this->remember('pin_required', 'Potreban PIN uređaja');
    }

    /** @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    public function markUnavailable(): array
    {
        return $this->remember('unavailable', 'Uređaj nije dostupan');
    }

    /** @return array{state: string, label: string, checked_at: ?string, is_stale: bool} */
    private function unknown(): array
    {
        return [
            'state' => 'unknown',
            'label' => 'Status uređaja nije provjeren',
            'checked_at' => null,
            'is_stale' => true,
        ];
    }

    /** @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    private function remember(string $state, string $label): array
    {
        $health = [
            'state' => $state,
            'label' => $label,
            'checked_at' => now()->toIso8601String(),
            'signature' => $this->settingsSignature(),
        ];

        Cache::put(self::CACHE_KEY, $health, now()->addSeconds(self::FRESH_FOR_SECONDS));

        return $this->responseFor($health, false);
    }

    /** @param array{state: string, label: string, checked_at: string, signature: string} $health
     *  @return array{state: string, label: string, checked_at: string, is_stale: bool} */
    private function responseFor(array $health, bool $isStale): array
    {
        return [
            'state' => $health['state'],
            'label' => $health['label'],
            'checked_at' => $health['checked_at'],
            'is_stale' => $isStale,
        ];
    }

    private function settingsSignature(): string
    {
        return hash('sha256', implode('|', [
            $this->settings->base_url,
            $this->settings->api_key,
            $this->settings->serial_number,
            $this->settings->pac,
            $this->settings->security_pin,
        ]));
    }
}
