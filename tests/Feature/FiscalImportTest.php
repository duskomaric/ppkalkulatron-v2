<?php

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentType;
use App\Enums\Unit;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\FiscalInvoiceImporter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/** Spisak računa je CSV: broj, tip, tip transakcije, vrijeme, iznos. */
function deviceInvoiceList(array $rows): string
{
    return implode("\n", array_map(fn (array $row): string => implode(',', $row), $rows));
}

/** Odgovor kase na pregled sadržaja računa. */
function deviceInvoice(array $overrides = []): array
{
    return array_replace_recursive([
        'invoiceRequest' => [
            'invoiceType' => 'Normal',
            'transactionType' => 'Sale',
            'cashier' => 'Prodavac',
            'buyerId' => '4400000000000',
            'referentDocumentNumber' => null,
            'payment' => [['amount' => 117, 'paymentType' => 'WireTransfer']],
            'items' => [[
                'name' => 'Usluga sa kase',
                'gtin' => '00000123',
                'quantity' => 1,
                'unitPrice' => 117,
                'totalAmount' => 117,
                'labels' => ['F'],
            ]],
        ],
        'invoiceResponse' => [
            'invoiceNumber' => 'AAA11111-AAA11111-10',
            'invoiceCounter' => '5/10ПП',
            'sdcDateTime' => '2026-03-15T10:20:30.000+01:00',
            'verificationUrl' => 'https://example.test/v/?vl=uvoz',
            'taxItems' => [['label' => 'F', 'categoryName' => 'ECAL', 'rate' => 11, 'amount' => 11.59]],
        ],
        'receiptImageBase64' => base64_encode('slika-sa-kase'),
        'receiptImageFormat' => 'Png',
    ], $overrides);
}

/** Kasa sa zadatim spiskom i sadržajem računa po broju. */
function fakeDeviceArchive(array $list, array $invoices): void
{
    Http::fake([
        '*/api/invoices/search' => Http::response(deviceInvoiceList($list)),
        '*/api/invoices/*' => function ($request) use ($invoices) {
            foreach ($invoices as $number => $invoice) {
                if (str_contains(rawurldecode($request->url()), $number)) {
                    return Http::response($invoice);
                }
            }

            return Http::response([], 404);
        },
    ]);
}

it('spisak sa kase preskače kopije i označava već uvezene račune', function (): void {
    fakeDeviceArchive([
        ['AAA11111-AAA11111-10', 'Normal', 'Sale', '2026-03-15T10:20:30+01:00', '117.00'],
        ['AAA11111-AAA11111-11', 'Copy', 'Sale', '2026-03-15T10:21:00+01:00', '117.00'],
        ['AAA11111-AAA11111-12', 'Normal', 'Sale', '2026-03-16T09:00:00+01:00', '50.00'],
    ], []);

    makeInvoice()->fiscalRecords()->create([
        'type' => FiscalRecordType::Original,
        'fiscal_invoice_number' => 'AAA11111-AAA11111-12',
        'fiscalized_at' => now(),
    ]);

    $found = app(FiscalInvoiceImporter::class)->search('2026-03-01', '2026-03-31');

    expect($found['skipped'])->toBe(1)
        ->and($found['invoices'])->toHaveCount(2)
        ->and($found['invoices'][0]['number'])->toBe('AAA11111-AAA11111-10')
        ->and($found['invoices'][0]['imported'])->toBeFalse()
        ->and($found['invoices'][1]['imported'])->toBeTrue();
});

it('uvozi račun sa stavkama, porezom, kupcem i fiskalnim zapisom', function (): void {
    fakeDeviceArchive([], ['AAA11111-AAA11111-10' => deviceInvoice()]);

    $result = app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10']);

    expect($result['imported'])->toBe(1)
        ->and($result['failed'])->toBe([]);

    $invoice = Invoice::whereNotNull('imported_at')->with('items', 'client', 'fiscalRecords.receipt')->firstOrFail();
    $item = $invoice->items->first();
    $record = $invoice->fiscalRecords->first();

    expect($invoice->status)->toBe(InvoiceStatus::Fiscalized)
        ->and($invoice->payment_type)->toBe(PaymentType::WireTransfer)
        ->and($invoice->date->format('Y-m-d'))->toBe('2026-03-15')
        ->and($invoice->currency)->toBe('BAM')
        ->and($invoice->total)->toBe(11700)
        ->and($invoice->tax_total)->toBe(1159)
        ->and($invoice->subtotal)->toBe(10541)
        ->and($item->name)->toBe('Usluga sa kase')
        ->and($item->tax_label)->toBe('F')
        ->and($item->tax_rate)->toBe(1100)
        ->and($item->unit_price)->toBe(11700)
        ->and($invoice->client->vat_id)->toBe('4400000000000')
        ->and($record->type)->toBe(FiscalRecordType::Original)
        ->and($record->fiscal_invoice_number)->toBe('AAA11111-AAA11111-10')
        ->and($record->fiscal_counter)->toBe('5/10ПП')
        ->and($record->receipt->extension)->toBe('png');
});

