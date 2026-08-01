<?php

use App\Mail\BackupMail;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Services\BackupArchive;
use App\Services\FiscalReceiptStore;
use App\Services\MailService;
use App\Settings\BackupSettings;
use App\Settings\CompanySettings;
use App\Settings\MailSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/*
 * Mail podešavanja i MailService. Slanje računa je pokriveno u InvoiceTest; ovdje
 * stoji sve što odlučuje kroz koji SMTP i sa koje adrese pošta ide.
 *
 * Napomena: pestphp/pest-plugin-laravel nije instaliran, pa nema
 * `use function Pest\Laravel\mock;` — koristi se $this->mock() iz Tests\TestCase.
 */

/** Podešeni SMTP firme. */
function ownSmtp(array $overrides = []): MailSettings
{
    $settings = app(MailSettings::class);

    foreach ($overrides + [
        'from_address' => 'racuni@firma.ba',
        'from_name' => 'Firma d.o.o.',
        'host' => 'smtp.firma.ba',
        'port' => 465,
        'username' => 'racuni',
        'password' => 'tajna',
        'encryption' => 'ssl',
    ] as $key => $value) {
        $settings->{$key} = $value;
    }

    $settings->save();

    return $settings;
}

it('šalje sa adrese iz podešavanja', function () {
    ownSmtp();

    expect(app(MailService::class)->from())->toBe(['racuni@firma.ba', 'Firma d.o.o.']);
});

it('pada na adresu iz konfiguracije kad podešavanja nemaju svoju', function () {
    config(['mail.from.address' => 'noreply@example.test', 'mail.from.name' => 'Kalkulatron']);

    expect(app(MailService::class)->from())->toBe(['noreply@example.test', 'Kalkulatron']);
});

it('ne gradi svoj mailer bez SMTP hosta', function () {
    expect(app(MailService::class)->mailer())->toBeNull();
});

it('gradi mailer iz podešenog SMTP-a', function () {
    ownSmtp();

    expect(app(MailService::class)->mailer())->not->toBeNull()
        ->and(config('mail.mailers.app_smtp'))
        ->toMatchArray([
            'transport' => 'smtp',
            'host' => 'smtp.firma.ba',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'racuni',
            'password' => 'tajna',
            'timeout' => 20,
        ]);
});

it('uzima port 587 kad podešavanja ne kažu koji', function () {
    ownSmtp(['port' => null]);

    app(MailService::class)->mailer();

    expect(config('mail.mailers.app_smtp.port'))->toBe(587);
});

it('kaže da SMTP nije podešen umjesto da tiho ne pošalje', function () {
    // Podrazumijevani mailer je smtp bez hosta — tako izgleda svježa instalacija.
    config(['mail.default' => 'smtp', 'mail.mailers.smtp' => ['transport' => 'smtp', 'host' => null]]);

    app(MailService::class)->send('kupac@example.com', new InvoiceMail(
        invoice: makeInvoice(), emailSubject: 'Račun', body: 'Tekst',
    ));
})->throws(RuntimeException::class, 'SMTP nije podešen.');

it('u razvoju šalje kroz podrazumijevani mailer bez pogovora', function () {
    Mail::fake();

    // MAIL_MAILER=array u phpunit.xml — host nije potreban.
    app(MailService::class)->send('kupac@example.com', new InvoiceMail(
        invoice: makeInvoice(), emailSubject: 'Račun', body: 'Tekst',
    ));

    Mail::assertSent(InvoiceMail::class);
});

it('vraća grešku slanja kao 422, sa porukom iz izuzetka', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $this->mock(MailService::class, function ($mock) {
        $mock->shouldReceive('from')->andReturn([null, null]);
        $mock->shouldReceive('send')->andThrow(new RuntimeException('SMTP nije podešen.'));
    });

    $this->postJson(route('invoices.email', Invoice::firstOrFail()), [
        'to' => 'kupac@example.com', 'subject' => 'Račun', 'body' => 'Tekst', 'attach_pdf' => true,
    ])->assertUnprocessable()
        ->assertJson(['message' => 'Slanje nije uspjelo: SMTP nije podešen.']);
});

it('ne otkriva tehnički detalj neočekivane greške slanja', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $this->mock(MailService::class, function ($mock) {
        $mock->shouldReceive('from')->andReturn([null, null]);
        $mock->shouldReceive('send')->andThrow(new LogicException('tajni tehnički detalj'));
    });

    $this->postJson(route('invoices.email', Invoice::firstOrFail()), [
        'to' => 'kupac@example.com', 'subject' => 'Račun', 'body' => 'Tekst',
    ])->assertUnprocessable()
        ->assertJson(['message' => 'Slanje emaila trenutno nije uspjelo. Pokušajte ponovo.']);
});

