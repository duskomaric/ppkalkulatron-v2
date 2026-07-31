<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/** SMTP firme; prazan host znači da se šalje preko podrazumijevanog mailera. */
class MailSettings extends Settings
{
    public ?string $from_address;

    public ?string $from_name;

    public ?string $host;

    public ?int $port;

    public ?string $username;

    public ?string $password;

    /** tls | ssl | null */
    public ?string $encryption;

    public static function group(): string
    {
        return 'mail';
    }

    public function usesOwnSmtp(): bool
    {
        return filled($this->host);
    }
}
