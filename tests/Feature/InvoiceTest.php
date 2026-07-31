<?php

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Article;
use App\Models\Client;
use App\Models\Invoice;
use App\Services\FiscalReceiptStore;
use App\Services\InvoiceNumber;
use App\Services\InvoicePdfService;
use App\Settings\CompanySettings;
use App\Settings\NumberingSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function invoicePayload(array $overrides = []): array
{
    return $overrides + [
        'client_id' => Client::create(['name' => 'Kupac d.o.o.'])->id,
        'payment_type' => 'Cash',
        'currency' => 'BAM',
        'template' => 'classic',
        'language' => 'sr_Latn',
        'date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(15)->format('Y-m-d'),
        'items' => [[
            'article_id' => null,
            'name' => 'Usluga',
            'unit' => 'kom',
            'tax_label' => 'F',   // 11%
            'quantity' => 2,
            'unit_price' => '55.50',
        ]],
    ];
}

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

it('oslobađa broj kad se račun obriše', function () {
    $year = date('Y');

    $this->post(route('invoices.store'), invoicePayload());
    $this->post(route('invoices.store'), invoicePayload());

    $this->delete(route('invoices.destroy', Invoice::where('invoice_number', "0002/{$year}")->first()));

    // Broj se izvodi iz računa, pa je 0002 opet slobodan — u v1 nije bio.
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
        ->assertStatus(200)
        ->assertSee('Mermer Gradnja')
        ->assertDontSee('Kafe Bar');

    $this->get(route('invoices.index', ['q' => '0002']))
        ->assertStatus(200)
        ->assertSee('Kafe Bar');
});

it('prikazuje listu i detalj', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::first();

    $this->get(route('invoices.index'))->assertStatus(200)->assertSee($invoice->invoice_number);
    $this->get(route('invoices.show', $invoice))->assertStatus(200)->assertSee('Usluga');
});

it('servira detalje računa kao dio drawera', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $partial = $this->get(route('invoices.show', [$invoice, 'partial' => 1]));

    $partial->assertStatus(200)
        ->assertSee($invoice->invoice_number)
        ->assertSee('Zatvori')
        ->assertDontSee('<!DOCTYPE html>', false);
});

it('generiše PDF na sva četiri predloška', function (string $template) {
    $this->post(route('invoices.store'), invoicePayload(['template' => $template]));
    $invoice = Invoice::firstOrFail();

    $pdf = app(InvoicePdfService::class)->contents($invoice);

    expect($pdf)->toStartWith('%PDF-')->and(strlen($pdf))->toBeGreaterThan(10000);
})->with(['classic', 'modern', 'minimal', 'standard']);

it('nudi PDF na preuzimanje', function () {
    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $this->get(route('invoices.pdf', $invoice))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
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
    ])->assertStatus(200)->assertJson(['message' => 'Račun je poslat na email.']);

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
    $invoice->load('fiscalRecords.receiptImage');

    $path = storage_path('app/private/test-'.uniqid().'.pdf');
    @mkdir(dirname($path), 0755, true);
    file_put_contents($path, app(InvoicePdfService::class)->contents($invoice));

    $attachments = (new InvoiceMail(
        invoice: $invoice, emailSubject: 'Račun', body: 'Tekst',
        pdfPath: $path, attachFiscalRecordIds: [$record->id],
    ))->attachments();

    expect($attachments)->toHaveCount(2);

    @unlink($path);
});

it('traži ispravnog primaoca', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $this->postJson(route('invoices.email', Invoice::firstOrFail()), [
        'to' => 'nije-email', 'subject' => '', 'body' => '',
    ])->assertStatus(422)->assertJsonValidationErrors(['to', 'subject', 'body']);
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
    ])->assertStatus(200)
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
        ->assertStatus(200)
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

it('ispisuje napomenu malog preduzetnika na PDF-u', function () {
    $company = app(CompanySettings::class);
    $company->is_small_entrepreneur = true;
    $company->small_entrepreneur_note = 'Mali preduzetnik — nije u sistemu PDV-a.';
    $company->save();

    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->load('client', 'items', 'fiscalRecords'),
        'company' => $company,
        'bankAccounts' => collect(),
    ])->render();

    expect($html)->toContain('Mali preduzetnik');
});

it('ne ispisuje napomenu kad mali preduzetnik nije uključen', function () {
    $company = app(CompanySettings::class);

    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->load('client', 'items', 'fiscalRecords'),
        'company' => $company,
        'bankAccounts' => collect(),
    ])->render();

    expect($html)->not->toContain('Mali preduzetnik');
});

it('u pregledniku vraća PDF na preuzimanje', function () {
    $this->post(route('invoices.store'), invoicePayload());

    // isMobile() je false van upakovane aplikacije, pa ostaje obično preuzimanje.
    $this->get(route('invoices.pdf', Invoice::firstOrFail()))
        ->assertStatus(200)
        ->assertHeader('content-type', 'application/pdf');
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

it('ne ruši listu na neispravan filter', function () {
    $this->get(route('invoices.index', ['status' => 'bogus', 'payment_type' => 'bogus']))->assertStatus(200);
});

it('prikazuje PDV na PDF-u i kad kompanija nije obveznik', function () {
    $company = app(CompanySettings::class);
    $company->is_vat_obligor = false;
    $company->save();

    $this->post(route('invoices.store'), invoicePayload());
    $invoice = Invoice::firstOrFail();

    $html = view('pdf.invoice', [
        'invoice' => $invoice->load('client', 'items', 'fiscalRecords'),
        'company' => $company,
        'bankAccounts' => collect(),
    ])->render();

    // Osnovica + PDV mora davati ukupno; inače dokument sam sebi ne odgovara.
    expect($html)->toContain('PDV');
});
