<?php

namespace App\Settings;

use Illuminate\Support\Carbon;
use Spatie\LaravelSettings\Settings;

class DiagnosticsSettings extends Settings
{
    public ?string $email;

    public ?Carbon $detailed_until;

    public ?Carbon $last_sent_at;

    public function detailedLoggingEnabled(): bool
    {
        return $this->detailed_until?->isFuture() ?? false;
    }

    public static function group(): string
    {
        return 'diagnostics';
    }
}
