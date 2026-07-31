<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Raspored modula, po v1 vizuelnim podešavanjima: šta stoji u donjem meniju,
 * a šta se otvara iz drawera. Ništa se ne sakriva — samo se premješta.
 */
class MenuSettings extends Settings
{
    /** @var string[] Ključevi modula u donjem meniju, redom. */
    public array $menu_modules;

    public static function group(): string
    {
        return 'menu';
    }

    /** Svi moduli koji se mogu premještati, sa naslovom i ikonom. */
    public static function modules(): array
    {
        return [
            'invoices' => ['title' => 'Računi', 'icon' => 'file-text', 'route' => 'invoices.index', 'pattern' => 'invoices.*'],
            'clients' => ['title' => 'Klijenti', 'icon' => 'contact', 'route' => 'clients.index', 'pattern' => 'clients.*'],
            'articles' => ['title' => 'Artikli', 'icon' => 'boxes', 'route' => 'articles.index', 'pattern' => 'articles.*'],
            'bank-accounts' => ['title' => 'Bankovni računi', 'icon' => 'credit-card', 'route' => 'bank-accounts.index', 'pattern' => 'bank-accounts.*'],
            'currencies' => ['title' => 'Valute', 'icon' => 'hash', 'route' => 'currencies.index', 'pattern' => 'currencies.*'],
        ];
    }

    /** Moduli koji nisu u meniju otvaraju se iz drawera. */
    public function drawerModules(): array
    {
        return array_values(array_diff(array_keys(self::modules()), $this->menu_modules));
    }
}
