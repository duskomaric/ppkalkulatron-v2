<?php

namespace App\Http\Controllers;

use App\Settings\MenuSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MenuSettingsController extends Controller
{
    public function edit(MenuSettings $settings)
    {
        return view('settings.menu', [
            'settings' => $settings,
            'modules' => MenuSettings::modules(),
        ]);
    }

    public function update(Request $request, MenuSettings $settings)
    {
        $keys = array_keys(MenuSettings::modules());

        $data = $request->validate([
            'menu_modules' => ['array', 'max:5'],
            'menu_modules.*' => [Rule::in($keys)],
        ], [], ['menu_modules' => 'moduli u meniju']);

        // Redoslijed prati definiciju modula, da meni ne skače po redoslijedu čekiranja.
        $settings->menu_modules = array_values(array_intersect($keys, $data['menu_modules'] ?? []));
        $settings->save();

        return redirect()->route('settings.menu.edit')->with('status', 'Raspored menija je sačuvan.');
    }
}
