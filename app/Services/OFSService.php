<?php

namespace App\Services;

use App\Settings\FiscalSettings;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OFS ESIR klijent — prenesen iz v1 (ppKalkulatron-api/app/Services/OFSService.php).
 *
 * Jedina razlika je odakle dolaze podešavanja: v1 ih čita iz podešavanja kompanije,
 * ovdje su u podešavanjima aplikacije, jer v2 nema model kompanije. Zaglavlja, putanje i
 * ponašanje su isti, i isti su testirani protiv prave kase.
 *
 * Poenta v2: ovaj poziv se izvršava iz PHP-a na uređaju, pa ograničenja preglednika
 * (mixed content, Private Network Access) ne važe i lokalni ESIR na http://192.168.x.x
 * je dostupan direktno.
 */
class OFSService
{
    protected string $baseUrl;

    public function __construct(
        ?string $baseUrl = null,
        protected ?string $apiKey = null,
        protected ?string $serialNumber = null,
        protected ?string $pac = null,
    ) {
        $settings = app(FiscalSettings::class);

        $this->baseUrl = rtrim($baseUrl ?: $settings->base_url, '/');
        $this->apiKey ??= $settings->api_key;
        $this->serialNumber ??= $settings->serial_number;
        $this->pac ??= $settings->pac;
    }

    /** Cloud traži sva tri; lokalni ESIR koristi samo Authorization. */
    protected function headers(): array
    {
        return array_filter([
            'Authorization' => $this->apiKey ? 'Bearer '.$this->apiKey : null,
            'X-Teron-SerialNumber' => $this->serialNumber,
            'X-PAC' => $this->pac,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    protected function request(string $method, string $path, array $payload = [], ?string $requestId = null): Response
    {
        $endpoint = $this->baseUrl.$path;

        $headers = $this->headers();
        if ($requestId !== null) {
            $headers['RequestId'] = $requestId;
        }

        Log::info('OFS request', ['method' => $method, 'url' => $endpoint, 'request_id' => $requestId]);

        // Uređaj na lokalnoj mreži ili odgovori na rukovanje odmah ili nije tu.
        // Bez ovoga se čeka Guzzle-ov podrazumijevani connect timeout, a jedan PHP
        // proces služi sve zahtjeve — aplikacija stoji dok se čeka.
        $http = Http::withHeaders($headers)->connectTimeout(2)->timeout(15);

        $response = $method === 'GET'
            ? $http->get($endpoint)
            : $http->send($method, $endpoint, ['json' => $payload]);

        Log::info('OFS response', ['status' => $response->status(), 'successful' => $response->successful()]);

        return $response;
    }

    /** GET /api/attention — 200 znači da je uređaj dostupan i konfigurisan. */
    public function testAttention(): Response
    {
        return $this->request('GET', '/api/attention');
    }

    public function getStatus(): Response
    {
        return $this->request('GET', '/api/status');
    }

    public function getSettings(): Response
    {
        return $this->request('GET', '/api/settings');
    }

    /**
     * POST /api/pin — PIN sigurnosnog elementa ide kao goli tekst, ne kao JSON.
     * Uređaj odgovara kodom: "0100" znači da je prihvaćen.
     */
    public function enterPin(string $pin): Response
    {
        $endpoint = $this->baseUrl.'/api/pin';

        Log::info('OFS request', ['method' => 'POST', 'url' => $endpoint]);

        $headers = $this->headers();
        unset($headers['Content-Type']);

        return Http::withHeaders($headers)->withBody($pin, 'text/plain')
            ->connectTimeout(2)->timeout(15)->post($endpoint);
    }

    public function createInvoice(array $payload, ?string $requestId = null): Response
    {
        return $this->request('POST', '/api/invoices', $payload, $requestId);
    }

    public function getInvoiceByRequestId(string $requestId): Response
    {
        return $this->request('GET', '/api/invoices/request/'.$requestId, [], $requestId);
    }
}