it('artikal i klijent se prvo traže lokalno, pa prave od podataka sa kase', function (): void {
    $article = Article::create(['name' => 'Postojeći artikal', 'unit' => 'sat', 'tax_label' => 'F', 'gtin' => '00000123', 'is_active' => true]);
    $client = Client::create(['name' => 'Postojeći kupac', 'vat_id' => '4400000000000']);

    fakeDeviceArchive([], [
        'AAA11111-AAA11111-10' => deviceInvoice(),
        'AAA11111-AAA11111-20' => deviceInvoice([
            'invoiceRequest' => ['buyerId' => '4499999999999', 'items' => [['name' => 'Nov artikal', 'gtin' => null]]],
            'invoiceResponse' => ['invoiceNumber' => 'AAA11111-AAA11111-20'],
        ]),
    ]);

    app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10', 'AAA11111-AAA11111-20']);

    $first = Invoice::whereNotNull('imported_at')->with('items')->oldest('id')->firstOrFail();
    $second = Invoice::whereNotNull('imported_at')->with('items', 'client')->latest('id')->firstOrFail();

    expect($first->items->first()->article_id)->toBe($article->id)
        ->and($first->items->first()->unit)->toBe($article->unit)
        ->and($first->client_id)->toBe($client->id)
        ->and(Client::where('vat_id', '4499999999999')->exists())->toBeTrue()
        ->and($second->client->name)->toBe('Kupac 4499999999999')
        ->and(Article::where('name', 'Nov artikal')->exists())->toBeTrue()
        ->and($second->items->first()->article_id)->toBe(Article::where('name', 'Nov artikal')->value('id'));
});

it('storno sa kase se veže na original i oba računa su stornirana', function (): void {
    fakeDeviceArchive([], [
        'AAA11111-AAA11111-10' => deviceInvoice(),
        'AAA11111-AAA11111-11' => deviceInvoice([
            'invoiceRequest' => ['transactionType' => 'Refund', 'referentDocumentNumber' => 'AAA11111-AAA11111-10'],
            'invoiceResponse' => ['invoiceNumber' => 'AAA11111-AAA11111-11'],
        ]),
    ]);

    app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10', 'AAA11111-AAA11111-11']);

    $original = Invoice::whereNotNull('imported_at')->with('refundInvoice')->oldest('id')->firstOrFail();
    $refund = Invoice::whereNotNull('imported_at')->with('originalInvoice', 'fiscalRecords')->latest('id')->firstOrFail();

    expect($original->status)->toBe(InvoiceStatus::Fiscalized)
        ->and($original->refundInvoice->is($refund))->toBeTrue()
        ->and($refund->status)->toBe(InvoiceStatus::Refunded)
        ->and($refund->fiscalRecords->first()->type)->toBe(FiscalRecordType::Refund);
});

it('uvoz storna povuče i original koji nije označen', function (): void {
    fakeDeviceArchive([], [
        'AAA11111-AAA11111-10' => deviceInvoice(),
        'AAA11111-AAA11111-11' => deviceInvoice([
            'invoiceRequest' => ['transactionType' => 'Refund', 'referentDocumentNumber' => 'AAA11111-AAA11111-10'],
            'invoiceResponse' => ['invoiceNumber' => 'AAA11111-AAA11111-11', 'sdcDateTime' => '2026-03-15T11:00:00.000+01:00'],
        ]),
    ]);

    app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-11']);

    $invoices = Invoice::whereNotNull('imported_at')->with('refundInvoice', 'originalInvoice')->orderBy('id')->get();

    // Original nosi manji broj: povučen je prije nego što je storno upisan.
    expect($invoices)->toHaveCount(2)
        ->and($invoices[0]->invoice_number)->toBe('0001/2026')
        ->and($invoices[0]->status)->toBe(InvoiceStatus::Fiscalized)
        ->and($invoices[0]->refundInvoice->is($invoices[1]))->toBeTrue()
        ->and($invoices[1]->status)->toBe(InvoiceStatus::Refunded)
        ->and($invoices[1]->originalInvoice->is($invoices[0]))->toBeTrue();
});

