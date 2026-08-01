<?php

use App\Services\TemporaryDemoBuildSettings;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;

test('it seeds the temporary demo profile on pristine settings', function (): void {
    $seeded = app(TemporaryDemoBuildSettings::class)->seedIfPristine();
    $fiscal = app(FiscalSettings::class);
    $mail = app(MailSettings::class);

    expect($seeded)->toBeTrue()
        ->and(app(CompanySettings::class))
        ->name->toBe('Throwcode sp Dusko Maric')
        ->is_vat_obligor->toBeFalse()
        ->and(app(DocumentSettings::class))
        ->language->toBe('sr_Latn')
        ->invoice_due_days->toBe(15)
        ->invoice_notes->toContain('nije u sistemu PDV-a.')
        ->and($fiscal)
        ->device_mode->toBe('cloud')
        ->receipt_document_format->toBe('Png')
        ->default_payment_type->toBe('WireTransfer')
        ->and($fiscal->api_key)->not->toBeEmpty()
        ->and($mail)
        ->host->toBe('smtp.gmail.com')
        ->port->toBe(587)
        ->and($mail->password)->not->toBeEmpty();
});

test('it never overwrites an existing company, fiscal device, or mail configuration', function (): void {
    $company = app(CompanySettings::class);
    $company->name = 'Moja firma d.o.o.';
    $company->save();

    $fiscal = app(FiscalSettings::class);
    $fiscal->api_key = 'existing-device-key';
    $fiscal->save();

    $mail = app(MailSettings::class);
    $mail->host = 'mail.example.test';
    $mail->save();

    $seeded = app(TemporaryDemoBuildSettings::class)->seedIfPristine();

    expect($seeded)->toBeFalse()
        ->and(app(CompanySettings::class)->name)->toBe('Moja firma d.o.o.')
        ->and(app(FiscalSettings::class)->api_key)->toBe('existing-device-key')
        ->and(app(MailSettings::class)->host)->toBe('mail.example.test');
});