it('ne ostavlja privremeni PDF kad slanje padne', function () {
    $this->post(route('invoices.store'), invoicePayload());

    $this->mock(MailService::class, function ($mock) {
        $mock->shouldReceive('from')->andReturn([null, null]);
        $mock->shouldReceive('send')->andThrow(new RuntimeException('Pao SMTP.'));
    });

    $this->postJson(route('invoices.email', Invoice::firstOrFail()), [
        'to' => 'kupac@example.com', 'subject' => 'Račun', 'body' => 'Tekst', 'attach_pdf' => true,
    ])->assertUnprocessable();

    expect(glob(storage_path('app/private/racun-*.pdf')))->toBe([]);
});

it('nosi link za provjeru posljednjeg fiskalnog računa', function () {
    $invoice = fiscalizedInvoice();
    Mail::fake();

    $this->postJson(route('invoices.email', $invoice), [
        'to' => 'kupac@example.com', 'subject' => 'Račun', 'body' => 'Tekst',
    ])->assertSuccessful();

    Mail::assertSent(fn (InvoiceMail $mail) => $mail->verificationUrl === 'https://example.test/v/?vl=x');
});

it('imenuje prilog fiskalnog računa po tipu i formatu zapisa', function (string $type, string $extension, string $expected) {
    $invoice = makeInvoice();
    $record = $invoice->fiscalRecords()->create(['type' => $type, 'fiscal_invoice_number' => 'X-1']);
    app(FiscalReceiptStore::class)->store($record, 'sadrzaj', $extension);

    $attachment = (new InvoiceMail(
        invoice: $invoice->load('fiscalRecords.receipt'), emailSubject: 'Račun', body: 'Tekst',
        attachFiscalRecordIds: [$record->id],
    ))->attachments()[0];

    expect($attachment->as)->toBe('fiskalni-racun_0001-'.date('Y').$expected.'.'.$extension);
})->with([
    'original' => ['original', 'png', ''],
    'kopija' => ['copy', 'pdf', '-kopija'],
    'refundacija' => ['refund', 'html', '-refundacija'],
]);

it('gradi sadržaj i pošiljaoca računa bez priloga koji ne postoji', function () {
    $invoice = makeInvoice();
    $company = app(CompanySettings::class);
    $company->name = 'Kalkulatron d.o.o.';
    $company->save();

    $mail = new InvoiceMail(
        invoice: $invoice,
        emailSubject: 'Račun '.$invoice->invoice_number,
        body: 'Pozdrav, račun je u prilogu.',
        verificationUrl: 'https://provjeri.example.test/racun',
        pdfPath: '/tmp/nepostojeci-racun.pdf',
        fromAddress: 'racuni@firma.ba',
        fromName: 'Firma d.o.o.',
    );

    expect($mail->envelope()->subject)->toBe('Račun '.$invoice->invoice_number)
        ->and($mail->envelope()->from->address)->toBe('racuni@firma.ba')
        ->and($mail->content()->view)->toBe('emails.invoice')
        ->and($mail->content()->with['company']->name)->toBe('Kalkulatron d.o.o.')
        ->and($mail->attachments())->toBe([]);

    expect($mail->render())->toContain('Pozdrav, račun je u prilogu.')
        ->and($mail->render())->toContain('https://provjeri.example.test/racun');
});

it('ne prilaže tuđi ili nedostajući fiskalni dokument i ostavlja zapis u logu', function () {
    $invoice = makeInvoice();
    $missing = $invoice->fiscalRecords()->create(['type' => 'original']);
    $foreign = makeInvoice()->fiscalRecords()->create(['type' => 'copy']);
    Log::spy();

    $attachments = (new InvoiceMail(
        invoice: $invoice,
        emailSubject: 'Račun',
        body: 'Tekst',
        attachFiscalRecordIds: [$missing->id, $foreign->id],
    ))->attachments();

    expect($attachments)->toBe([]);
    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Fiskalni račun nije priložen, sadržaja nema', [
            'invoice_id' => $invoice->id,
            'fiscal_record_id' => $missing->id,
        ]);
});

it('čuva mail podešavanja', function () {
    $this->put(route('settings.mail.update'), [
        'from_address' => 'racuni@firma.ba', 'from_name' => 'Firma', 'host' => 'smtp.firma.ba',
        'port' => 587, 'username' => 'racuni', 'password' => 'tajna', 'encryption' => 'tls',
    ])->assertRedirect(route('settings.mail.edit'));

    expect(app(MailSettings::class))
        ->host->toBe('smtp.firma.ba')
        ->port->toBe(587)
        ->encryption->toBe('tls');
});