it('jedinica mjere se vraća iz naziva u svoje polje', function (): void {
    fakeDeviceArchive([], [
        'AAA11111-AAA11111-10' => deviceInvoice([
            'invoiceRequest' => ['items' => [['name' => 'Konsultacije / sat', 'gtin' => null]]],
        ]),
        'AAA11111-AAA11111-30' => deviceInvoice([
            'invoiceRequest' => ['items' => [['name' => 'Servis / popravka', 'gtin' => null]]],
            'invoiceResponse' => ['invoiceNumber' => 'AAA11111-AAA11111-30'],
        ]),
    ]);

    app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10', 'AAA11111-AAA11111-30']);

    $withUnit = Invoice::whereNotNull('imported_at')->with('items')->oldest('id')->firstOrFail()->items->first();
    $withoutUnit = Invoice::whereNotNull('imported_at')->with('items')->latest('id')->firstOrFail()->items->first();

    expect($withUnit->name)->toBe('Konsultacije')
        ->and($withUnit->unit)->toBe(Unit::Sat)
        ->and(Article::where('name', 'Konsultacije')->first()->unit)->toBe(Unit::Sat)
        // Kosa crta koja nije jedinica mjere ostaje dio naziva.
        ->and($withoutUnit->name)->toBe('Servis / popravka')
        ->and($withoutUnit->unit)->toBe(Unit::Kom);
});

it('zapis bez računa ne označava račun kao uvezen', function (): void {
    fakeDeviceArchive([], ['AAA11111-AAA11111-10' => deviceInvoice()]);

    $invoice = makeInvoice();
    $invoice->fiscalRecords()->create([
        'type' => FiscalRecordType::Original,
        'fiscal_invoice_number' => 'AAA11111-AAA11111-10',
        'fiscalized_at' => now(),
    ]);
    DB::table('invoices')->where('id', $invoice->id)->delete();

    expect(app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10']))
        ->toMatchArray(['imported' => 1, 'skipped' => 0]);
});

it('ne uvozi isti račun dva puta', function (): void {
    fakeDeviceArchive([], ['AAA11111-AAA11111-10' => deviceInvoice()]);

    $importer = app(FiscalInvoiceImporter::class);
    $importer->import(['AAA11111-AAA11111-10']);
    $result = $importer->import(['AAA11111-AAA11111-10']);

    expect($result)->toMatchArray(['imported' => 0, 'skipped' => 1])
        ->and(Invoice::whereNotNull('imported_at')->count())->toBe(1);
});

it('na izbor korisnika broj računa dolazi sa kase i prepisuje postojeći', function (): void {
    fakeDeviceArchive([], ['AAA11111-AAA11111-10' => deviceInvoice()]);

    $existing = makeInvoice();
    $existing->update(['invoice_number' => 'AAA11111-AAA11111-10']);

    app(FiscalInvoiceImporter::class)->import(['AAA11111-AAA11111-10'], useFiscalNumbers: true);

    expect(Invoice::where('invoice_number', 'AAA11111-AAA11111-10')->count())->toBe(1)
        ->and(Invoice::find($existing->id))->toBeNull()
        ->and(Invoice::where('invoice_number', 'AAA11111-AAA11111-10')->value('imported_at'))->not->toBeNull();
});

it('uvoz ide kroz rutu podešavanja fiskalizacije', function (): void {
    fakeDeviceArchive([
        ['AAA11111-AAA11111-10', 'Normal', 'Sale', '2026-03-15T10:20:30+01:00', '117.00'],
    ], ['AAA11111-AAA11111-10' => deviceInvoice()]);

    $this->postJson(route('settings.fiscal.import.search'), ['from' => '2026-03-01', 'to' => '2026-03-31'])
        ->assertSuccessful()
        ->assertJsonPath('invoices.0.number', 'AAA11111-AAA11111-10')
        ->assertJsonPath('invoices.0.imported', false);

    $this->postJson(route('settings.fiscal.import.store'), ['numbers' => ['AAA11111-AAA11111-10']])
        ->assertSuccessful()
        ->assertJsonPath('imported', 1);

    expect(Invoice::whereNotNull('imported_at')->count())->toBe(1);
});

it('greška kase na pretrazi dolazi kao jasna poruka', function (): void {
    Http::fake(['*/api/invoices/search' => Http::response('nope', 500)]);

    $this->postJson(route('settings.fiscal.import.search'), ['from' => '2026-03-01', 'to' => '2026-03-31'])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Kasa nije vratila spisak računa. Provjerite vezu i period, pa pokušajte ponovo.']);
});

it('ekran fiskalizacije nudi uvoz računa', function (): void {
    $this->get(route('settings.fiscal.edit'))
        ->assertSuccessful()
        ->assertSee('Uvoz računa sa kase')
        ->assertSee('Broj sa kase')
        ->assertSee('biće prepisan');
});
