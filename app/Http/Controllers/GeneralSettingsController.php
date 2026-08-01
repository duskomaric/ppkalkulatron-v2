<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\NumberingSettings;

class GeneralSettingsController extends Controller
{
    public function edit(NumberingSettings $numbering, DocumentSettings $document, CompanySettings $company)
    {
        return view('settings.general', compact('numbering', 'document', 'company'));
    }

    public function update(UpdateGeneralSettingsRequest $request, NumberingSettings $numbering, DocumentSettings $document)
    {
        $data = $request->validated();

        $numbering->fill(collect($data)->only([
            'pad_zeros', 'invoice_prefix', 'invoice_starting_number',
        ])->map(fn ($v, $k) => str_contains($k, 'prefix') ? (string) $v : $v)->all());
        $numbering->reset_yearly = $request->boolean('reset_yearly');
        $numbering->save();

        $document->fill(collect($data)->only(['template', 'language', 'invoice_due_days', 'invoice_notes'])->all());
        $document->save();

        return redirect()->route('settings.general.edit')->with('status', 'Podešavanja su sačuvana.');
    }
}
