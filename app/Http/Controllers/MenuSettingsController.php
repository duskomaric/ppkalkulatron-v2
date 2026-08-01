<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMenuSettingsRequest;
use App\Settings\MenuSettings;

class MenuSettingsController extends Controller
{
    public function edit(MenuSettings $settings)
    {
        return view('settings.menu', [
            'settings' => $settings,
            'modules' => MenuSettings::modules(),
            'moduleOptions' => array_map(
                fn (string $key) => ['key' => $key, ...MenuSettings::modules()[$key]],
                $settings->orderedModules(),
            ),
        ]);
    }

    public function update(UpdateMenuSettingsRequest $request, MenuSettings $settings)
    {
        $keys = array_keys(MenuSettings::modules());
        $data = $request->validated();

        $menuModules = array_values(array_unique(array_filter(
            $data['menu_modules'] ?? [],
            fn (string $key) => in_array($key, $keys, true),
        )));
        $drawerModules = array_values(array_unique(array_filter(
            $data['drawer_modules'] ?? [],
            fn (string $key) => in_array($key, $keys, true),
        )));
        $menuModules = array_slice($menuModules, 0, $data['max_menu_items']);

        $settings->menu_modules = $menuModules;
        $settings->drawer_modules = array_values(array_diff($drawerModules, $menuModules));
        $settings->max_menu_items = $data['max_menu_items'];
        $settings->save();

        return redirect()->route('settings.menu.edit')->with('status', 'Raspored menija je sačuvan.');
    }
}
