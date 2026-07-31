<?php

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\FiscalService;
use App\Services\InvoiceWriter;
use App\Settings\FiscalSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function makeInvoice(array $client = []): Invoice
{
    return app(InvoiceWriter::class)->create([
        'client_id' => Client::create(['name' => 'Kupac d.o.o.'] + $client)->id,
        'payment_type' => 'Cash',
        'currency' => 'BAM',
        'template' => 'classic',
        'language' => 'sr_Latn',
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDay()->format('Y-m-d'),
        'items' => [[
            'article_id' => null, 'name' => 'Usluga', 'unit' => 'kom',
            'tax_label' => 'F', 'quantity' => 1, 'unit_price' => '1.00',
        ]],
    ]);
}

function fakeDevice(array $extra = []): void
{
    Http::fake(['*/api/invoices' => Http::response([
        'invoiceNumber' => 'ABC12345-ABC12345-1',
        'invoiceCounter' => '1/1ПП',
        'verificationUrl' => 'https://example.test/v/?vl=x',
        'invoiceImagePngBase64' => base64_encode('slika-racuna'),
    ] + $extra)]);
}

it('fiskalizuje račun i sačuva račun uređaja', function () {
    fakeDevice();
    $invoice = makeInvoice();

    $record = app(FiscalService::class)->fiscalize($invoice);

    expect($record->fiscal_invoice_number)->toBe('ABC12345-ABC12345-1')
        ->and($record->fiscal_counter)->toBe('1/1ПП')
        ->and($record->receiptImage->binary())->toBe('slika-racuna')
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized);
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

it('šalje JIB kupca kao buyerId', function () {
    fakeDevice();

    app(FiscalService::class)->fiscalize(makeInvoice(['vat_id' => '4403927160006']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === '4403927160006');
});

it('prefiksuje JIB sa VP za veleprodaju', function () {
    fakeDevice();
    $settings = app(FiscalSettings::class);
    $settings->wholesale = true;
    $settings->save();

    app(FiscalService::class)->fiscalize(makeInvoice(['vat_id' => '4403927160006']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === 'VP:4403927160006');
});

it('odbija veleprodaju bez JIB-a kupca', function () {
    fakeDevice();
    $settings = app(FiscalSettings::class);
    $settings->wholesale = true;
    $settings->save();

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Za veleprodaju je obavezan JIB kupca.');

it('šalje strano lice kao VP sa devetkama', function () {
    fakeDevice();
    $settings = app(FiscalSettings::class);
    $settings->wholesale = true;
    $settings->save();

    app(FiscalService::class)->fiscalize(makeInvoice(['country' => 'DE']));

    Http::assertSent(fn ($request) => $request['invoiceRequest']['buyerId'] === 'VP:9999999999999');
});

it('kopija nosi referencu na original', function () {
    fakeDevice();
    $invoice = makeInvoice();
    app(FiscalService::class)->fiscalize($invoice);

    app(FiscalService::class)->copy($invoice->fresh()->load('fiscalRecords'));

    Http::assertSent(function ($request) {
        return $request['invoiceRequest']['invoiceType'] !== 'Copy'
            || $request['invoiceRequest']['referentDocumentNumber'] === 'ABC12345-ABC12345-1';
    });
});

it('traži fiskalizovan račun prije kopije', function () {
    app(FiscalService::class)->copy(makeInvoice());
})->throws(RuntimeException::class, 'Račun mora biti fiskalizovan prije štampe kopije.');

it('storno prebacuje original u storniran', function () {
    fakeDevice();
    $invoice = makeInvoice();
    app(FiscalService::class)->fiscalize($invoice);

    $this->postJson(route('invoices.create-refund', $invoice->fresh()))->assertStatus(200);
    $refund = Invoice::latest('id')->first();

    app(FiscalService::class)->refund($refund->fresh());

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Refunded)
        ->and($refund->fresh()->status)->toBe(InvoiceStatus::Fiscalized);

    Http::assertSent(function ($request) {
        return $request['invoiceRequest']['transactionType'] !== 'Refund'
            || $request['invoiceRequest']['referentDocumentNumber'] === 'ABC12345-ABC12345-1';
    });
});

it('odbija račun u stranoj valuti dok preračun ne postoji', function () {
    fakeDevice();
    $invoice = makeInvoice();
    $invoice->update(['currency' => 'EUR']);

    app(FiscalService::class)->fiscalize($invoice->fresh());
})->throws(RuntimeException::class, 'Fiskalizacija je za sada moguća samo za račune u KM.');

it('vraća poruku uređaja kad odbije račun', function () {
    Http::fake(['*/api/invoices' => Http::response('Neispravan PIN', 400)]);

    app(FiscalService::class)->fiscalize(makeInvoice());
})->throws(RuntimeException::class, 'Uređaj je odbio račun (HTTP 400)');

it('ne dozvoljava dva storna istog računa', function () {
    fakeDevice();
    $invoice = makeInvoice();
    app(FiscalService::class)->fiscalize($invoice);

    $this->postJson(route('invoices.create-refund', $invoice->fresh()))->assertStatus(200);
    $this->postJson(route('invoices.create-refund', $invoice->fresh()))
        ->assertStatus(422)
        ->assertJson(['message' => 'Storno za ovaj račun već postoji.']);
});
