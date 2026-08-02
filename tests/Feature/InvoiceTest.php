<?php

use App\Enums\DocumentTemplate;
use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Article;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Invoice;
use App\Services\FiscalReceiptStore;
use App\Services\InvoiceNumber;
use App\Services\InvoicePdfService;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\NumberingSettings;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Native\Mobile\Facades\Share;
use Native\Mobile\Facades\System;

// invoicePayload() i renderPdfView() stoje u tests/Pest.php.

it('kreira račun i preračuna iznose iz količine i cijene', function () {
    $this->post(route('invoices.store'), invoicePayload())->assertRedirect();

    $invoice = Invoice::with('items')->firstOrFail();

    // 2 × 55,50 = 111,00 sa porezom; osnovica = 111 / 1,11 = 100,00; porez = 11,00
    expect($invoice->total)->toBe(11100)
        ->and($invoice->subtotal)->toBe(10000)
        ->and($invoice->tax_total)->toBe(1100)
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->tax_rate)->toBe(1100);
});

it('ne vjeruje iznosima iz forme nego ih računa sam', function () {
    // Klijent šalje i "total" — mora biti ignorisan.
    $this->post(route('invoices.store'), invoicePayload(['total' => 999999, 'subtotal' => 999999]));

    expect(Invoice::first()->total)->toBe(11100);
});

it('dodjeljuje brojeve redom', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $this->post(route('invoices.store'), invoicePayload());

    $year = date('Y');
    expect(Invoice::pluck('invoice_number')->all())->toBe(["0001/{$year}", "0002/{$year}"]);
});

it('listu računa filtrira rasponom datuma izabrane godine', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'date' => '2024-06-01', 'due_date' => '2024-06-15',
    ]));
    $previousYearNumber = Invoice::sole()->invoice_number;

    $this->post(route('invoices.store'), invoicePayload([
        'date' => '2025-06-01', 'due_date' => '2025-06-15',
    ]));
    $selectedYearNumber = Invoice::latest('id')->value('invoice_number');

    $this->get(route('invoices.index', ['year' => 2025]))
        ->assertSuccessful()
        ->assertSee($selectedYearNumber)
        ->assertDontSee($previousYearNumber);
});

it('povezuje oznake sa poljima forme računa', function () {
    $html = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('for="date"')
        ->and($html)->toContain('<input id="date"')
        ->and($html)->toContain('for="payment_type"')
        ->and($html)->toMatch('/<select[^>]*id="payment_type"/');
});

it('koristi standardne radnje forme računa', function () {
    $html = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Kreiraj račun')
        ->and($html)->toContain('href="'.route('invoices.index').'"')
        ->and($html)->toContain('Odustani');
});

it('oslobađa broj kad se račun obriše', function () {
    $year = date('Y');

    $this->post(route('invoices.store'), invoicePayload());
    $this->post(route('invoices.store'), invoicePayload());

    $this->delete(route('invoices.destroy', Invoice::where('invoice_number', "0002/{$year}")->first()));

    // Broj se izvodi iz računa, pa je 0002 opet slobodan.
    expect(app(InvoiceNumber::class)->next())->toBe("0002/{$year}");
});

it('vraća se na početni broj kad nema računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $this->delete(route('invoices.destroy', Invoice::first()));

    expect(app(InvoiceNumber::class)->next())->toBe('0001/'.date('Y'));
});

it('pamti zadnju cijenu na artiklu', function () {
    $article = Article::create(['name' => 'Usluga', 'unit' => 'sat', 'tax_label' => 'F']);

    $this->post(route('invoices.store'), invoicePayload([
        'items' => [[
            'article_id' => $article->id,
            'name' => 'Usluga',
            'unit' => 'sat',
            'tax_label' => 'F',
            'quantity' => 1,
            'unit_price' => '80.00',
        ]],
    ]));

    expect($article->fresh()->last_unit_price)->toBe(8000);
});

it('traži bar jednu stavku', function () {
    $this->post(route('invoices.store'), invoicePayload(['items' => []]))
        ->assertSessionHasErrors('items');
});

it('ne prima rok dospijeća prije datuma', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]))->assertSessionHasErrors('due_date');
});

