<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Services\CompanyProfileImporter;
use App\Settings\CompanySettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class CompanySettingsController extends Controller
{
    public function edit(CompanySettings $settings): View
    {
        return view('settings.company', ['settings' => $settings]);
    }

    public function update(UpdateCompanySettingsRequest $request, CompanySettings $settings): RedirectResponse
    {
        $settings->fill($request->safe()->except(['is_small_entrepreneur', 'is_vat_obligor']));
        $settings->is_small_entrepreneur = $request->boolean('is_small_entrepreneur');
        $settings->is_vat_obligor = $request->boolean('is_vat_obligor');
        $settings->save();

        return redirect()->route('settings.company.edit')->with('status', 'Podaci kompanije su sačuvani.');
    }

    /** Naziv, adresa i JIB stoje na sertifikatu kase — nema potrebe da se prepisuju ručno. */
    public function importFromDevice(CompanyProfileImporter $importer): RedirectResponse
    {
        try {
            $result = $importer->import();
        } catch (RuntimeException $exception) {
            return redirect()->route('settings.company.edit')->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('settings.company.edit')
                ->with('error', 'Fiskalna kasa nije dostupna. Provjerite da je uključena i na istoj mreži.');
        }

        return redirect()->route('settings.company.edit')->with(
            'status',
            $result['changed'] === []
                ? 'Podaci sa kase se poklapaju sa unesenim — ništa nije mijenjano.'
                : 'Preuzeto sa kase: '.implode(', ', array_map(
                    fn (string $field): string => self::FIELD_LABELS[$field] ?? $field,
                    array_keys($result['changed']),
                )).'.',
        );
    }

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'name' => 'naziv',
        'address' => 'adresa',
        'city' => 'grad',
        'country' => 'država',
        'identification_number' => 'JIB',
    ];
}
