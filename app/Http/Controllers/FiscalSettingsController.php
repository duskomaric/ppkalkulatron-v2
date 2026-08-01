<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnterFiscalPinRequest;
use App\Http\Requests\FindFiscalRequestRequest;
use App\Http\Requests\ScanFiscalNetworkRequest;
use App\Http\Requests\UpdateFiscalSettingsRequest;
use App\Services\FiscalDeviceHealth;
use App\Services\NetworkScanner;
use App\Services\OFSService;
use App\Settings\FiscalSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use RuntimeException;
use Throwable;

class FiscalSettingsController extends Controller
{
    public function edit(FiscalSettings $settings, FiscalDeviceHealth $health)
    {
        return view('settings.fiscal', [
            'settings' => $settings,
            'fiscalHealth' => $health->current(),
        ]);
    }

    public function update(UpdateFiscalSettingsRequest $request, FiscalSettings $settings, FiscalDeviceHealth $health)
    {
        $data = $request->validated();

        $settings->fill(collect($data)->except([
            'receipt_header_text_lines',
            'wholesale',
            'print_receipt',
        ])->all());
        $settings->receipt_header_text_lines = collect(explode("\n", (string) $request->input('receipt_header_text_lines')))
            ->map(fn ($line) => trim($line))->filter()->values()->all();
        $settings->wholesale = $request->boolean('wholesale');
        $settings->print_receipt = $request->boolean('print_receipt');
        $settings->save();
        $health->forget();

        return redirect()->route('settings.fiscal.edit')->with('status', 'Fiskalna podešavanja su sačuvana.');
    }

    public function status(FiscalDeviceHealth $health): JsonResponse
    {
        return response()->json($health->refreshIfStale());
    }

    /** Provjera dostupnosti i poreskih oznaka uređaja. */
    public function test(FiscalSettings $settings, FiscalDeviceHealth $health)
    {
        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $attention = $ofs->testAttention();

            if (! $attention->successful()) {
                $health->markUnavailable();

                return redirect()->route('settings.fiscal.edit')->with('error', "Uređaj nije dostupan (HTTP {$attention->status()}).");
            }

            $status = $ofs->getStatus();

            if (! $status->successful()) {
                $health->markUnavailable();

                return redirect()->route('settings.fiscal.edit')->with('error', "Uređaj nije dostupan (HTTP {$status->status()}).");
            }

            $data = $status->json() ?? [];
            $gsc = array_map('strval', (array) ($data['gsc'] ?? []));

            if (in_array('1500', $gsc, true)) {
                $health->markPinRequired();

                return redirect()->route('settings.fiscal.edit')->with('error', 'Uređaj traži PIN sigurnosnog elementa.');
            }

            $health->markReady();

            $labels = collect($data['currentTaxRates']['taxCategories'] ?? [])
                ->flatMap(fn ($c) => $c['taxRates'] ?? [])
                ->map(fn ($r) => ($r['label'] ?? '?').' '.($r['rate'] ?? '?').'%')
                ->implode(', ');

            return redirect()->route('settings.fiscal.edit')->with('status', 'Uređaj je dostupan. UID '.($data['uid'] ?? '—').
                ($labels ? '. Oznake: '.$labels : ''));
        } catch (Throwable $e) {
            $health->markUnavailable();

            return $this->fiscalError($e);
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
    public function scan(ScanFiscalNetworkRequest $request, NetworkScanner $scanner, FiscalSettings $settings)
    {
        $data = $request->validated();
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
    public function pin(EnterFiscalPinRequest $request, FiscalSettings $settings)
    {
        $data = $request->validated();

        try {
            $ofs = new OFSService($settings->base_url, $settings->api_key, $settings->serial_number, $settings->pac);
            $response = $ofs->enterPin($data['security_pin']);
            $code = trim($response->body(), " \t\n\r\0\x0B\"");

            if ($response->successful() && $code === self::PIN_OK) {
                return redirect()->route('settings.fiscal.edit')->with('status', 'PIN je prihvaćen. Uređaj je spreman za fiskalizaciju.');
            }

            return redirect()->route('settings.fiscal.edit')->with('error', self::PIN_ERRORS[$code] ?? "Uređaj je odbio PIN (kod {$code}).");
        } catch (Throwable $e) {
            return $this->fiscalError($e);
        }
    }

    /** Potraga za izgubljenim odgovorom uređaja po RequestId-u. */
    public function findRequest(FindFiscalRequestRequest $request, FiscalSettings $settings)
    {
        $data = $request->validated();

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
        } catch (Throwable $e) {
            return $this->fiscalError($e);
        }
    }

    private function fiscalError(Throwable $exception): RedirectResponse
    {
        if ($exception instanceof RuntimeException) {
            return redirect()->route('settings.fiscal.edit')->with('error', $exception->getMessage());
        }

        report($exception);

        return redirect()->route('settings.fiscal.edit')
            ->with('error', 'Fiskalni uređaj trenutno nije dostupan. Pokušajte ponovo.');
    }
}