it('mijenja račun i preračuna iznose ponovo', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();

    $this->put(route('invoices.update', $invoice), invoicePayload([
        'items' => [[
            'article_id' => null,
            'name' => 'Druga usluga',
            'unit' => 'kom',
            'tax_label' => 'F',
            'quantity' => 1,
            'unit_price' => '11.10',
        ]],
    ]))->assertRedirect(route('invoices.show', $invoice));

    $invoice->refresh()->load('items');

    expect($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->name)->toBe('Druga usluga')
        ->and($invoice->total)->toBe(1110);
});

it('otvara izmjenu kreiranog računa sa njegovim stavkama', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.edit', $invoice))
        ->assertSuccessful()
        ->assertViewHas('invoice', fn (Invoice $value): bool => $value->is($invoice))
        ->assertSee('Usluga');
});

it('ne dozvoljava izmjenu ni brisanje fiskalizovanog računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();
    $invoice->update(['status' => InvoiceStatus::Fiscalized]);

    $this->get(route('invoices.edit', $invoice))->assertRedirect(route('invoices.show', $invoice));
    $this->put(route('invoices.update', $invoice), invoicePayload())->assertRedirect(route('invoices.show', $invoice));
    $this->delete(route('invoices.destroy', $invoice))->assertRedirect(route('invoices.show', $invoice));

    expect(Invoice::count())->toBe(1);
});

it('pretražuje po broju i po klijentu', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'client_id' => Client::create(['name' => 'Mermer Gradnja'])->id,
    ]));
    $this->post(route('invoices.store'), invoicePayload([
        'client_id' => Client::create(['name' => 'Kafe Bar'])->id,
    ]));

    $this->get(route('invoices.index', ['q' => 'Mermer']))
        ->assertSuccessful()
        ->assertSee('Mermer Gradnja')
        ->assertDontSee('Kafe Bar');

    $this->get(route('invoices.index', ['q' => '0002']))
        ->assertSuccessful()
        ->assertSee('Kafe Bar');
});

it('prikazuje aktivne filtere i linkove koji brišu samo odgovarajući filter', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $response = $this->get(route('invoices.index', [
        'q' => '0001',
        'status' => InvoiceStatus::Created->value,
        'payment_type' => 'Cash',
        'created_from' => now()->format('Y-m-d'),
        'created_to' => now()->format('Y-m-d'),
        'year' => date('Y'),
    ]))->assertSuccessful();

    $response->assertViewHas('activeFilters', function (array $filters): bool {
        $byLabel = collect($filters)->keyBy('label');

        return $byLabel->keys()->all() === ['Pretraga', 'Status', 'Plaćanje', 'Datum']
            && str_contains($byLabel['Pretraga']['clear'], 'status=created')
            && ! str_contains($byLabel['Pretraga']['clear'], 'q=')
            && str_contains($byLabel['Datum']['clear'], 'payment_type=Cash')
            && ! str_contains($byLabel['Datum']['clear'], 'created_from=');
    });
});

it('nudi godine računa zajedno sa izabranom i tekućom godinom', function () {
    $this->post(route('invoices.store'), invoicePayload([
        'date' => '2024-06-01',
        'due_date' => '2024-06-15',
    ]));

    $this->get(route('invoices.index', ['year' => 2025]))
        ->assertSuccessful()
        ->assertViewHas('years', function (array $years): bool {
            return in_array(2024, $years, true)
                && in_array(2025, $years, true)
                && in_array((int) date('Y'), $years, true)
                && $years === collect($years)->sortDesc()->values()->all();
        });
});

it('prikazuje listu i detalj', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();

    $this->get(route('invoices.index'))->assertSuccessful()->assertSee($invoice->invoice_number);
    $this->get(route('invoices.show', $invoice))->assertSuccessful()->assertSee('Usluga');
});

it('servira punu stranicu detalja računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee($invoice->invoice_number)
        ->assertSee('<!DOCTYPE html>', false);
});

it('povezuje fiskalnu sekciju računa sa pomoći', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee(route('help').'#fiskalizacija', false);
});

