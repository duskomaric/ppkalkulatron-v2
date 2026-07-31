<?php

namespace App\Http\Controllers;

use App\Enums\PaymentType;
use App\Services\NetworkScanner;
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
            'cashier' => ['required', 'string', 'max:64'],
            'device_mode' => ['required', Rule::in(['cloud', 'local'])],
            'receipt_layout' => ['required', Rule::in(['Slip', 'Invoice'])],
            'receipt_image_format' => ['required', Rule::in(['Png', 'Pdf', 'Html'])],
            'default_payment_type' => ['required', Rule::enum(PaymentType::class)],
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

        return redirect()->route('settings.fiscal.edit')->with('status', 'Fiskalna podešavanja su sačuvana.');
    }

    /** Provjera dostupnosti uređaja — v1 ima tri dugmeta, ovdje su spojena u jedno. */
    public function test(FiscalSettings $settings)
    {
        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $attention = $ofs->testAttention();

            if (! $attention->successful()) {
                return redirect()->route('settings.fiscal.edit')->with('error', "Uređaj nije dostupan (HTTP {$attention->status()}).");
            }

            $status = $ofs->getStatus();
            $data = $status->json() ?? [];
            $gsc = array_map('strval', (array) ($data['gsc'] ?? []));

            if (in_array('1500', $gsc, true)) {
                return redirect()->route('settings.fiscal.edit')->with('error', 'Uređaj traži PIN sigurnosnog elementa.');
            }

            $labels = collect($data['currentTaxRates']['taxCategories'] ?? [])
                ->flatMap(fn ($c) => $c['taxRates'] ?? [])
                ->map(fn ($r) => ($r['label'] ?? '?').' '.($r['rate'] ?? '?').'%')
                ->implode(', ');

            return redirect()->route('settings.fiscal.edit')->with('status', 'Uređaj je dostupan. UID '.($data['uid'] ?? '—').
                ($labels ? '. Oznake: '.$labels : ''));
        } catch (\Throwable $e) {
            return redirect()->route('settings.fiscal.edit')->with('error', 'Greška: '.$e->getMessage());
        }
    }

    /** Uređaj vraća „0100" kada je PIN prihvaćen. */
    public const PIN_OK = '0100';

    /** Kod 1500 u /api/status → „gsc" znači da uređaj traži PIN. */
    public const PIN_REQUIRED_CODE = '1500';

    /** @var array<string, string> Kodovi koje /api/pin vraća na grešku. */
    public const PIN_ERRORS = [
        '1300' => 'Sigurnosni element nije prisutan u uređaju.',
        '2400' => 'Lokalni ESIR (LPFR) nije spreman.',
        '2800' => 'PIN nije u ispravnom formatu — očekuje se 4 cifre.',
        '2806' => 'PIN nije u ispravnom formatu — očekuje se 4 cifre.',
    ];

    /** Potraga za ESIR-om na lokalnoj mreži; odgovor je JSON jer traje. */
    public function scan(Request $request, NetworkScanner $scanner, FiscalSettings $settings)
    {
        $data = $request->validate(['range' => ['nullable', 'string', 'max:32']]);
        $range = trim((string) ($data['range'] ?? ''));

        if ($range !== '' && $scanner->parseRange($range) === []) {
            return response()->json([
                'message' => 'Opseg nije prepoznat. Primjer: 192.168.31.100-105 ili 192.168.31.',
            ], 422);
        }

        if ($range === '' && ! $scanner->localIp()) {
            return response()->json([
                'message' => 'Nije moguće pročitati lokalnu adresu uređaja. Unesite opseg ručno.',
            ], 422);
        }

        $found = $scanner->scan($range ?: null, $settings->api_key);

        return response()->json([
            'devices' => $found,
            'message' => $found === []
                ? 'Nijedan uređaj nije pronađen na mreži.'
                : 'Pronađeno uređaja: '.count($found).'.',
        ]);
    }

    /** PIN sigurnosnog elementa; uređaj ga traži poslije napajanja. */
    public function pin(Request $request, FiscalSettings $settings)
    {
        $data = $request->validate(['security_pin' => ['required', 'digits:4']], [], ['security_pin' => 'PIN']);

        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $response = $ofs->enterPin($data['security_pin']);
            $code = trim($response->body(), " \t\n\r\0\x0B\"");

            if ($response->successful() && $code === self::PIN_OK) {
                return redirect()->route('settings.fiscal.edit')->with('status', 'PIN je prihvaćen. Uređaj je spreman za fiskalizaciju.');
            }

            return redirect()->route('settings.fiscal.edit')->with('error', self::PIN_ERRORS[$code] ?? "Uređaj je odbio PIN (kod {$code}).");
        } catch (\Throwable $e) {
            return redirect()->route('settings.fiscal.edit')->with('error', 'Greška: '.$e->getMessage());
        }
    }

    /** Potraga za izgubljenim odgovorom uređaja po RequestId-u. */
    public function findRequest(Request $request, FiscalSettings $settings)
    {
        $data = $request->validate(
            ['request_id' => ['required', 'string', 'max:32']], [], ['request_id' => 'RequestId']
        );

        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $response = $ofs->getInvoiceByRequestId($data['request_id']);

            if (! $response->successful()) {
                return redirect()->route('settings.fiscal.edit')->with('error', "Uređaj nije odgovorio (HTTP {$response->status()}).");
            }

            $found = (array) $response->json();

            return redirect()->route('settings.fiscal.edit')->with('status', empty($found)
                ? 'Zahtjev nije pronađen — fiskalizacija vjerovatno nije prošla.'
                : 'Pronađen račun '.($found['invoiceNumber'] ?? '—').', brojač '.($found['invoiceCounter'] ?? '—').'.');
        } catch (\Throwable $e) {
            return redirect()->route('settings.fiscal.edit')->with('error', 'Greška: '.$e->getMessage());
        }
    }
}
