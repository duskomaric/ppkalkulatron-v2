<?php

namespace App\Http\Controllers;

use App\Services\OFSService;
use App\Settings\FiscalSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class FiscalSettingsController extends Controller
{
    public function edit(FiscalSettings $settings)
    {
        return view('settings.fiscal', ['settings' => $settings]);
    }

    public function update(Request $request, FiscalSettings $settings)
    {
        $data = $request->validate([
            'base_url' => ['required', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'pac' => ['nullable', 'string', 'max:32'],
            'device_mode' => ['required', Rule::in(['cloud', 'local'])],
            'receipt_layout' => ['required', Rule::in(['Slip', 'Invoice'])],
            'receipt_image_format' => ['required', Rule::in(['Png', 'Pdf', 'Html'])],
            'default_payment_type' => ['required', Rule::enum(\App\Enums\PaymentType::class)],
            'receipt_header_text_lines' => ['nullable', 'string'],
        ], [], [
            'base_url' => 'base URL', 'device_mode' => 'način uređaja',
            'receipt_layout' => 'izgled računa', 'receipt_image_format' => 'format slike',
        ]);

        // A4 layout nema PNG renderer — uređaj vrati praznu jednopikselnu sliku.
        $allowed = $data['receipt_layout'] === 'Invoice' ? ['Pdf', 'Html'] : ['Png', 'Pdf', 'Html'];

        if (! in_array($data['receipt_image_format'], $allowed, true)) {
            throw ValidationException::withMessages([
                'receipt_image_format' => sprintf(
                    'Izgled "%s" ne podržava format "%s". Dozvoljeno: %s.',
                    $data['receipt_layout'], $data['receipt_image_format'], implode(', ', $allowed),
                ),
            ]);
        }

        $settings->fill(collect($data)->except('receipt_header_text_lines')->all());
        $settings->receipt_header_text_lines = collect(explode("\n", (string) $request->input('receipt_header_text_lines')))
            ->map(fn ($line) => trim($line))->filter()->values()->all();
        $settings->wholesale = $request->boolean('wholesale');
        $settings->render_receipt_image = $request->boolean('render_receipt_image');
        $settings->print_receipt = $request->boolean('print_receipt');
        $settings->save();

        return back()->with('status', 'Fiskalna podešavanja su sačuvana.');
    }

    /** Provjera dostupnosti uređaja — v1 ima tri dugmeta, ovdje su spojena u jedno. */
    public function test(FiscalSettings $settings)
    {
        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $attention = $ofs->testAttention();

            if (! $attention->successful()) {
                return back()->with('error', "Uređaj nije dostupan (HTTP {$attention->status()}).");
            }

            $status = $ofs->getStatus();
            $data = $status->json() ?? [];
            $gsc = array_map('strval', (array) ($data['gsc'] ?? []));

            if (in_array('1500', $gsc, true)) {
                return back()->with('error', 'Uređaj traži PIN sigurnosnog elementa.');
            }

            $labels = collect($data['currentTaxRates']['taxCategories'] ?? [])
                ->flatMap(fn ($c) => $c['taxRates'] ?? [])
                ->map(fn ($r) => ($r['label'] ?? '?').' '.($r['rate'] ?? '?').'%')
                ->implode(', ');

            return back()->with('status', 'Uređaj je dostupan. UID '.($data['uid'] ?? '—').
                ($labels ? '. Oznake: '.$labels : ''));
        } catch (\Throwable $e) {
            return back()->with('error', 'Greška: '.$e->getMessage());
        }
    }
}
