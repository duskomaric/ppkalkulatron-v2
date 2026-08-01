<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Settings\CompanySettings;

class CompanySettingsController extends Controller
{
    public function edit(CompanySettings $settings)
    {
        return view('settings.company', ['settings' => $settings]);
    }

    public function update(UpdateCompanySettingsRequest $request, CompanySettings $settings)
    {
        $settings->fill($request->safe()->except(['is_small_entrepreneur', 'is_vat_obligor']));
        $settings->is_small_entrepreneur = $request->boolean('is_small_entrepreneur');
        $settings->is_vat_obligor = $request->boolean('is_vat_obligor');
        $settings->save();

        return redirect()->route('settings.company.edit')->with('status', 'Podaci kompanije su sačuvani.');
    }
}
