<?php

namespace App\Http\Controllers;

use App\Http\Requests\EnterFiscalPinRequest;
use App\Http\Requests\FindFiscalRequestRequest;
use App\Http\Requests\ScanFiscalNetworkRequest;
use App\Http\Requests\UpdateFiscalSettingsRequest;
use App\Models\FiscalTaxRate;
use App\Services\FiscalDeviceHealth;
use App\Services\FiscalTaxRateSynchronizer;
use App\Services\NetworkScanner;
use App\Services\OFSService;
use App\Settings\FiscalSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Throwable;

class FiscalSettingsController extends Controller
{
    public function edit(FiscalSettings $settings, FiscalDeviceHealth $health, NetworkScanner $scanner)
    {
        $localIp = $scanner->localIp();

        return view('settings.fiscal', [
            'settings' => $settings,
            'fiscalHealth' => $health->current(),
            'taxRates' => FiscalTaxRate::query()->orderBy('category_name')->orderBy('label')->get(),
            // Prikaz toka skeniranja: koja mreža se pregleda i kojim rokovima.
            'scanSubnet' => $localIp ? implode('.', array_slice(explode('.', $localIp), 0, 3)).'.1–254' : null,
            'scanDeadlines' => NetworkScanner::deadlines(),
        ]);
    }

    public function update(UpdateFiscalSettingsRequest $request, FiscalSettings $settings, FiscalDeviceHealth $health)
    {
        $data = $request->validated();

        // Stope pripadaju konkretnoj kasi. Kad se poveže druga, stare više ne važe —
        // artikli bi inače nosili oznake kojih na novoj kasi nema.
        $deviceChanged = collect(['base_url', 'serial_number', 'device_mode'])
            ->contains(fn (string $key): bool => ($data[$key] ?? $settings->{$key}) !== $settings->{$key});

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

        if ($deviceChanged && FiscalTaxRate::query()->exists()) {
            FiscalTaxRate::query()->delete();

            return redirect()->route('settings.fiscal.edit')
                ->with('status', 'Fiskalna podešavanja su sačuvana. Stope prethodne kase su uklonjene — preuzmite stope nove kase.');
        }

        return redirect()->route('settings.fiscal.edit')->with('status', 'Fiskalna podešavanja su sačuvana.');
    }

    /** Provjera dostupnosti uređaja bez izmjene lokalnog kataloga poreskih stopa. */
    public function test(FiscalDeviceHealth $health, OFSService $ofs): RedirectResponse
    {
        try {
            $attention = $ofs->testAttention();

            if (! $attention->successful()) {
                $health->markUnavailable();

                return redirect()->route('settings.fiscal.edit')->with('error', 'Fiskalna kasa nije dostupna. Provjerite mrežnu vezu i podatke za pristup.');
            }

            $status = $ofs->getStatus();

            if (! $status->successful()) {
                $health->markUnavailable();

                return redirect()->route('settings.fiscal.edit')->with('error', 'Fiskalna kasa trenutno nije spremna za fiskalizaciju. Provjerite status uređaja i PIN.');
            }

            $data = $status->json() ?? [];
            $gsc = array_map('strval', (array) ($data['gsc'] ?? []));

            if (in_array('1500', $gsc, true)) {
                $health->markPinRequired();

                return redirect()->route('settings.fiscal.edit')->with('error', 'Uređaj traži PIN sigurnosnog elementa.');
            }

            $health->markReady();

            return redirect()->route('settings.fiscal.edit')->with('status', 'Fiskalna kasa je dostupna i spremna za fiskalizaciju.');
        } catch (Throwable $e) {
            $health->markUnavailable();

            return $this->fiscalError($e);
        }
    }

    public function syncTaxRates(Request $request, FiscalDeviceHealth $health, FiscalTaxRateSynchronizer $taxRates): RedirectResponse
    {
        try {
            $synced = $taxRates->syncFromDevice();
            $health->markReady();

            return redirect()->route($request->input('return_to') === 'articles' ? 'articles.create' : 'settings.fiscal.edit')
                ->with('status', "Preuzeto poreskih stopa: {$synced['count']}.");
        } catch (RuntimeException $exception) {
            $health->markUnavailable();

            return redirect()->back()->with('error', $exception->getMessage());
        } catch (Throwable $exception) {
            $health->markUnavailable();

            return $this->fiscalError($exception);
        }
    }

    /** Uređaj vraća „0100" kada je PIN prihvaćen. */
    public const PIN_OK = '0100';

    /** Kod 1500 u /api/status → „gsc" znači da uređaj traži PIN. */
    public const PIN_REQUIRED_CODE = '1500';

    /** @var array<string, string> Kodovi koje /api/pin vraća na grešku. */
    public const PIN_ERRORS = [
        '1300' => 'Sigurnosni element nije prisutan u uređaju.',
        '2400' => 'Fiskalna kasa još nije spremna. Sačekajte trenutak, pa pokušajte ponovo.',
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

        // Ranije upisana adresa se provjeri prva: kasa koja spava se često javi iz drugog pokušaja.
        $known = $range === '' ? $this->knownHost($settings->base_url) : null;
        $found = $known ? array_filter([$scanner->probeKnown($known, $settings->api_key)]) : [];

        if ($found === []) {
            $found = $scanner->scan($range ?: null, $settings->api_key);
        }

        return response()->json([
            'devices' => $found,
            // Izvještaj se prikazuje ispod dugmeta: šta je pregledano i koliko je trajalo.
            'report' => $scanner->report(),
            'message' => $found === []
                ? 'Nijedan uređaj nije pronađen. Provjerite da su ovaj uređaj i kasa na istoj mreži, ili unesite opseg ručno.'
                : 'Pronađeno uređaja: '.count($found).'.',
        ]);
    }

    /** Adresa iz podešavanja, ako je uopšte adresa na lokalnoj mreži. */
    private function knownHost(?string $baseUrl): ?string
    {
        $host = parse_url((string) $baseUrl, PHP_URL_HOST);

        return is_string($host) && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? $host : null;
    }

    /** PIN sigurnosnog elementa; uređaj ga traži poslije napajanja. */
    public function pin(EnterFiscalPinRequest $request, OFSService $ofs)
    {
        $data = $request->validated();

        try {
            $response = $ofs->enterPin($data['security_pin']);
            $code = trim($response->body(), " \t\n\r\0\x0B\"");

            if ($response->successful() && $code === self::PIN_OK) {
                return redirect()->route('settings.fiscal.edit')->with('status', 'PIN je prihvaćen. Fiskalna kasa je spremna za fiskalizaciju.');
            }

            return redirect()->route('settings.fiscal.edit')->with('error', self::PIN_ERRORS[$code] ?? 'Fiskalna kasa nije prihvatila PIN. Provjerite ga i pokušajte ponovo.');
        } catch (Throwable $e) {
            return $this->fiscalError($e);
        }
    }

    /** Potraga za izgubljenim odgovorom uređaja po RequestId-u. */
    public function findRequest(FindFiscalRequestRequest $request, OFSService $ofs)
    {
        $data = $request->validated();

        try {
            $response = $ofs->getInvoiceByRequestId($data['request_id']);

            if (! $response->successful()) {
                return redirect()->route('settings.fiscal.edit')->with('error', 'Prethodni zahtjev nije moguće provjeriti. Provjerite vezu sa kasom, pa pokušajte ponovo.');
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
