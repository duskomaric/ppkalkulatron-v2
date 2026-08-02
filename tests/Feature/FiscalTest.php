<?php

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\ExchangeRate;
use App\Models\Invoice;
use App\Services\Diagnostics;
use App\Services\FiscalDeviceErrorMessage;
use App\Services\FiscalReceiptStore;
use App\Services\FiscalService;
use App\Services\NetworkScanner;
use App\Services\OFSService;
use App\Settings\FiscalSettings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

// makeInvoice(), fakeDevice(), fiscalizedInvoice(), refundFor(), enableWholesale()
// i unlocked() stoje u tests/Pest.php.

it('fiskalizuje račun i sačuva račun uređaja', function () {
    fakeDevice();
    $invoice = makeInvoice();

    $record = app(FiscalService::class)->fiscalize($invoice);

    expect($record->fiscal_invoice_number)->toBe('ABC12345-ABC12345-1')
        ->and($record->fiscal_counter)->toBe('1/1ПП')
        ->and(app(FiscalReceiptStore::class)->binary($record->fresh('receipt')))->toBe('slika-racuna')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized);
});

it('čuva svaki podržani format fiskalnog dokumenta', function (string $layout, string $format, string $responseField, string $contents, string $extension) {
    $settings = app(FiscalSettings::class);
    $settings->receipt_layout = $layout;
    $settings->receipt_document_format = $format;
    $settings->save();

    Http::fake(['*/api/invoices' => Http::response([
        'invoiceNumber' => 'ABC12345-ABC12345-1',
        'invoiceCounter' => '1/1ПП',
        $responseField => $responseField === 'invoiceImageHtml' ? $contents : base64_encode($contents),
    ])]);

    $record = app(FiscalService::class)->fiscalize(makeInvoice());
    $record = $record->fresh('receipt');

    expect($record->receipt->extension)->toBe($extension)
        ->and(app(FiscalReceiptStore::class)->binary($record))->toBe($contents);

    Http::assertSent(fn ($request) => $request['receiptLayout'] === $layout
        && $request['receiptImageFormat'] === $format
        && $request['renderReceiptImage'] === true);
})->with([
    'termalni PNG' => ['Slip', 'Png', 'invoiceImagePngBase64', "\x89PNG\r\n", 'png'],
    'termalni PDF' => ['Slip', 'Pdf', 'invoiceImagePdfBase64', '%PDF-1.7', 'pdf'],
    'termalni HTML' => ['Slip', 'Html', 'invoiceImageHtml', '<html>slip</html>', 'html'],
    'A4 PDF' => ['Invoice', 'Pdf', 'invoiceImagePdfBase64', '%PDF-1.7 A4', 'pdf'],
    'A4 HTML' => ['Invoice', 'Html', 'invoiceImageHtml', '<html>A4</html>', 'html'],
]);

it('čuva sadržaj računa samo u povezanoj tabeli slika', function () {
    expect(Schema::hasColumn('fiscal_records', 'fiscal_receipt_image_path'))->toBeFalse()
        ->and(Schema::hasColumn('fiscal_records', 'fiscal_meta'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'is_fiscalized'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'fiscal_invoice_number'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'fiscal_counter'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'fiscal_verification_url'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'fiscal_request_id'))->toBeFalse()
        ->and(Schema::hasColumn('invoices', 'fiscalized_at'))->toBeFalse();
});

it('zamjenjuje raniji fiskalni dokument kada uređaj vrati noviji format', function () {
    $record = makeInvoice()->fiscalRecords()->create(['type' => FiscalRecordType::Original]);
    $receipts = app(FiscalReceiptStore::class);

    $receipts->store($record, 'stari PNG', 'png');
    $receipts->store($record->fresh('receipt'), 'novi PDF', 'pdf');

    $receipt = $record->fresh('receipt')->receipt;

    expect($receipt->extension)->toBe('pdf')
        ->and($receipts->binary($record->fresh('receipt')))->toBe('novi PDF');
    Storage::disk('local')->assertMissing('fiscal-receipts/'.$record->id.'.png');
});

