<?php

use App\Enums\FiscalRecordType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\FiscalDeviceHealth;
use App\Services\FiscalReceiptStore;
use App\Services\FiscalService;
use Illuminate\Support\Facades\Http;

/*
 * Rute fiskalizacije. FiscalService je pokriven u FiscalTest; ovdje se provjerava
 * šta ekran računa zaista dobije — poruku, status i zapis u bazi.
 */

it('fiskalizuje račun preko rute', function (): void {
    fakeDevice();
    $invoice = makeInvoice();

    $this->postJson(route('invoices.fiscalize', $invoice))
        ->assertSuccessful()
        ->assertJson(['message' => 'Račun je fiskalizovan.', 'invoice_id' => $invoice->id]);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized);
});

it('vraća poruku greške sa fiskalizacije kao 422', function (): void {
    $invoice = fiscalizedInvoice();

    $this->postJson(route('invoices.fiscalize', $invoice))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Račun nije moguće fiskalizovati.']);
});

it('prijavljuje korisniku jasnu odbijenicu poreske oznake', function (): void {
    Http::fake(['*/api/invoices' => Http::response(['message' => 'Unknown tax label F'], 400)]);

    $this->postJson(route('invoices.fiscalize', makeInvoice()))
        ->assertUnprocessable()
        ->assertJsonPath('message', fn ($message) => str_contains($message, 'Poreska oznaka na računu nije važeća'));
});

it('ne otkriva tehnički detalj neočekivane greške fiskalizacije', function (): void {
    $this->mock(FiscalService::class, function ($mock): void {
        $mock->shouldReceive('fiscalize')->once()->andThrow(new LogicException('tajni tehnički detalj'));
    });

    $this->postJson(route('invoices.fiscalize', makeInvoice()))
        ->assertServerError()
        ->assertJson(['message' => 'Fiskalizacija trenutno nije uspjela. Pokušajte ponovo.']);

    expect(app(FiscalDeviceHealth::class)->current()['state'])->toBe('unavailable');
});

it('ne vjeruje odgovoru bez broja fiskalnog računa', function (): void {
    Http::fake(['*/api/invoices' => Http::response(['invoiceCounter' => '1/1ПП'])]);

    $this->postJson(route('invoices.fiscalize', makeInvoice()))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Neispravan odgovor fiskalnog uređaja.']);
});

it('snima kopiju kao poseban zapis, bez promjene statusa računa', function (): void {
    $invoice = fiscalizedInvoice();

    $this->postJson(route('invoices.fiscal-copy', $invoice))
        ->assertSuccessful()
        ->assertJson(['message' => 'Kopija je odštampana.']);

    expect($invoice->fresh()->status)->toBe(InvoiceStatus::Fiscalized)
        ->and($invoice->fiscalRecords()->pluck('type')->all())
        ->toBe([FiscalRecordType::Original, FiscalRecordType::Copy]);
});

it('ne štampa kopiju nefiskalizovanog računa', function (): void {
    fakeDevice();

    $this->postJson(route('invoices.fiscal-copy', makeInvoice()))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Račun mora biti fiskalizovan prije štampe kopije.']);
});

it('pravi storno samo od fiskalizovanog računa', function (): void {
    $this->postJson(route('invoices.create-refund', makeInvoice()))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Storno se pravi samo od fiskalizovanog računa.']);

    expect(Invoice::count())->toBe(1);
});

it('storno preslikava stavke i iznose originala', function (): void {
    $invoice = fiscalizedInvoice();

    $refund = refundFor($invoice);

    expect($refund->status)->toBe(InvoiceStatus::RefundCreated)
        ->and($refund->total)->toBe($invoice->total)
        ->and($refund->subtotal)->toBe($invoice->subtotal)
        ->and($refund->tax_total)->toBe($invoice->tax_total)
        ->and($refund->currency)->toBe($invoice->currency)
        ->and($refund->items)->toHaveCount($invoice->items()->count())
        ->and($refund->items->first()->name)->toBe($invoice->items->first()->name)
        // Original i storno zajedno ulaze u „Storniranje".
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::RefundCreated)
        ->and($invoice->fresh()->refund_invoice_id)->toBe($refund->id);
});

it('storno dobija sljedeći broj u godini originala', function (): void {
    $invoice = fiscalizedInvoice();

    expect(refundFor($invoice)->invoice_number)->toBe('0002/'.date('Y'));
});

