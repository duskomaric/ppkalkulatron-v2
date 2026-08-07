<?php

namespace App\Services;

use App\Models\Article;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\FiscalTaxRate;
use App\Models\Invoice;
use App\Settings\CompanySettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;
use App\Settings\SetupSettings;

/**
 * Šta aplikaciji fali da bi se mogao izdati prvi račun.
 *
 * Svježa instalacija nema ništa od ovoga, pa umjesto prazne liste računa ima
 * smisla pokazati redoslijed koraka. Isto stanje nastaje i kasnije — npr. kad se
 * promijeni kasa, poreske stope se obrišu — pa se koraci računaju uvijek iznova,
 * iz stvarnog stanja, a ne iz zapamćene zastavice.
 */
class SetupProgress
{
    public function __construct(
        private CompanySettings $company,
        private FiscalSettings $fiscal,
        private SetupSettings $setup,
    ) {}

    /**
     * Koraci bez kojih račun ne može nastati ni biti fiskalizovan.
     *
     * @return list<array{key: string, title: string, description: string, done: bool, route: string, action: string}>
     */
    public function steps(): array
    {
        return [
            [
                'key' => 'company',
                'title' => 'Podaci firme',
                'description' => 'Naziv i JIB idu na svaki dokument. Mogu se preuzeti i sa fiskalne kase.',
                'done' => filled($this->company->name) && filled($this->company->identification_number),
                'route' => route('settings.company.edit'),
                'action' => 'Otvori profil',
            ],
            [
                'key' => 'device',
                'title' => 'Veza sa fiskalnom kasom',
                'description' => 'Adresa kase i pristupni ključ; kasa se može i potražiti na mreži.',
                'done' => filled($this->fiscal->base_url) && filled($this->fiscal->api_key),
                'route' => route('settings.fiscal.edit'),
                'action' => 'Podesi kasu',
            ],
            [
                'key' => 'tax_rates',
                'title' => 'Poreske stope sa kase',
                'description' => 'Oznake poreza javlja sama kasa. Bez njih se ne može dodati artikal.',
                'done' => FiscalTaxRate::query()->exists(),
                'route' => route('settings.fiscal.edit').'#stope',
                'action' => 'Preuzmi stope',
            ],
            [
                'key' => 'article',
                'title' => 'Prvi artikal',
                'description' => 'Usluga ili proizvod sa cijenom i poreskom oznakom.',
                'done' => Article::query()->exists(),
                'route' => route('articles.create'),
                'action' => 'Dodaj artikal',
            ],
            [
                'key' => 'client',
                'title' => 'Prvi klijent',
                'description' => 'Kupac sa JIB-om; za veleprodaju je obavezan.',
                'done' => Client::query()->exists(),
                'route' => route('clients.create'),
                'action' => 'Dodaj klijenta',
            ],
        ];
    }

    /**
     * Korisno, ali ne i uslov za prvi račun.
     *
     * @return list<array{title: string, done: bool, route: string}>
     */
    public function recommended(): array
    {
        return [
            [
                'title' => 'Bankovni račun na dokumentima',
                'done' => BankAccount::query()->exists(),
                'route' => route('bank-accounts.create'),
            ],
            [
                'title' => 'PIN za zaključavanje aplikacije',
                'done' => app(PinLock::class)->isEnabled(),
                'route' => route('settings.pin.edit'),
            ],
            [
                'title' => 'Mail server za slanje računa',
                'done' => filled(app(MailSettings::class)->host),
                'route' => route('settings.mail.edit'),
            ],
        ];
    }

    public function remaining(): int
    {
        return count(array_filter($this->steps(), fn (array $step): bool => ! $step['done']));
    }

    public function isComplete(): bool
    {
        return $this->remaining() === 0;
    }

    public function isDismissed(): bool
    {
        return $this->setup->onboarding_dismissed;
    }

    /**
     * Vodič na računima stoji dok se koraci ne završe ili dok ga korisnik ne skloni.
     *
     * Ko već ima račune, prošao je početak — makar neki korak i dalje bio prazan,
     * lista mu je korisnija od uputstva.
     */
    public function shouldShow(): bool
    {
        return ! $this->isComplete()
            && ! $this->isDismissed()
            && ! Invoice::query()->exists();
    }

    public function dismiss(): void
    {
        $this->setup->onboarding_dismissed = true;
        $this->setup->save();
    }

    public function restore(): void
    {
        $this->setup->onboarding_dismissed = false;
        $this->setup->save();
    }
}