it('ne briše sačuvanu lozinku praznim poljem', function () {
    ownSmtp();

    // Polje lozinke se nikad ne popunjava nazad, pa prazno znači „ne mijenjaj".
    $this->put(route('settings.mail.update'), [
        'host' => 'smtp.firma.ba', 'port' => 465, 'username' => 'racuni', 'password' => '',
    ])->assertRedirect(route('settings.mail.edit'));

    expect(app(MailSettings::class)->password)->toBe('tajna');
});

it('odbija neispravna mail podešavanja', function (array $payload, string $error) {
    $this->put(route('settings.mail.update'), $payload)->assertSessionHasErrors($error);
})->with([
    'adresa nije email' => [['from_address' => 'nije-email'], 'from_address'],
    'port nije broj' => [['port' => 'brzo'], 'port'],
    'port ispod jedan' => [['port' => 0], 'port'],
    'port iznad opsega' => [['port' => 70000], 'port'],
    'nepoznata enkripcija' => [['encryption' => 'starttls'], 'encryption'],
    'predug host' => [['host' => str_repeat('a', 256)], 'host'],
]);

it('prima mail podešavanja bez ijednog polja', function () {
    // Prazna podešavanja su ispravna: tada se šalje preko podrazumijevanog mailera.
    $this->put(route('settings.mail.update'), [])->assertSessionHasNoErrors();
});

it('pravi samostalan ZIP sa PDF računima, fiskalnim dokumentima i manifestom', function () {
    $invoice = makeInvoice();
    $original = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'F-1']);
    $copy = $invoice->fiscalRecords()->create(['type' => 'copy', 'fiscal_invoice_number' => 'F-2']);
    $refund = $invoice->fiscalRecords()->create(['type' => 'refund', 'fiscal_invoice_number' => 'F-3']);
    $receipts = app(FiscalReceiptStore::class);
    $receipts->store($original, 'png dokument', 'png');
    $receipts->store($copy, '%PDF-1.7', 'pdf');
    $receipts->store($refund, '<html>storno</html>', 'html');

    $backup = app(BackupArchive::class)->create();
    $zip = new ZipArchive;

    expect($zip->open($backup['path']))->toBeTrue()
        ->and($zip->locateName('racuni/0001-'.date('Y').'.pdf'))->not->toBeFalse()
        ->and($zip->locateName('fiskalni/0001-'.date('Y').'-original.png'))->not->toBeFalse()
        ->and($zip->locateName('fiskalni/0001-'.date('Y').'-kopija.pdf'))->not->toBeFalse()
        ->and($zip->locateName('fiskalni/0001-'.date('Y').'-refundacija.html'))->not->toBeFalse()
        ->and($zip->getFromName('manifest.csv'))->toContain('fiskalni_broj')
        ->and($backup['invoice_count'])->toBe(1)
        ->and($backup['fiscal_document_count'])->toBe(3);

    $zip->close();
    unlink($backup['path']);
});

it('uključuje svaki račun u backup i kada datumi nisu redom upisa', function () {
    $newer = makeInvoice();
    $newer->update(['date' => now()->addDay()]);

    $older = makeInvoice();
    $older->update(['date' => now()->subDay()]);

    $backup = app(BackupArchive::class)->create();
    $zip = new ZipArchive;

    expect($zip->open($backup['path']))->toBeTrue()
        ->and($zip->locateName('racuni/'.str_replace('/', '-', $newer->invoice_number).'.pdf'))->not->toBeFalse()
        ->and($zip->locateName('racuni/'.str_replace('/', '-', $older->invoice_number).'.pdf'))->not->toBeFalse()
        ->and($backup['invoice_count'])->toBe(2);

    $zip->close();
    unlink($backup['path']);
});

it('ZIP backup uključuje samo odabrani sadržaj', function () {
    $invoice = makeInvoice();
    $record = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'F-1']);
    app(FiscalReceiptStore::class)->store($record, 'fiskalni dokument', 'html');

    $backup = app(BackupArchive::class)->create([
        'invoices' => false,
        'fiscal_documents' => true,
        'manifest' => false,
    ]);
    $zip = new ZipArchive;

    expect($zip->open($backup['path']))->toBeTrue()
        ->and($zip->locateName('racuni/0001-'.date('Y').'.pdf'))->toBeFalse()
        ->and($zip->locateName('fiskalni/0001-'.date('Y').'-original.html'))->not->toBeFalse()
        ->and($zip->locateName('manifest.csv'))->toBeFalse()
        ->and($backup['invoice_count'])->toBe(0)
        ->and($backup['fiscal_document_count'])->toBe(1);

    $zip->close();
    unlink($backup['path']);
});