it('fiskalizuje storno preko rute', function (): void {
    $refund = refundFor(fiscalizedInvoice());

    $this->postJson(route('invoices.fiscal-refund', $refund))
        ->assertSuccessful()
        ->assertJson(['message' => 'Storno je fiskalizovan.']);

    expect($refund->fresh()->fiscalRecords()->pluck('type')->all())->toBe([FiscalRecordType::Refund]);
});

it('ne pravi storno od storno računa', function (): void {
    $refund = refundFor(fiscalizedInvoice());

    app(FiscalService::class)->refund($refund->fresh());

    $this->postJson(route('invoices.create-refund', $refund->fresh()))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Storno računa se ne može stornirati.']);
});

it('ne fiskalizuje storno kao prodaju', function (): void {
    $refund = refundFor(fiscalizedInvoice());

    $this->postJson(route('invoices.fiscalize', $refund))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Storno se fiskalizuje kao storno, ne kao prodaja.']);
});

it('ne fiskalizuje kao storno račun koji ničiji storno nije', function (): void {
    $invoice = fiscalizedInvoice();

    $this->postJson(route('invoices.fiscal-refund', $invoice))
        ->assertUnprocessable()
        ->assertJson(['message' => 'Ovaj račun nije storno nekog računa.']);
});

it('nema slike fiskalnog računa koja nije sačuvana', function (): void {
    $record = makeInvoice()->fiscalRecords()->create(['type' => FiscalRecordType::Original]);

    $this->get(route('invoices.receipt', $record))->assertNotFound();
});

it('servira račun uređaja u formatu u kojem je došao', function (string $extension, string $mime): void {
    $record = makeInvoice()->fiscalRecords()->create(['type' => FiscalRecordType::Original]);
    app(FiscalReceiptStore::class)->store($record, 'sadrzaj-racuna', $extension);

    $this->get(route('invoices.receipt', $record))
        ->assertSuccessful()
        ->assertHeader('content-type', $mime);
})->with([
    'png' => ['png', 'image/png'],
    'pdf' => ['pdf', 'application/pdf'],
    'html' => ['html', 'text/html; charset=UTF-8'],
]);

it('šalje svaki podržani fiskalni dokument kao JSON payload kroz Jump proxy', function (string $extension, string $mime, string $contents): void {
    $record = makeInvoice()->fiscalRecords()->create(['type' => FiscalRecordType::Original]);
    app(FiscalReceiptStore::class)->store($record, $contents, $extension);

    $this->getJson(route('invoices.receipt', [$record, 'mobile_payload' => 1]))
        ->assertSuccessful()
        ->assertJson([
            'mime' => $mime,
            'extension' => $extension,
            'contents' => base64_encode($contents),
        ]);
})->with([
    'PNG' => ['png', 'image/png', "\x89PNG\r\n"],
    'PDF' => ['pdf', 'application/pdf', '%PDF-1.7'],
    'HTML' => ['html', 'text/html; charset=UTF-8', '<html>račun</html>'],
]);

it('otvara svaki podržani fiskalni format u ugrađenom pregledu', function (string $extension, string $previewKind): void {
    $invoice = fiscalizedInvoice();
    $record = $invoice->fiscalRecords()->sole();
    $record->update(['verification_url' => 'https://provjeri.example.test/racun']);
    app(FiscalReceiptStore::class)->store($record, 'sadrzaj-racuna', $extension);
    $record = $record->fresh();

    $html = view('invoices.fiscal', [
        'invoice' => $invoice->fresh(['fiscalRecords.receipt']),
        'fiscalHealth' => [],
    ])->render();

    expect($html)->toContain('fiskalni-racun')
        ->and($html)->toContain("&#039;{$previewKind}&#039;")
        ->and($html)->toContain('https://provjeri.example.test/racun');
})->with([
    'PNG' => ['png', 'image'],
    'PDF' => ['pdf', 'pdf'],
    'HTML' => ['html', 'html'],
]);

it('pretvara binarni fiskalni dokument u prikaz koji Android WebView podržava', function (): void {
    $javascript = file_get_contents(resource_path('js/app.js'));
    $modal = file_get_contents(resource_path('views/components/receipt-modal.blade.php'));
    $fiscalView = file_get_contents(resource_path('views/invoices/fiscal.blade.php'));

    expect($javascript)
        ->toContain('? `data:${document.mime};base64,${document.contents}`')
        ->toContain('withMobilePayload(url, useMobilePayload)')
        ->toContain('reader.readAsDataURL(blob);')
        ->and($fiscalView)->toContain("getenv('JUMP_BRIDGE_PORT') !== false || isMobile()")
        ->and($modal)->toContain(':src="receiptUrl"')
        ->toContain(':srcdoc="receiptHtml"');
});
