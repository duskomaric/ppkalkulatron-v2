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
        $message = mb_strtolower($this->responseText($response));

        if ($this->containsAny($message, ['currenttaxrates', 'tax label', 'tax rate', 'poresk', 'пореск'])) {
            return 'Poreska oznaka na računu nije važeća na fiskalnom uređaju. U Fiskalizaciji preuzmite aktuelne stope, zatim provjerite artikle na računu.';
        }

        if ($this->containsAny($message, ['invoiceresponse', 'print', 'printer', 'štamp', 'stamp', 'papir'])) {
            return 'Račun je moguće da je fiskalizovan, ali štampa nije uspjela. Provjerite štampač i papir; ne šaljite račun ponovo dok ne provjerite prethodni zahtjev po RequestId-u.';
        }

        if ($this->containsAny($message, ['pin', 'gsc', 'security element', 'sigurnosni element'])) {
            return 'Fiskalni uređaj traži PIN sigurnosnog elementa. Unesite ga u Fiskalizaciji, pa pokušajte ponovo.';
        }

        if ($this->containsAny($message, ['gtin', 'barcode', 'bar code', 'barkod'])) {
            return 'Jedan artikal ima neispravan GTIN/barkod. Provjerite podatke artikla, pa pokušajte ponovo.';
        }

        if ($response->status() === 401 || $response->status() === 403) {
            return 'Fiskalni uređaj nije prihvatio pristupne podatke. Provjerite API ključ i, za cloud kasu, serijski broj i PAK.';
        }

        if ($response->status() === 409) {
            return 'Fiskalni uređaj već obrađuje ovaj zahtjev. Ne šaljite račun ponovo; prvo ga provjerite po RequestId-u u Fiskalizaciji.';
        }

        if ($response->status() === 404) {
            return 'Fiskalni uređaj ne prepoznaje traženu funkciju. Provjerite adresu uređaja i verziju njegovog softvera.';
        }

        if ($response->status() === 429 || $response->serverError()) {
            return 'Fiskalni uređaj trenutno ne može obraditi račun. Sačekajte trenutak, provjerite vezu i prije ponovnog slanja provjerite prethodni zahtjev po RequestId-u.';
        }

        return 'Fiskalni uređaj je odbio podatke računa. Provjerite stavke, količine, cijene i fiskalna podešavanja, pa pokušajte ponovo.';
    }

    private function responseText(Response $response): string
    {
        $json = $response->json();

        if (is_array($json)) {
            return json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return $response->body();
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
