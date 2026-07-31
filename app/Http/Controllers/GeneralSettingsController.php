<?php

namespace App\Http\Controllers;

use App\Settings\DocumentSettings;
use App\Settings\NumberingSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeneralSettingsController extends Controller
{
    public function edit(NumberingSettings $numbering, DocumentSettings $document)
    {
        return view('settings.general', compact('numbering', 'document'));
    }

    public function update(Request $request, NumberingSettings $numbering, DocumentSettings $document)
    {
        $data = $request->validate([
            'pad_zeros' => ['required', 'integer', 'min:1', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:16'],
            'invoice_starting_number' => ['required', 'integer', 'min:1'],
            'proforma_prefix' => ['nullable', 'string', 'max:16'],
            'proforma_starting_number' => ['required', 'integer', 'min:1'],
            'quote_prefix' => ['nullable', 'string', 'max:16'],
            'quote_starting_number' => ['required', 'integer', 'min:1'],
            'template' => ['required', Rule::in(['classic', 'modern', 'minimal', 'standard'])],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
        ], [], [
            'pad_zeros' => 'broj nula', 'template' => 'predložak',
            'invoice_due_days' => 'rok plaćanja', 'invoice_notes' => 'napomena',
        ]);

        $numbering->fill(collect($data)->only([
            'pad_zeros', 'invoice_prefix', 'invoice_starting_number',
            'proforma_prefix', 'proforma_starting_number', 'quote_prefix', 'quote_starting_number',
        ])->map(fn ($v, $k) => str_contains($k, 'prefix') ? (string) $v : $v)->all());
        $numbering->reset_yearly = $request->boolean('reset_yearly');
        $numbering->save();

        $document->fill(collect($data)->only(['template', 'invoice_due_days', 'invoice_notes'])->all());
        $document->save();

        return back()->with('status', 'Podešavanja su sačuvana.');
    }
}
