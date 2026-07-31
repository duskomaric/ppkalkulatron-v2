<?php

namespace App\Services;

use App\Settings\MailSettings;
use Illuminate\Contracts\Mail\Mailer;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

/**
 * Slanje pošte kroz SMTP iz podešavanja, po v1 CompanyMailService.
 *
 * Bez podešenog hosta koristi se podrazumijevani mailer iz konfiguracije.
 */
class MailService
{
    public function __construct(private MailSettings $settings) {}

    /** @return array{0: ?string, 1: ?string} */
    public function from(): array
    {
        return [
            $this->settings->from_address ?: config('mail.from.address'),
            $this->settings->from_name ?: config('mail.from.name'),
        ];
    }

    public function mailer(): ?Mailer
    {
        if (! $this->settings->usesOwnSmtp()) {
            return null;
        }

        config(['mail.mailers.app_smtp' => [
            'transport' => 'smtp',
            'host' => $this->settings->host,
            'port' => $this->settings->port ?: 587,
            'encryption' => $this->settings->encryption ?: null,
            'username' => $this->settings->username ?: null,
            'password' => $this->settings->password ?: null,
            'timeout' => null,
        ]]);

        return Mail::mailer('app_smtp');
    }

    public function send(string|array $to, Mailable $mailable): void
    {
        ($this->mailer() ?? Mail::getFacadeRoot())->to($to)->send($mailable);
    }
}
