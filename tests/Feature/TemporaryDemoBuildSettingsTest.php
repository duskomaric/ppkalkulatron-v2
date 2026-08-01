<?php

use App\Services\TemporaryDemoBuildSettings;
use App\Settings\BackupSettings;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\FiscalSettings;
use App\Settings\MailSettings;
use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Hash;

test('it seeds the temporary demo profile on pristine settings', function (): void {
    $seeded = app(TemporaryDemoBuildSettings::class)->seedIfPristine();
    $backup = app(BackupSettings::class);
    $fiscal = app(FiscalSettings::class);
    $mail = app(MailSettings::class);
    $security = app(SecuritySettings::class);

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
        ->and($mail->password)->not->toBeEmpty()
        ->and($backup)
        ->email->toBe('duskomaric86@gmail.com')
        ->last_backup_at->toBeNull()
        ->and(Hash::check('1111', $security->pin_hash))->toBeTrue()
        ->and($security->auto_lock_minutes)->toBe(5);
});

test('it never overwrites existing configuration', function (): void {
    $company = app(CompanySettings::class);
    $company->name = 'Moja firma d.o.o.';
    $company->save();

    $fiscal = app(FiscalSettings::class);
    $fiscal->api_key = 'existing-device-key';
    $fiscal->save();

    $mail = app(MailSettings::class);
    $mail->host = 'mail.example.test';
    $mail->save();

    $backup = app(BackupSettings::class);
    $backup->email = 'existing-backup@example.test';
    $backup->save();

    $security = app(SecuritySettings::class);
    $security->pin_hash = Hash::make('9999');
    $security->save();

    $seeded = app(TemporaryDemoBuildSettings::class)->seedIfPristine();

    expect($seeded)->toBeFalse()
        ->and(app(CompanySettings::class)->name)->toBe('Moja firma d.o.o.')
        ->and(app(FiscalSettings::class)->api_key)->toBe('existing-device-key')
        ->and(app(MailSettings::class)->host)->toBe('mail.example.test')
        ->and(app(BackupSettings::class)->email)->toBe('existing-backup@example.test')
        ->and(Hash::check('9999', app(SecuritySettings::class)->pin_hash))->toBeTrue();
});

test('it does not seed over an existing backup or PIN configuration', function (): void {
    $backup = app(BackupSettings::class);
    $backup->email = 'existing-backup@example.test';
    $backup->save();

    $security = app(SecuritySettings::class);
    $security->pin_hash = Hash::make('9999');
    $security->save();

    $seeded = app(TemporaryDemoBuildSettings::class)->seedIfPristine();

    expect($seeded)->toBeFalse()
        ->and(app(CompanySettings::class)->name)->toBe('')
        ->and(app(BackupSettings::class)->email)->toBe('existing-backup@example.test')
        ->and(Hash::check('9999', app(SecuritySettings::class)->pin_hash))->toBeTrue();
});
