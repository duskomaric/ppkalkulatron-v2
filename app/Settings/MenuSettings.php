<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Raspored modula u donjem meniju i dodatnoj navigaciji. Ništa se ne sakriva —
 * samo se premješta.
 */
class MenuSettings extends Settings
{
    /** @var string[] Ključevi modula u donjem meniju, redom. */
    public array $menu_modules;

    /** @var string[] Ključevi modula u dodatnoj navigaciji, redom. */
    public array $drawer_modules;

    /** Najveći broj stavki u donjem meniju prije grupe „Više“. */
    public int $max_menu_items;

    /** Glavna boja aplikacije, iz palete u App\Support\Brand. */
    public string $primary_color;

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
        $keys = array_keys(self::modules());
        $menu = array_values(array_filter(
            $this->menu_modules,
            fn (string $key) => in_array($key, $keys, true),
        ));
        $drawer = array_values(array_filter(
            $this->drawer_modules,
            fn (string $key) => in_array($key, $keys, true),
        ));

        return array_values(array_unique(array_merge(
            array_diff($drawer, $menu),
            array_diff($keys, $menu, $drawer),
        )));
    }

    /** Redoslijed koji se koristi na ekranu podešavanja i u navigaciji. */
    public function orderedModules(): array
    {
        $keys = array_keys(self::modules());
        $menu = array_values(array_filter(
            $this->menu_modules,
            fn (string $key) => in_array($key, $keys, true),
        ));

        return array_values(array_unique(array_merge($menu, $this->drawerModules())));
    }
}
