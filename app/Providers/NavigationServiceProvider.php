<?php

namespace App\Providers;

use App\Settings\MenuSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Svi moduli ostaju dostupni; podešavanja biraju šta stoji u donjem meniju, a
 * šta se otvara iz dodatne navigacije.
 */
class NavigationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(
            ['layouts.app', 'components.app-header', 'components.bottom-nav', 'components.module-drawer', 'components.settings-drawer'],
            function ($view) {
                $modules = MenuSettings::modules();
                $settings = app(MenuSettings::class);

                $item = fn (string $key) => [
                    'key' => $key,
                    'title' => $modules[$key]['title'],
                    'icon' => $modules[$key]['icon'],
                    'href' => route($modules[$key]['route']),
                    'active' => request()->routeIs($modules[$key]['pattern']),
                ];

                $view->with([
                    'navItems' => array_map($item, array_slice($settings->menu_modules, 0, $settings->max_menu_items)),
                    'drawerItems' => array_map($item, $settings->drawerModules()),
                ]);
            }
        );
    }
}