it('pravi pojedinačne priloge bez ZIP ekstenzije', function () {
    $invoice = makeInvoice();
    $record = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'F-1']);
    app(FiscalReceiptStore::class)->store($record, 'fiskalni dokument', 'html');

    $backup = app(BackupArchive::class)->raw([
        'invoices' => true,
        'fiscal_documents' => true,
        'manifest' => true,
    ]);

    expect($backup['attachments'])->toHaveCount(3)
        ->and(collect($backup['attachments'])->pluck('name')->all())
        ->toContain('0001-'.date('Y').'.pdf', '0001-'.date('Y').'-original.html', 'manifest.csv')
        ->and($backup['invoice_count'])->toBe(1)
        ->and($backup['fiscal_document_count'])->toBe(1);
});

it('prekida backup sa jasnom greškom kada ZIP datoteka ne može biti otvorena', function () {
    $now = now()->setDate(2040, 1, 2)->setTime(3, 4, 5);
    Carbon::setTestNow($now);
    $path = storage_path('app/private/backups/kalkulatron-backup-2040-01-02_030405.zip');
    @mkdir($path, 0755, true);

    try {
        app(BackupArchive::class)->create();
    } finally {
        Carbon::setTestNow();
        @rmdir($path);
    }
})->throws(RuntimeException::class, 'Nije moguće napraviti ZIP backup.');

it('šalje ZIP backup na podešeni email i pamti posljednje uspješno slanje', function () {
    makeInvoice();
    ownSmtp();
    Mail::fake();

    $this->put(route('settings.backup.update'), ['email' => 'backup@firma.ba'])
        ->assertRedirect(route('settings.backup.edit'));

    $this->post(route('settings.backup.send'), ['delivery_format' => 'zip'])
        ->assertRedirect(route('settings.backup.edit'))
        ->assertSessionHas('status');

    Mail::assertSent(BackupMail::class, fn (BackupMail $mail) => $mail->invoiceCount === 1
        && $mail->fiscalDocumentCount === 0
        && str_ends_with($mail->archiveName, '.zip'));

    expect(app(BackupSettings::class))
        ->email->toBe('backup@firma.ba')
        ->last_backup_at->not->toBeNull()
        ->last_backup_at->toBeInstanceOf(Carbon::class)
        ->last_backup_invoice_count->toBe(1)
        ->last_backup_fiscal_document_count->toBe(0);
});

it('šalje backup kao pojedinačne dokumente', function () {
    $invoice = makeInvoice();
    $record = $invoice->fiscalRecords()->create(['type' => 'original', 'fiscal_invoice_number' => 'F-1']);
    app(FiscalReceiptStore::class)->store($record, 'fiskalni dokument', 'html');
    ownSmtp();
    Mail::fake();

    $this->put(route('settings.backup.update'), ['email' => 'backup@firma.ba']);

    $this->post(route('settings.backup.send'), [
        'delivery_format' => 'raw',
        'include_invoices' => 0,
        'include_fiscal_documents' => 1,
        'include_manifest' => 0,
    ])
        ->assertRedirect(route('settings.backup.edit'))
        ->assertSessionHas('status');

    Mail::assertSent(BackupMail::class, function (BackupMail $mail): bool {
        return $mail->deliveryFormat === 'raw'
            && count($mail->backupAttachments) === 1
            && collect($mail->backupAttachments)->pluck('name')->contains(fn (string $name): bool => str_ends_with($name, '.html'));
    });
});

it('odbija nepoznat format backupa', function () {
    $this->post(route('settings.backup.send'), ['delivery_format' => 'nepoznat'])
        ->assertSessionHasErrors('delivery_format');
});

it('odbija backup bez PDF računa i fiskalnih dokumenata', function () {
    $this->post(route('settings.backup.send'), [
        'delivery_format' => 'zip',
        'include_invoices' => false,
        'include_fiscal_documents' => false,
        'include_manifest' => true,
    ])->assertSessionHasErrors('include_invoices');
});

it('odbija backup bez ispravnog odredišnog emaila', function () {
    $this->put(route('settings.backup.update'), ['email' => 'nije-email'])
        ->assertSessionHasErrors('email');

    $this->post(route('settings.backup.send'), ['delivery_format' => 'zip'])
        ->assertSessionHas('error', 'Prvo unesite email na koji se šalje backup.');
});