it('prikazuje Alpine prekidače za priloge maila', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('x-model="emailForm.attach_pdf"', false)
        ->assertSee('x-model="emailForm.attach_fiscal_record_ids"', false);
});

it('učitava slike fiskalnih računa jednim upitom na detalju', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();
    $original = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'X-1']);
    $copy = $invoice->fiscalRecords()->create(['type' => 'copy', 'fiscal_invoice_number' => 'X-2']);
    $receiptStore = app(FiscalReceiptStore::class);

    $receiptStore->store($original, 'original receipt');
    $receiptStore->store($copy, 'copy receipt');

    $receiptQueries = 0;

    DB::listen(function (QueryExecuted $query) use (&$receiptQueries): void {
        if (str_contains($query->sql, 'fiscal_receipts')) {
            $receiptQueries++;
        }
    });

    $this->get(route('invoices.show', $invoice))->assertSuccessful();

    expect($receiptQueries)->toBe(1);
});

it('generiše PDF na svim predlošcima', function (string $template) {
    $this->post(route('invoices.store'), invoicePayload(['template' => $template]));
    $invoice = Invoice::firstOrFail();

    $pdf = app(InvoicePdfService::class)->contents($invoice);

    expect($pdf)->toStartWith('%PDF-')
        ->and(strlen($pdf))->toBeGreaterThan(10000)
        ->toBeLessThan(150000);
})->with(['classic', 'modern', 'minimal', 'standard', 'programmer', 'blueprint', 'terminal', 'protocol', 'kernel', 'terminal-light', 'editor', 'signal', 'ops-console', 'shell', 'workstation', 'terminal-matrix', 'programmer-catalog', 'editor-margin', 'signal-plot', 'ops-board', 'git-diff', 'network-packet', 'vscode-dark', 'vscode-light', 'phpstorm-dark', 'phpstorm-light']);

it('dodaje potpis ovlaštenog lica na svaki PDF predložak', function (string $template): void {
    $invoice = makeInvoice(['template' => $template]);

    expect(app(InvoicePdfService::class)->html($invoice, DocumentTemplate::from($template)))
        ->toContain(in_array($template, ['classic', 'modern', 'minimal', 'standard'], true)
            ? 'Izdao'
            : 'Potpis ovlaštenog lica');
})->with(['classic', 'modern', 'minimal', 'standard', 'programmer', 'blueprint', 'terminal', 'protocol', 'kernel', 'terminal-light', 'editor', 'signal', 'ops-console', 'shell', 'workstation', 'terminal-matrix', 'programmer-catalog', 'editor-margin', 'signal-plot', 'ops-board', 'git-diff', 'network-packet', 'vscode-dark', 'vscode-light', 'phpstorm-dark', 'phpstorm-light']);

it('prikazuje potpis i u pregledu predloška', function (): void {
    $this->get(route('settings.templates.preview', [
        'template' => DocumentTemplate::Terminal,
        'embedded' => 1,
    ]))
        ->assertSuccessful()
        ->assertSee('Potpis ovlaštenog lica');
});

it('koristi lokalizovane oznake u programerskim predlošcima', function (): void {
    $invoice = makeInvoice(['template' => 'terminal']);

    expect(app(InvoicePdfService::class)->html($invoice, DocumentTemplate::Terminal))
        ->not->toContain('INVOICE.SESSION')
        ->not->toContain('RESOURCE')
        ->not->toContain('PAYMENT_ENDPOINTS')
        ->toContain('RAČUN')
        ->toContain('STAVKA')
        ->toContain('PLAĆANJE');
});

