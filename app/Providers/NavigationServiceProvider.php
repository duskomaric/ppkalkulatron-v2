<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Stavke menija — isti redoslijed i naslovi kao u v1.
 * Korisnik vidi sve module; nema skrivanja po kompaniji jer nema kompanija.
 */
class NavigationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(['layouts.app', 'components.app-header', 'components.bottom-nav'], function ($view) {
            $view->with('navItems', [
                [
                    'title' => 'Računi',
                    'icon' => 'file-text',
                    'href' => route('invoices.index'),
                    'active' => request()->routeIs('invoices.*'),
                ],
                [
                    'title' => 'Klijenti',
                    'icon' => 'contact',
                    'href' => route('clients.index'),
                    'active' => request()->routeIs('clients.*'),
                ],
                [
                    'title' => 'Artikli',
                    'icon' => 'boxes',
                    'href' => route('articles.index'),
                    'active' => request()->routeIs('articles.*'),
                ],
            ]);
        });
    }
}
