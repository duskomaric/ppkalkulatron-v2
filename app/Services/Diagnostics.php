<?php

namespace App\Services;

use App\Settings\DiagnosticsSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Sigurni tehnički trag: nikad ne prima sadržaj dokumenata ni tajne. */
class Diagnostics
{
    private const SENSITIVE_KEYS = [
        'api_key', 'authorization', 'body', 'contents', 'email', 'error', 'html',
        'message', 'pac', 'password', 'pdf', 'pin', 'response', 'secret',
        'serial_number', 'token',
    ];

    public function __construct(private DiagnosticsSettings $settings) {}

    /** Događaji detaljnog debugovanja postoje samo uz vremenski ograničenu saglasnost. */
    public function debug(string $event, array $context = []): void
    {
        if (! $this->settings->detailedLoggingEnabled()) {
            return;
        }

        Log::channel('support-diagnostics')->info($event, $this->context($context));
    }

    /** Greške se čuvaju i kada detaljna dijagnostika nije uključena. */
    public function error(string $event, array $context = []): void
    {
        Log::channel('support-diagnostics')->error($event, $this->context($context));
    }

    public function exception(Throwable $exception): void
    {
        $this->error('Unhandled application exception', [
            'exception' => $exception::class,
            'code' => $exception->getCode(),
        ]);
    }

    /** @return array<string, mixed> */
    private function context(array $context): array
    {
        return [
            'app_version' => config('nativephp.version'),
            'build_code' => config('nativephp.version_code'),
            ...$this->sanitize($context),
        ];
    }

    /** @return array<string, mixed> */
    public function sanitize(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            $normalizedKey = strtolower((string) $key);

            if (in_array($normalizedKey, self::SENSITIVE_KEYS, true)
                || str_contains($normalizedKey, 'password')
                || str_contains($normalizedKey, 'secret')
                || str_contains($normalizedKey, 'content')) {
                $sanitized[$key] = '[redacted]';

                continue;
            }

            $sanitized[$key] = is_array($value) ? $this->sanitize($value) : $value;
        }

        return $sanitized;
    }
}