it('koristi podešeni simbol valute na računu i PDF-u', function (string $pdfView): void {
    Currency::query()->where('code', 'BAM')->update(['symbol' => 'KM']);
    $invoice = makeInvoice();

    $this->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('1,00 KM');

    expect(renderPdfView($invoice, view: $pdfView))->toContain('1,00 KM');
})->with([
    'classic' => 'pdf.invoice',
    'modern' => 'pdf.invoice-modern',
    'minimal' => 'pdf.invoice-minimal',
    'standard' => 'pdf.invoice-standard',
    'programmer' => 'pdf.invoice-programmer',
    'blueprint' => 'pdf.invoice-blueprint',
    'terminal' => 'pdf.invoice-terminal',
    'protocol' => 'pdf.invoice-protocol',
    'kernel' => 'pdf.invoice-kernel',
    'terminal-light' => 'pdf.invoice-terminal-light',
    'editor' => 'pdf.invoice-editor',
    'signal' => 'pdf.invoice-signal',
    'ops-console' => 'pdf.invoice-ops-console',
    'shell' => 'pdf.invoice-shell',
    'workstation' => 'pdf.invoice-workstation',
    'terminal-matrix' => 'pdf.invoice-light-systems',
    'programmer-catalog' => 'pdf.invoice-light-systems',
    'editor-margin' => 'pdf.invoice-light-systems',
    'signal-plot' => 'pdf.invoice-light-systems',
    'ops-board' => 'pdf.invoice-light-systems',
    'git-diff' => 'pdf.invoice-git-diff',
    'network-packet' => 'pdf.invoice-network-packet',
    'vscode-dark' => 'pdf.invoice-vscode-dark',
    'vscode-light' => 'pdf.invoice-vscode-light',
    'phpstorm-dark' => 'pdf.invoice-phpstorm-dark',
    'phpstorm-light' => 'pdf.invoice-phpstorm-light',
]);

it('blueprint predložak ima zaseban nacrtni izgled', function () {
    $html = renderPdfView(makeInvoice(), view: 'pdf.invoice-blueprint');

    expect($html)->toContain('NACRT')
        ->and($html)->toContain('#0b6f8c')
        ->and($html)->toContain('Referenca');
});

it('programerski predložak ima samostalan profesionalni izgled', function () {
    $html = renderPdfView(makeInvoice(), view: 'pdf.invoice-programmer');

    expect($html)->toContain('NAPLATA / SOFTVERSKE USLUGE')
        ->and($html)->toContain('#111a2e')
        ->and($html)->toContain('Podaci za plaćanje');
});

it('novi programerski predlošci imaju različite vizuelne identitete', function (string $view, string $marker, string $color): void {
    $html = renderPdfView(makeInvoice(), view: $view);

    expect($html)->toContain($marker)
        ->and($html)->toContain($color);
})->with([
    'terminal' => ['pdf.invoice-terminal', 'INVOICE.SESSION', '#a3e635'],
    'protocol' => ['pdf.invoice-protocol', 'PROTOCOL / INVOICE', '#2563eb'],
    'kernel' => ['pdf.invoice-kernel', 'KERNEL // BILLING', '#f97316'],
    'terminal-light' => ['pdf.invoice-terminal-light', 'račun --izdaj', '#0f766e'],
    'editor' => ['pdf.invoice-editor', 'račun.faktura', '#c084fc'],
    'signal' => ['pdf.invoice-signal', 'SIGNAL_01', '#ec4899'],
    'ops-console' => ['pdf.invoice-ops-console', 'OPS::RAČUN', '#22d3ee'],
    'shell' => ['pdf.invoice-shell', 'račun@lokalno', '#d97706'],
    'workstation' => ['pdf.invoice-workstation', 'RADNA_STANICA', '#4f46e5'],
]);

it('nudi PDF na preuzimanje', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();
    System::shouldReceive('isMobile')->once()->andReturnFalse();

    $this->get(route('invoices.pdf', $invoice))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('koristi čitljivo ime za PDF datoteku', function () {
    $this->post(route('invoices.store'), invoicePayload());

    expect(app(InvoicePdfService::class)->filename(Invoice::firstOrFail()))
        ->toBe('faktura-0001-'.date('Y').'.pdf');
});

it('šalje račun mailom sa PDF prilogom', function () {
    Mail::fake();

    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->postJson(route('invoices.email', $invoice), [
        'to' => 'kupac@example.com',
        'subject' => 'Račun '.$invoice->invoice_number,
        'body' => 'U prilogu je račun.',
        'attach_pdf' => true,
    ])->assertSuccessful()->assertJson(['message' => 'Račun je poslat na email.']);

    // Privremeni PDF se briše čim slanje prođe, pa se provjerava da je bio priložen,
    // a da prilog stvarno nastane pokriva test ispod.
    Mail::assertSent(InvoiceMail::class, function ($mail) {
        return $mail->hasTo('kupac@example.com') && $mail->pdfPath !== null;
    });
});

it('prilaže PDF i fiskalni račun sa pravim sadržajem', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail()->load('fiscalRecords');

    $record = $invoice->fiscalRecords()->create(['type' => 'original']);
    app(FiscalReceiptStore::class)->store($record, 'bajtovi-racuna', 'pdf');
    $invoice->load('fiscalRecords.receipt');

    $path = storage_path('app/private/test-'.uniqid().'.pdf');
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, app(InvoicePdfService::class)->contents($invoice));

    $attachments = (new InvoiceMail(
        invoice: $invoice, emailSubject: 'Račun', body: 'Tekst',
        pdfPath: $path, attachFiscalRecordIds: [$record->id],
        receipts: app(FiscalReceiptStore::class),
    ))->attachments();

    expect($attachments)->toHaveCount(2);

    @unlink($path);
});

