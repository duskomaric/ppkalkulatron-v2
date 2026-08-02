<?php

namespace App\Services;

use Illuminate\Http\Client\Response;

/**
 * Prevodi tehnički odgovor ESIR-a u uputstvo koje je sigurno prikazati korisniku.
 * Izvorni odgovor ostaje samo u redigovanoj dijagnostici.
 */
class FiscalDeviceErrorMessage
{
    public function forInvoice(Response $response): string
    {
        if ($this->hasInvoiceResponse($response)) {
            return 'Račun je fiskalizovan, ali štampa nije uspjela. Provjerite štampač i papir; ne šaljite račun ponovo prije provjere prethodnog zahtjeva u Fiskalizaciji.';
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return 'Fiskalni uređaj nije prihvatio pristupne podatke. Provjerite API ključ i, za cloud kasu, serijski broj i PAK.';
        }

        if ($response->status() === 409) {
            return 'Fiskalna kasa već obrađuje ovaj zahtjev. Ne šaljite račun ponovo; prvo provjerite prethodni zahtjev u Fiskalizaciji.';
        }

        if ($response->status() === 404) {
            return 'Fiskalni uređaj ne prepoznaje traženu funkciju. Provjerite adresu uređaja i verziju njegovog softvera.';
        }

        if ($response->status() === 429 || $response->serverError()) {
            return 'Fiskalna kasa trenutno ne može obraditi račun. Sačekajte trenutak, provjerite vezu i prije ponovnog slanja provjerite prethodni zahtjev u Fiskalizaciji.';
        }

        if ($this->hasModelStateError($response, 'labels', '2805')) {
            return 'Poreska oznaka na računu nije važeća na fiskalnom uređaju. U Fiskalizaciji preuzmite aktuelne stope, zatim provjerite artikle na računu.';
        }

        $message = mb_strtolower($this->responseMessage($response));

        if ($this->containsAny($message, ['currenttaxrates', 'tax label', 'tax rate', 'poresk', 'пореск'])) {
            return 'Poreska oznaka na računu nije važeća na fiskalnom uređaju. U Fiskalizaciji preuzmite aktuelne stope, zatim provjerite artikle na računu.';
        }

        if ($this->containsAny($message, ['pin', 'gsc', 'security element', 'sigurnosni element'])) {
            return 'Fiskalni uređaj traži PIN sigurnosnog elementa. Unesite ga u Fiskalizaciji, pa pokušajte ponovo.';
        }

        if ($this->containsAny($message, ['gtin', 'barcode', 'bar code', 'barkod'])) {
            return 'Jedan artikal ima neispravan GTIN/barkod. Provjerite podatke artikla, pa pokušajte ponovo.';
        }

        return 'Fiskalni uređaj je odbio podatke računa. Provjerite stavke, količine, cijene i fiskalna podešavanja, pa pokušajte ponovo.';
    }

    private function hasInvoiceResponse(Response $response): bool
    {
        $json = $response->json();

        return is_array($json) && array_key_exists('invoiceResponse', $json);
    }

    private function responseMessage(Response $response): string
    {
        $json = $response->json();

        if (! is_array($json)) {
            return $response->body();
        }

        return implode(' ', array_filter([
            $this->stringValue($json['message'] ?? null),
            $this->stringValue($json['error'] ?? null),
            $this->stringValue($json['detail'] ?? null),
            $this->stringValue($json['errors'] ?? null),
        ]));
    }

    private function hasModelStateError(Response $response, string $propertyFragment, string $errorCode): bool
    {
        $json = $response->json();
        $modelState = is_array($json) ? ($json['modelState'] ?? []) : [];

        foreach ((array) $modelState as $error) {
            if (! is_array($error) || ! str_contains((string) ($error['property'] ?? ''), $propertyFragment)) {
                continue;
            }

            if (in_array($errorCode, (array) ($error['errors'] ?? []), true)) {
                return true;
            }
        }

        return false;
    }

    private function stringValue(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            return (string) $value;
        }

        return is_array($value)
            ? implode(' ', array_map($this->stringValue(...), $value))
            : '';
    }

    /** @param array<int, string> $needles */
    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }
}