it('šalje iznose sa porezom i poresku oznaku', function () {
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice());

    Http::assertSent(function ($request) {
        $item = $request['invoiceRequest']['items'][0];

        // json_encode pretvori 1.0 u 1, pa se poredi kao broj — uređaj to prihvata.
        return (float) $item['totalAmount'] === 1.0
            && (float) $item['unitPrice'] === 1.0
            && $item['labels'] === ['F']
            && $request['invoiceRequest']['transactionType'] === 'Sale'
            && $request['invoiceRequest']['invoiceType'] === 'Normal'
            && strlen($request->header('RequestId')[0]) <= 32;
    });
});

it('ne fiskalizuje dva puta', function () {
    fakeDevice();
    $invoice = makeInvoice();
    app(FiscalService::class)->fiscalize($invoice);

    app(FiscalService::class)->fiscalize($invoice->fresh());
})->throws(RuntimeException::class, 'Račun nije moguće fiskalizovati.');

it('prvo pronađe prethodni zahtjev prije ponovnog slanja računa', function () {
    $invoice = makeInvoice();
    $pending = $invoice->fiscalRecords()->create([
        'type' => FiscalRecordType::Original,
        'request_id' => 'inv'.$invoice->id.'ponovo123',
    ]);

    Http::fake([
        '*/api/invoices/request/*' => Http::response([
            'invoiceNumber' => 'ABC12345-ABC12345-1',
            'invoiceCounter' => '1/1ПП',
        ]),
    ]);

    $record = app(FiscalService::class)->fiscalize($invoice);

    expect($record->id)->toBe($pending->id)
        ->and($record->fiscal_invoice_number)->toBe('ABC12345-ABC12345-1')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized);

    Http::assertSentCount(1);
});

it('ne šalje novi račun kada se prethodni zahtjev ne može provjeriti', function () {
    $invoice = makeInvoice();
    $invoice->fiscalRecords()->create(['type' => FiscalRecordType::Original, 'request_id' => 'ponovi-zahtjev']);
    Http::fake(['*/api/invoices/request/*' => Http::response('', 503)]);

    app(FiscalService::class)->fiscalize($invoice);
})->throws(RuntimeException::class, 'Nije moguće provjeriti prethodni zahtjev fiskalnom uređaju.');

it('šalje JIB kupca kao buyerId', function () {
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice(['vat_id' => '4403927160006']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === '4403927160006');
});