it('traži ispravnog primaoca', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $this->postJson(route('invoices.email', Invoice::firstOrFail()), [
        'to' => 'nije-email', 'subject' => '', 'body' => '',
    ])->assertUnprocessable()->assertJsonValidationErrors(['to', 'subject', 'body']);
});

it('prilaže fiskalni račun i kaže kad ga nema', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $withImage = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'X-1']);
    app(FiscalReceiptStore::class)->store($withImage, 'binarni-sadrzaj', 'png');
    $withoutImage = $invoice->fiscalRecords()->create(['type' => 'copy', 'fiscal_invoice_number' => 'X-2']);

    Mail::fake();

    $this->postJson(route('invoices.email', $invoice), [
        'to' => 'kupac@example.com',
        'subject' => 'Račun',
        'body' => 'Tekst',
        'attach_pdf' => false,
        'attach_fiscal_record_ids' => [$withImage->id, $withoutImage->id],
    ])->assertSuccessful()
        ->assertJson(['message' => 'Račun je poslat, ali fiskalni račun nije priložen jer sadržaja nema.']);

    Mail::assertSent(InvoiceMail::class, function ($mail) use ($withImage) {
        return $mail->attachFiscalRecordIds === [$withImage->id];
    });
});

it('servira sliku fiskalnog računa iz baze', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $record = Invoice::firstOrFail()->fiscalRecords()->create(['type' => 'original']);
    app(FiscalReceiptStore::class)->store($record, 'sadrzaj-slike', 'png');

    $this->get(route('invoices.receipt', $record))
        ->assertSuccessful()
        ->assertHeader('content-type', 'image/png')
        ->assertSee('sadrzaj-slike');
});

it('ne stavlja kosu crtu u ime priloga', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail()->load('fiscalRecords');

    $path = storage_path('app/private/test-'.uniqid().'.pdf');
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, '%PDF-');

    $name = (new InvoiceMail(
        invoice: $invoice, emailSubject: 'x', body: 'x', pdfPath: $path,
    ))->attachments()[0]->as;

    expect($name)->toBe('racun_0001-'.date('Y').'.pdf');

    @unlink($path);
});

it('kopira podrazumijevanu napomenu dokumenta na novi račun', function () {
    $documents = app(DocumentSettings::class);
    $documents->invoice_notes = 'Hvala na povjerenju.';
    $documents->save();

    $this->post(route('invoices.store'), invoicePayload());

    expect(Invoice::firstOrFail()->notes)->toBe('Hvala na povjerenju.');
});

it('čuva izmijenjenu napomenu samo na kreiranom računu', function () {
    $documents = app(DocumentSettings::class);
    $documents->invoice_notes = 'Zadana napomena.';
    $documents->save();

    $this->post(route('invoices.store'), invoicePayload([
        'notes' => 'Dogovorena napomena za ovaj račun.',
    ]))->assertRedirect();

    expect(Invoice::firstOrFail()->notes)->toBe('Dogovorena napomena za ovaj račun.');
});

it('prikazuje izmjenjivu zadanu napomenu pri kreiranju računa', function () {
    $documents = app(DocumentSettings::class);
    $documents->invoice_notes = 'Hvala na povjerenju.';
    $documents->save();

    $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->assertSee('name="notes"', false)
        ->assertSee('Hvala na povjerenju.');
});

