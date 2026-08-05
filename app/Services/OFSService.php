<?php

namespace App\Services;

use App\Settings\FiscalSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * OFS ESIR klijent. Poziv se izvršava iz PHP-a na uređaju, pa je lokalni ESIR na
 * privatnoj HTTP adresi dostupan direktno.
 */
class OFSService
{
    /**
     * Uređaj na lokalnoj mreži ili odgovori na rukovanje odmah ili nije tu.
     *
     * Bez ovoga se čeka Guzzle-ov podrazumijevani connect timeout, a jedan PHP
     * proces služi sve zahtjeve — aplikacija stoji dok se čeka.
     */
    private const CONNECT_TIMEOUT = 2;

    private const TIMEOUT = 15;

    /** Pretraga prolazi kroz bazu izdatih računa, pa traje duže od pojedinačnog poziva. */
    private const SEARCH_TIMEOUT = 60;

    protected string $baseUrl;

    private bool $usesCloud;

    public function __construct(
        private FiscalSettings $settings,
        private Diagnostics $diagnostics,
    ) {
        $this->baseUrl = rtrim($settings->base_url, '/');
        $this->usesCloud = $settings->device_mode === 'cloud';
    }

    /**
     * Klon za provjeru druge kase iz komandne linije. Aplikacija inače uvijek
     * koristi vezu iz fiskalnih podešavanja.
     */
    public function withOverrides(?string $baseUrl = null, ?string $apiKey = null, ?string $serialNumber = null, ?string $pac = null): self
    {
        $service = clone $this;
        $service->baseUrl = rtrim($baseUrl ?: $this->settings->base_url, '/');
        $service->apiKey = $apiKey ?: $this->settings->api_key;
        $service->serialNumber = $serialNumber ?: $this->settings->serial_number;
        $service->pac = $pac ?: $this->settings->pac;

        return $service;
    }

    /** Cloud traži dodatne identifikatore; lokalnom ESIR-u se šalje samo API ključ. */
    protected function headers(): array
    {
        return array_filter([
            'Authorization' => $this->apiKey() ? 'Bearer '.$this->apiKey() : null,
            'X-Teron-SerialNumber' => $this->usesCloud ? $this->serialNumber() : null,
            'X-PAC' => $this->usesCloud ? $this->pac() : null,
            'Content-Type' => 'application/json; charset=UTF-8',
            'Accept' => 'application/json',
        ]);
    }

    private ?string $apiKey = null;

    private ?string $serialNumber = null;

    private ?string $pac = null;

    private function apiKey(): ?string
    {
        return $this->apiKey ?? $this->settings->api_key;
    }

    private function serialNumber(): ?string
    {
        return $this->serialNumber ?? $this->settings->serial_number;
    }

    private function pac(): ?string
    {
        return $this->pac ?? $this->settings->pac;
    }

    protected function client(array $headers, int $timeout = self::TIMEOUT): PendingRequest
    {
        return Http::withHeaders($headers)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout($timeout);
    }

    /**
     * Nedostupan uređaj je domenska greška, ne golo cURL izuzeće.
     *
     * Inače korisnik na ekranu dobije „cURL error 7: Failed to connect to …", a
     * fiskalizacija vrati HTTP 500 umjesto uputstva šta da provjeri. Poziv se ne
     * ponavlja: /api/invoices nije idempotentan, pa bi ponovni pokušaj mogao
     * odštampati drugi fiskalni račun.
     */
    protected function attempt(callable $call): Response
    {
        try {
            return $call();
        } catch (ConnectionException $e) {
            $this->diagnostics->error('OFS uređaj nije dostupan', ['url' => $this->baseUrl, 'error' => $e->getMessage()]);

            throw new RuntimeException(
                'Fiskalna kasa nije dostupna. '.
                'Provjerite da je uključena i na istoj mreži, pa pokušajte ponovo.'
            );
        }
    }

    protected function request(string $method, string $path, array $payload = [], ?string $requestId = null, int $timeout = self::TIMEOUT, ?string $accept = null): Response
    {
        $endpoint = $this->baseUrl.$path;

        $headers = $this->headers();
        if ($requestId !== null) {
            $headers['RequestId'] = $requestId;
        }

        if ($accept !== null) {
            $headers['Accept'] = $accept;
        }

        $this->diagnostics->debug('OFS request', ['method' => $method, 'url' => $endpoint, 'request_id' => $requestId]);

        $http = $this->client($headers, $timeout);

        $response = $this->attempt(fn () => $method === 'GET'
            ? $http->get($endpoint)
            : $http->send($method, $endpoint, ['json' => $payload]));

        $this->diagnostics->debug('OFS response', ['status' => $response->status(), 'successful' => $response->successful()]);

        return $response;
    }

    /** GET /api/attention — 200 znači da je uređaj dostupan i konfigurisan. */
    public function testAttention(int $timeout = self::TIMEOUT): Response
    {
        return $this->request('GET', '/api/attention', timeout: $timeout);
    }

    public function getStatus(int $timeout = self::TIMEOUT): Response
    {
        return $this->request('GET', '/api/status', timeout: $timeout);
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

        $this->diagnostics->debug('OFS request', ['method' => 'POST', 'url' => $endpoint]);

        $headers = $this->headers();
        unset($headers['Content-Type']);

        return $this->attempt(fn () => $this->client($headers)
            ->withBody($pin, 'text/plain')
            ->post($endpoint));
    }

    public function createInvoice(array $payload, ?string $requestId = null): Response
    {
        return $this->request('POST', '/api/invoices', $payload, $requestId);
    }

    public function getInvoiceByRequestId(string $requestId): Response
    {
        return $this->request('GET', '/api/invoices/request/'.$requestId, [], $requestId);
    }

    /**
     * POST /api/invoices/search — odgovor je CSV (broj, tip, tip transakcije,
     * vrijeme, iznos), a uređaj na `Accept: application/json` vrati 406/500.
     *
     * @param  array<string, mixed>  $filters  fromDate, toDate, invoiceTypes, transactionTypes…
     */
    public function searchInvoices(array $filters): Response
    {
        return $this->request('POST', '/api/invoices/search', $filters, timeout: self::SEARCH_TIMEOUT, accept: '*/*');
    }

    /**
     * GET /api/invoices/{broj} — puni sadržaj računa: poslati zahtjev sa stavkama i
     * kupcem, odgovor uređaja i, po traženom formatu, slika računa.
     *
     * @param  array<string, string|bool>  $query  receiptLayout, imageFormat, includeHeaderAndFooter
     */
    public function getInvoice(string $invoiceNumber, array $query = []): Response
    {
        $path = '/api/invoices/'.rawurlencode($invoiceNumber);

        if ($query !== []) {
            $path .= '?'.http_build_query($query);
        }

        return $this->request('GET', $path, accept: '*/*');
    }
}