it('prefiksuje JIB sa VP za veleprodaju', function () {
    fakeDevice();
    enableWholesale();

    app(FiscalService::class)->fiscalize(makeInvoice(['vat_id' => '4403927160006']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === 'VP:4403927160006');
});

it('odbija veleprodaju bez JIB-a kupca', function () {
    fakeDevice();
    enableWholesale();

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Za veleprodaju je obavezan JIB kupca.');

it('šalje strano lice kao VP sa devetkama', function () {
    fakeDevice();
    enableWholesale();

    app(FiscalService::class)->fiscalize(makeInvoice(['country' => 'DE']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === 'VP:9999999999999');
});

it('kopija nosi referencu na original', function () {
    $invoice = fiscalizedInvoice();

    app(FiscalService::class)->copy($invoice->load('fiscalRecords'));

    // Traži se da kopija zaista bude poslata; implikacija „nije Copy ILI ima
    // referencu" bila bi istinita i kad kopije nema.
    Http::assertSent(function ($request) {
        return ($request['invoiceRequest']['invoiceType'] ?? null) === 'Copy'
            && ($request['invoiceRequest']['referentDocumentNumber'] ?? null) === 'ABC12345-ABC12345-1';
    });
});

it('traži fiskalizovan račun prije kopije', function () {
    app(FiscalService::class)->copy(makeInvoice());
})->throws(RuntimeException::class, 'Račun mora biti fiskalizovan prije štampe kopije.');

it('storno prebacuje original u storniran', function () {
    $invoice = fiscalizedInvoice();
    $refund = refundFor($invoice);

    app(FiscalService::class)->refund($refund->fresh());

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Refunded)
        ->and($refund->fresh()->status)->toBe(InvoiceStatus::Fiscalized);

    // Refund zahtjev mora zaista biti poslat, uz referencu na original.
    Http::assertSent(function ($request) {
        return ($request['invoiceRequest']['transactionType'] ?? null) === 'Refund'
            && ($request['invoiceRequest']['referentDocumentNumber'] ?? null) === 'ABC12345-ABC12345-1';
    });
});

it('preračuna stranu valutu u KM po kursu', function () {
    fakeDevice();
    ExchangeRate::create([
        'currency' => 'EUR', 'rate_to_bam' => 1.95583, 'rate_date' => now()->subDay(),
    ]);

    $invoice = makeInvoice();
    $invoice->update(['currency' => 'EUR']);

    app(FiscalService::class)->fiscalize($invoice->fresh());

    // 1,00 EUR × 1,95583 = 1,96 KM
    Http::assertSent(fn ($request) => (float) $request['invoiceRequest']['items'][0]['totalAmount'] === 1.96);
});

it('odbija fiskalizaciju bez kursa za tu valutu', function () {
    fakeDevice();
    $invoice = makeInvoice();
    $invoice->update(['currency' => 'EUR']);

    app(FiscalService::class)->fiscalize($invoice->fresh());
})->throws(RuntimeException::class, 'Nema kursa za EUR');

it('prevodi nevažeću poresku oznaku uređaja u jasnu uputu', function () {
    Http::fake(['*/api/invoices' => Http::response([
        'message' => 'Bad Request',
        'modelState' => [[
            'property' => 'items[0].labels',
            'errors' => ['2805'],
        ]],
    ], 400)]);

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Poreska oznaka na računu nije važeća na fiskalnom uređaju. U Fiskalizaciji preuzmite aktuelne stope');

it('upozorava da se račun ne šalje ponovo kada uređaj ne može štampati', function () {
    Http::fake(['*/api/invoices' => Http::response([
        'invoiceResponse' => ['invoiceNumber' => 'ABC12345-ABC12345-1'],
    ], 500)]);

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Račun je fiskalizovan, ali štampa nije uspjela.');

it('ne prikazuje sirovi odgovor uređaja za nepoznatu grešku', function () {
    Http::fake(['*/api/invoices' => Http::response('Internal device detail: 1234', 400)]);

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Fiskalni uređaj je odbio podatke računa.');

it('prevodi ostale poznate odgovore fiskalnog uređaja', function (mixed $body, int $status, string $expected) {
    Http::fake(['*/api/invoices' => Http::response($body, $status)]);

    $response = Http::post('http://fiscal-device.test/api/invoices');

    expect(app(FiscalDeviceErrorMessage::class)->forInvoice($response))->toBe($expected);
})->with([
    'PIN sigurnosnog elementa' => ['PIN required', 400, 'Fiskalni uređaj traži PIN sigurnosnog elementa. Unesite ga u Fiskalizaciji, pa pokušajte ponovo.'],
    'neispravan pristup' => ['', 401, 'Fiskalni uređaj nije prihvatio pristupne podatke. Provjerite API ključ i, za cloud kasu, serijski broj i PAK.'],
    'zahtjev je već u obradi' => ['', 409, 'Fiskalni uređaj već obrađuje ovaj zahtjev. Ne šaljite račun ponovo; prvo ga provjerite po RequestId-u u Fiskalizaciji.'],
    'nedostupan servis' => ['', 503, 'Fiskalni uređaj trenutno ne može obraditi račun. Sačekajte trenutak, provjerite vezu i prije ponovnog slanja provjerite prethodni zahtjev po RequestId-u.'],
    'neispravan barkod' => ['Invalid GTIN', 400, 'Jedan artikal ima neispravan GTIN/barkod. Provjerite podatke artikla, pa pokušajte ponovo.'],
    'status pristupa ima prednost nad tekstom greške' => [['message' => 'Unknown tax label F'], 401, 'Fiskalni uređaj nije prihvatio pristupne podatke. Provjerite API ključ i, za cloud kasu, serijski broj i PAK.'],
]);

it('ne dozvoljava dva storna istog računa', function () {
    $invoice = fiscalizedInvoice();
    refundFor($invoice);

    $this->postJson(route('invoices.create-refund', $invoice->fresh()))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Storno za ovaj račun već postoji.']);
});

it('stavlja čuvanje fiskalnih podešavanja na kraj ekrana', function () {
    $html = $this->get(route('settings.fiscal.edit'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('id="fiscal-settings-form"')
        ->and($html)->toContain('form="fiscal-settings-form"')
        ->and(strrpos($html, 'Sačuvaj izmjene'))->toBeGreaterThan(strpos($html, 'Potraga po RequestId'));
});

it('objašnjava zašto se stope ne mogu preuzeti dok kasa nije spremna', function () {
    $html = $this->get(route('settings.fiscal.edit'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Prvo provjerite vezu sa fiskalnom kasom.')
        ->and($html)->toContain('Kasa nije dostupna. Provjerite adresu, mrežu i podatke za pristup.');
});

it('čita opseg adresa iz teksta', function () {
    $scanner = app(NetworkScanner::class);

    expect($scanner->parseRange('192.168.31.100-103'))
        ->toBe(['192.168.31.100', '192.168.31.101', '192.168.31.102', '192.168.31.103'])
        ->and($scanner->parseRange('192.168.31.'))->toHaveCount(254)
        ->and($scanner->parseRange('192.168.31.10-5'))->toBe([])
        ->and($scanner->parseRange('999.168.31.10-12'))->toBe([])
        ->and($scanner->parseRange('192.168.999.'))->toBe([])
        ->and($scanner->parseRange('8.8.8.1-2'))->toBe([])
        ->and($scanner->parseRange('bilo šta'))->toBe([]);
});

it('prijavljuje uređaj koji odgovori na attention', function () {
    Http::fake([
        'http://10.0.0.5:3566/api/attention' => Http::response('', 200),
        'http://10.0.0.6:3566/api/attention' => Http::response('', 401),
        '*' => Http::response('', 500),
    ]);

    $found = app(NetworkScanner::class)->scan('10.0.0.1-10');

    expect($found)->toBe(['http://10.0.0.5:3566', 'http://10.0.0.6:3566']);
});

it('šalje PIN sigurnosnog elementa kao goli tekst', function () {
    Http::fake(['*/api/pin' => Http::response('"0100"', 200)]);

    unlocked()->post(route('settings.fiscal.pin'), ['security_pin' => '1234'])
        ->assertSessionHas('status', 'PIN je prihvaćen. Uređaj je spreman za fiskalizaciju.');

    Http::assertSent(fn ($request) => $request->body() === '1234');
});

it('prevodi kod greške sa PIN-a u poruku', function (string $code, string $message) {
    Http::fake(['*/api/pin' => Http::response('"'.$code.'"', 200)]);

    unlocked()->post(route('settings.fiscal.pin'), ['security_pin' => '1234'])
        ->assertSessionHas('error', $message);
})->with([
    ['1300', 'Sigurnosni element nije prisutan u uređaju.'],
    ['2400', 'Lokalni ESIR (LPFR) nije spreman.'],
    ['2800', 'PIN nije u ispravnom formatu — očekuje se 4 cifre.'],
    ['2806', 'PIN nije u ispravnom formatu — očekuje se 4 cifre.'],
    ['9999', 'Uređaj je odbio PIN (kod 9999).'],
]);

it('ne otkriva tehnički detalj greške fiskalnog uređaja', function () {
    Http::fake(fn () => throw new LogicException('tajni tehnički detalj'));

    unlocked()->post(route('settings.fiscal.test'))
        ->assertSessionHas('error', 'Fiskalni uređaj trenutno nije dostupan. Pokušajte ponovo.');
});

it('cijena × količina daje ukupno i poslije preračuna valute', function () {
    fakeDevice();
    ExchangeRate::create(['currency' => 'EUR', 'rate_to_bam' => 1.95583, 'rate_date' => now()->subDay()]);

    $invoice = makeInvoice();
    $invoice->update(['currency' => 'EUR']);
    $invoice->items()->update(['quantity' => 3, 'total' => 300, 'unit_price' => 100]);

    app(FiscalService::class)->fiscalize($invoice->fresh()->load('items'));

    Http::assertSent(function ($request) {
        $item = $request['invoiceRequest']['items'][0];

        return round($item['unitPrice'] * $item['quantity'], 2) === round($item['totalAmount'], 2);
    });
});

it('ne fiskalizuje storno dva puta', function () {
    $refund = refundFor(fiscalizedInvoice());
    app(FiscalService::class)->refund($refund->fresh());

    app(FiscalService::class)->refund($refund->fresh());
})->throws(RuntimeException::class, 'Ovaj storno je već fiskalizovan.');

it('ne dozvoljava izmjenu fiskalizovanog računa ni kad mu je storno kreiran', function () {
    $invoice = fiscalizedInvoice();
    refundFor($invoice);

    expect($invoice->fresh()->isDeletable())->toBeFalse();

    $this->delete(route('invoices.destroy', $invoice))->assertRedirect();
    expect(Invoice::find($invoice->id))->not->toBeNull();
});

it('brisanje storna vraća original u fiskalizovano stanje', function () {
    $invoice = fiscalizedInvoice();
    $refund = refundFor($invoice);

    $this->delete(route('invoices.destroy', $refund));

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized)
        ->and($invoice->fresh()->refund_invoice_id)->toBeNull();
});

it('traži serijski broj i PAK za cloud uređaj', function () {
    // Način uređaja je do sada bio samo dekoracija; sada odlučuje šta je obavezno.
    unlocked()->put(route('settings.fiscal.update'), [
        'base_url' => 'https://pos.ofs.ba', 'device_mode' => 'cloud', 'cashier' => 'Prodavac',
        'receipt_layout' => 'Slip', 'receipt_document_format' => 'Png', 'default_payment_type' => 'Cash',
    ])->assertSessionHasErrors(['serial_number', 'pac']);
});

it('šalje cloud identifikatore uz OFS zahtjev', function () {
    $settings = app(FiscalSettings::class);
    $settings->device_mode = 'cloud';
    $settings->serial_number = 'test-serial';
    $settings->pac = 'test-pac';
    $settings->save();
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice());

    Http::assertSent(fn ($request) => $request->header('X-Teron-SerialNumber') === ['test-serial']
        && $request->header('X-PAC') === ['test-pac']
        && $request->header('Content-Type') === ['application/json; charset=UTF-8']);
});

it('lokalni uređaj ne traži serijski broj', function () {
    unlocked()->put(route('settings.fiscal.update'), [
        'base_url' => 'http://192.168.31.103:3566', 'device_mode' => 'local', 'cashier' => 'Prodavac',
        'receipt_layout' => 'Slip', 'receipt_document_format' => 'Png', 'default_payment_type' => 'Cash',
    ])->assertSessionHasNoErrors();
});

it('ne dozvoljava PNG za A4 fiskalni dokument', function () {
    unlocked()->put(route('settings.fiscal.update'), [
        'base_url' => 'http://192.168.31.103:3566',
        'device_mode' => 'local',
        'cashier' => 'Prodavac',
        'receipt_layout' => 'Invoice',
        'receipt_document_format' => 'Png',
        'default_payment_type' => 'Cash',
    ])->assertSessionHasErrors('receipt_document_format');
});

it('ne šalje cloud identifikatore lokalnom ESIR-u', function () {
    $settings = app(FiscalSettings::class);
    $settings->device_mode = 'local';
    $settings->serial_number = 'ne šalji';
    $settings->pac = 'ne šalji';
    $settings->save();
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice());

    Http::assertSent(fn ($request) => $request->header('X-Teron-SerialNumber') === []
        && $request->header('X-PAC') === []);
});

it('čita podešavanja fiskalnog uređaja preko OFS klijenta', function () {
    Http::fake(['*/api/settings' => Http::response(['device' => 'ESIR'])]);

    expect(app(OFSService::class)->getSettings()->json('device'))->toBe('ESIR');
    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/settings'));
});

it('može provjeriti drugu kasu bez mijenjanja aktivnih podešavanja', function () {
    Http::fake(['http://esir.test/api/settings' => Http::response(['device' => 'test-esir'])]);

    $response = app(OFSService::class)
        ->withOverrides(baseUrl: 'http://esir.test/', apiKey: 'test-key')
        ->getSettings();

    expect($response->json('device'))->toBe('test-esir');
    Http::assertSent(fn ($request) => $request->url() === 'http://esir.test/api/settings'
        && $request->header('Authorization') === ['Bearer test-key']);
});

it('za staru nevažeću kombinaciju A4 i PNG bira podržani format dokumenta', function () {
    $settings = app(FiscalSettings::class);
    $settings->receipt_layout = 'Invoice';
    $settings->receipt_document_format = 'Png';
    $settings->save();
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice());

    Http::assertSent(fn ($request) => $request['receiptLayout'] === 'Invoice'
        && $request['receiptImageFormat'] === 'Pdf');
});

it('vraća prazan rezultat skeniranja kada opseg nema adresa', function () {
    expect(app(NetworkScanner::class)->scan('neispravan opseg'))->toBe([]);
});

it('lokalni opseg uvijek sadrži samo privatne IPv4 adrese kada je dostupan', function () {
    $range = app(NetworkScanner::class)->localRange();

    expect($range === [] || (count($range) === 254
        && collect($range)->every(fn (string $ip): bool => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE))))->toBeTrue();
});

it('traži privatnu adresu mrežnog interfejsa kada socket vrati javnu adresu', function () {
    $scanner = new class(app(Diagnostics::class)) extends NetworkScanner
    {
        protected function socketLocalIp(): ?string
        {
            return '8.8.8.8';
        }

        protected function interfaceIps(): array
        {
            return ['203.0.113.1', '192.168.50.12'];
        }
    };

    expect($scanner->localIp())->toBe('192.168.50.12')
        ->and($scanner->localRange())->toHaveCount(254)
        ->and($scanner->localRange()[0])->toBe('192.168.50.1');
});

it('ne pokušava skeniranje kada nijedan mrežni interfejs nema privatnu adresu', function () {
    $scanner = new class(app(Diagnostics::class)) extends NetworkScanner
    {
        protected function socketLocalIp(): ?string
        {
            return null;
        }

        protected function interfaceIps(): array
        {
            return ['203.0.113.1'];
        }
    };

    expect($scanner->localIp())->toBeNull()
        ->and($scanner->localRange())->toBe([])
        ->and($scanner->scan())->toBe([]);
});

it('čita dostupne IP adrese iz stvarnih mrežnih interfejsa uređaja', function () {
    $scanner = new class(app(Diagnostics::class)) extends NetworkScanner
    {
        /** @return array<int, string> */
        public function availableInterfaceIps(): array
        {
            return $this->interfaceIps();
        }
    };

    expect($scanner->availableInterfaceIps())->each->toBeString();
});