it('smješta napomenu u sklopiva dodatna polja računa', function () {
    $html = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('Valuta, predložak, jezik i napomena')
        ->and(strpos($html, 'x-show="showMore"'))->toBeLessThan(strpos($html, 'id="notes"'));
});

it('prenosi napomenu sačuvanu kroz podešavanja u formu novog računa', function () {
    $documents = app(DocumentSettings::class);
    $numbering = app(NumberingSettings::class);

    $this->put(route('settings.general.update'), [
        'pad_zeros' => $numbering->pad_zeros,
        'invoice_prefix' => $numbering->invoice_prefix,
        'invoice_starting_number' => $numbering->invoice_starting_number,
        'reset_yearly' => $numbering->reset_yearly,
        'template' => $documents->template,
        'language' => $documents->language,
        'invoice_due_days' => $documents->invoice_due_days,
        'invoice_notes' => 'Napomena iz podešavanja.',
    ])->assertRedirect(route('settings.general.edit'));

    $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->assertSee('id="notes"', false)
        ->assertSee('Napomena iz podešavanja.')
        ->assertSee('Zadana napomena iz Podešavanja je unesena iznad');
});

it('primjenjuje rok plaćanja i podrazumijevani način plaćanja iz podešavanja na novi račun', function () {
    $documents = app(DocumentSettings::class);
    $numbering = app(NumberingSettings::class);

    $this->put(route('settings.general.update'), [
        'pad_zeros' => $numbering->pad_zeros,
        'invoice_prefix' => $numbering->invoice_prefix,
        'invoice_starting_number' => $numbering->invoice_starting_number,
        'reset_yearly' => $numbering->reset_yearly,
        'template' => $documents->template,
        'language' => $documents->language,
        'invoice_due_days' => 30,
        'invoice_notes' => $documents->invoice_notes,
    ])->assertRedirect(route('settings.general.edit'));

    $this->put(route('settings.fiscal.update'), [
        'base_url' => 'http://192.168.31.103:3566',
        'cashier' => 'Prodavac',
        'device_mode' => 'local',
        'receipt_layout' => 'Slip',
        'receipt_document_format' => 'Png',
        'default_payment_type' => 'WireTransfer',
        'receipt_header_text_lines' => '',
    ])->assertRedirect(route('settings.fiscal.edit'));

    $html = $this->get(route('invoices.create'))->assertSuccessful()->getContent();

    expect($html)
        ->toContain('name="due_date"')
        ->toContain(now()->addDays(30)->format('Y-m-d'))
        ->toContain('<option value="WireTransfer" selected>Bankovni transfer</option>');
});

it('u pregledniku vraća PDF na preuzimanje', function () {
    $this->post(route('invoices.store'), invoicePayload());
    System::shouldReceive('isMobile')->once()->andReturnFalse();

    $this->get(route('invoices.pdf', Invoice::firstOrFail()))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('u Jumpu priprema PDF datoteku iz detalja računa', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.show', $invoice))
        ->assertSuccessful()
        ->assertSee('$data.preparePdf', false)
        ->assertSee(app(InvoicePdfService::class)->filename($invoice));

    expect(file_get_contents(resource_path('views/invoices/detail.blade.php')))
        ->toContain("getenv('JUMP_BRIDGE_PORT') !== false || isMobile()");
});

it('u Jumpu servira PDF koji preglednik dijeli kao datoteku', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();
    putenv('JUMP_BRIDGE_PORT=3002');

    try {
        $this->get(route('invoices.pdf', $invoice))
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename='.$this->app->make(InvoicePdfService::class)->filename($invoice));
    } finally {
        putenv('JUMP_BRIDGE_PORT');
    }
});

it('šalje PDF kao JSON payload kroz Jump proxy', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();
    putenv('JUMP_BRIDGE_PORT=3002');

    try {
        $this->getJson(route('invoices.pdf', [$invoice, 'mobile_payload' => 1]))
            ->assertSuccessful()
            ->assertJsonPath('mime', 'application/pdf')
            ->assertJsonPath('filename', app(InvoicePdfService::class)->filename($invoice))
            ->assertJsonPath('contents', fn (string $contents): bool => str_starts_with(base64_decode($contents, true) ?: '', '%PDF-'));
    } finally {
        putenv('JUMP_BRIDGE_PORT');
    }
});

