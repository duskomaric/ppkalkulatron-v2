<?php

namespace App\Http\Controllers;

use App\Settings\CompanySettings;
use Illuminate\Http\Request;

class CompanySettingsController extends Controller
{
    public function edit(CompanySettings $settings)
    {
        return view('settings.company', ['settings' => $settings]);
    }

    public function update(Request $request, CompanySettings $settings)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'max:120'],
            'identification_number' => ['nullable', 'string', 'max:32'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'small_entrepreneur_note' => ['nullable', 'string', 'max:255'],
        ], [], [
            'name' => 'naziv kompanije', 'email' => 'email', 'phone' => 'telefon',
            'address' => 'adresa', 'city' => 'grad', 'zip' => 'poštanski broj',
            'country' => 'država', 'identification_number' => 'JIB', 'vat_number' => 'PIB',
            'small_entrepreneur_note' => 'napomena',
        ]);

        $settings->fill($data);
        $settings->is_small_entrepreneur = $request->boolean('is_small_entrepreneur');
        $settings->is_vat_obligor = $request->boolean('is_vat_obligor');
        $settings->save();

        return back()->with('status', 'Podaci kompanije su sačuvani.');
    }
}
