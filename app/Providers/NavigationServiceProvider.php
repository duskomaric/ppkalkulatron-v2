<?php

namespace App\Providers;

use App\Settings\MenuSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Stavke menija — isti redoslijed i naslovi kao u v1.
 *
 * Svi moduli su vidljivi; vizuelna podešavanja samo biraju šta stoji u donjem
 * meniju, a šta se otvara iz drawera.
 */
class NavigationServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer(
            ['layouts.app', 'components.app-header', 'components.bottom-nav', 'components.settings-drawer'],
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
                    'navItems' => array_map($item, array_values(array_intersect(array_keys($modules), $settings->menu_modules))),
                    'drawerItems' => array_map($item, $settings->drawerModules()),
                ]);
            }
        );
    }
}