it('jasno pokaže da je slanje backupa pokrenuto i spriječi dupli klik', function () {
    $settings = app(BackupSettings::class);
    $settings->email = 'backup@firma.ba';
    $settings->save();

    $this->get(route('settings.backup.edit'))
        ->assertSuccessful()
        ->assertSee('x-on:submit="sending = true"', false)
        ->assertSee('x-bind:disabled="sending"', false)
        ->assertSee('Pripremam backup...')
        ->assertSee('x-bind:aria-busy="sending"', false)
        ->assertSee('Pripremam dokumente i šaljem ih na email. Ne zatvarajte aplikaciju.')
        ->assertSee('Pojedinačni fajlovi');
});

it('prikazuje vrijeme posljednjeg backupa u sarajevskoj vremenskoj zoni', function () {
    $settings = app(BackupSettings::class);
    $settings->email = 'backup@firma.ba';
    $settings->last_backup_at = Carbon::parse('2026-08-01 12:00:00', 'UTC');
    $settings->last_backup_invoice_count = 2;
    $settings->last_backup_fiscal_document_count = 3;
    $settings->save();

    $this->get(route('settings.backup.edit'))
        ->assertSuccessful()
        ->assertSee('01.08.2026. u 14:00');
});

it('koristi sarajevsku vremensku zonu u cijeloj aplikaciji', function () {
    expect(config('app.timezone'))->toBe('Europe/Sarajevo')
        ->and(now()->getTimezone()->getName())->toBe('Europe/Sarajevo');
});

it('opisuje ZIP prilog i pošiljaoca backupa', function () {
    $mail = new BackupMail(
        archivePath: '/tmp/kalkulatron-backup.zip',
        archiveName: 'kalkulatron-backup.zip',
        invoiceCount: 2,
        fiscalDocumentCount: 3,
        fromAddress: 'backup@firma.ba',
        fromName: 'Firma d.o.o.',
    );

    expect($mail->envelope()->subject)->toContain('Kalkulatron backup')
        ->and($mail->envelope()->from->address)->toBe('backup@firma.ba')
        ->and($mail->content()->view)->toBe('emails.backup')
        ->and($mail->attachments()[0]->as)->toBe('kalkulatron-backup.zip')
        ->and($mail->attachments()[0]->mime)->toBe('application/zip');
});

it('gradi odvojene priloge za raw backup', function () {
    $mail = new BackupMail(
        archivePath: '/tmp/kalkulatron-backup.zip',
        archiveName: 'kalkulatron-backup.zip',
        invoiceCount: 1,
        fiscalDocumentCount: 1,
        deliveryFormat: 'raw',
        backupAttachments: [
            ['name' => 'racun.pdf', 'mime' => 'application/pdf', 'contents' => '%PDF-'],
            ['name' => 'fiskalni.html', 'mime' => 'text/html; charset=UTF-8', 'contents' => '<html></html>'],
        ],
    );

    expect($mail->attachments())->toHaveCount(2)
        ->and($mail->attachments()[0]->as)->toBe('racun.pdf')
        ->and($mail->attachments()[0]->mime)->toBe('application/pdf')
        ->and($mail->attachments()[1]->as)->toBe('fiskalni.html');
});

it('prikaže očekivanu grešku kada izrada backupa ne uspije', function () {
    $settings = app(BackupSettings::class);
    $settings->email = 'backup@firma.ba';
    $settings->save();

    $this->mock(BackupArchive::class, function ($mock): void {
        $mock->shouldReceive('create')->once()->andThrow(new RuntimeException('Arhiva nije dostupna.'));
    });

    $this->post(route('settings.backup.send'), ['delivery_format' => 'zip'])
        ->assertRedirect(route('settings.backup.edit'))
        ->assertSessionHas('error', 'Slanje backupa nije uspjelo: Arhiva nije dostupna.');
});

it('ne otkriva tehnički detalj neočekivane greške backupa', function () {
    $settings = app(BackupSettings::class);
    $settings->email = 'backup@firma.ba';
    $settings->save();

    $this->mock(BackupArchive::class, function ($mock): void {
        $mock->shouldReceive('create')->once()->andThrow(new LogicException('tajni tehnički detalj'));
    });

    $this->post(route('settings.backup.send'), ['delivery_format' => 'zip'])
        ->assertRedirect(route('settings.backup.edit'))
        ->assertSessionHas('error', 'Slanje backupa trenutno nije uspjelo. Pokušajte ponovo.');
});