it('na telefonu predaje PDF sistemskom dijalogu kao datoteku', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();
    $temporaryDirectory = storage_path('app/private/mobile-share-test');
    config(['nativephp-internal.tempdir' => $temporaryDirectory]);
    putenv('JUMP_BRIDGE_PORT');
    System::shouldReceive('isMobile')->once()->andReturnTrue();
    Share::shouldReceive('file')->once()->withArgs(function (string $title, string $text, string $path) use ($invoice): bool {
        return $title === 'Račun '.$invoice->invoice_number
            && $text === 'Račun '.$invoice->invoice_number
            && is_file($path)
            && str_starts_with($path, storage_path('app/private/mobile-share-test/'));
    });

    try {
        $this->getJson(route('invoices.pdf', $invoice))
            ->assertSuccessful()
            ->assertJson(['message' => 'PDF je spreman za čuvanje ili dijeljenje.']);
    } finally {
        foreach (glob($temporaryDirectory.'/*') ?: [] as $path) {
            @unlink($path);
        }

        @rmdir($temporaryDirectory);
    }
});

it('čuva opis stavke', function () {
    $payload = invoicePayload();
    $payload['items'][0]['description'] = 'Radovi po ugovoru 12/2026';

    $this->post(route('invoices.store'), $payload);

    expect(Invoice::firstOrFail()->items->first()->description)->toBe('Radovi po ugovoru 12/2026');
});

it('poštuje početni broj i prefiks iz podešavanja', function () {
    $settings = app(NumberingSettings::class);
    $settings->invoice_starting_number = 100;
    $settings->invoice_prefix = 'INV';
    $settings->pad_zeros = 5;
    $settings->save();

    $this->post(route('invoices.store'), invoicePayload());

    expect(Invoice::firstOrFail()->invoice_number)->toBe('INV00100/'.date('Y'));
});

it('nastavlja numeraciju kroz godine kad je godišnji reset isključen', function () {
    $settings = app(NumberingSettings::class);
    $settings->reset_yearly = false;
    $settings->save();

    $this->post(route('invoices.store'), invoicePayload(['date' => '2025-06-01', 'due_date' => '2025-06-15']));
    $this->post(route('invoices.store'), invoicePayload(['date' => '2026-06-01', 'due_date' => '2026-06-15']));

    expect(Invoice::orderBy('id')->pluck('invoice_number')->all())->toBe(['0001/2025', '0002/2026']);
});

it('uzima godinu broja sa datuma računa, ne sa današnjeg', function () {
    $this->post(route('invoices.store'), invoicePayload(['date' => '2025-12-20', 'due_date' => '2025-12-31']));

    expect(Invoice::firstOrFail()->invoice_number)->toEndWith('/2025');
});

it('ignoriše neispravne stare brojeve pri nastavku numeracije', function () {
    Invoice::create([
        'invoice_number' => 'stari-neispravan-broj',
        'date' => now(),
        'due_date' => now(),
    ]);
    Invoice::create([
        'invoice_number' => '0007/'.date('Y'),
        'date' => now(),
        'due_date' => now(),
    ]);

    $numbers = app(InvoiceNumber::class);

    expect($numbers->parse('neispravno'))->toBeNull()
        ->and($numbers->next())->toBe('0008/'.date('Y'));
});

it('ne ruši listu na neispravan filter', function () {
    $this->get(route('invoices.index', ['status' => 'bogus', 'payment_type' => 'bogus']))->assertSuccessful();
});

it('prikazuje PDV na PDF-u i kad kompanija nije obveznik', function () {
    $company = app(CompanySettings::class);
    $company->is_vat_obligor = false;
    $company->save();

    $this->post(route('invoices.store'), invoicePayload());

    // Osnovica + PDV mora davati ukupno; inače dokument sam sebi ne odgovara.
    expect(renderPdfView(Invoice::firstOrFail(), $company))->toContain('PDV');
});
