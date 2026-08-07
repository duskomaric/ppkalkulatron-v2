<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BackgroundChecksController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\DiagnosticsController;
use App\Http\Controllers\DocumentTemplatePreviewController;
use App\Http\Controllers\FiscalController;
use App\Http\Controllers\FiscalImportController;
use App\Http\Controllers\FiscalSettingsController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\MenuSettingsController;
use App\Http\Controllers\MobileDiagnosticsController;
use App\Http\Controllers\PinSettingsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UnlockController;
use App\Http\Middleware\EnsureUnlocked;
use App\Http\Middleware\LogDiagnosticAction;
use Illuminate\Support\Facades\Route;

// Ekran za otključavanje mora biti dostupan i zaključanoj aplikaciji.
Route::get('/otkljucaj', [UnlockController::class, 'show'])->name('unlock');
Route::post('/otkljucaj', [UnlockController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('unlock.store');

Route::middleware([EnsureUnlocked::class, LogDiagnosticAction::class])->group(function (): void {
    Route::redirect('/', '/racuni');

    Route::post('/zakljucaj', [UnlockController::class, 'destroy'])->name('unlock.destroy');
    Route::post('/dijagnostika/mobilna', [MobileDiagnosticsController::class, 'store'])->name('mobile.diagnostics.store');

    Route::get('/racuni/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('/racuni/{invoice}/posalji', [InvoiceController::class, 'email'])->name('invoices.email');
    Route::post('/racuni/{invoice}/fiskalizuj', [FiscalController::class, 'fiscalize'])->name('invoices.fiscalize');
    Route::post('/racuni/{invoice}/fiskalna-kopija', [FiscalController::class, 'copy'])->name('invoices.fiscal-copy');
    Route::post('/racuni/{invoice}/storno', [FiscalController::class, 'createRefund'])->name('invoices.create-refund');
    Route::post('/racuni/{invoice}/fiskalni-storno', [FiscalController::class, 'refund'])->name('invoices.fiscal-refund');
    Route::get('/fiskalni-racun/{record}', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
    Route::resource('racuni', InvoiceController::class)
        ->parameters(['racuni' => 'invoice'])
        ->names('invoices');
    Route::resource('klijenti', ClientController::class)->parameters(['klijenti' => 'client'])->names('clients')->except('show');
    Route::resource('artikli', ArticleController::class)
        ->parameters(['artikli' => 'article'])
        ->names('articles')
        ->except('show');

    Route::resource('bankovni-racuni', BankAccountController::class)->parameters(['bankovni-racuni' => 'bankAccount'])->names('bank-accounts')->except('show');
    Route::post('/valute/{currency}/kurs', [CurrencyController::class, 'storeRate'])->name('currencies.rates.store');
    Route::post('/valute/kursna-lista', [CurrencyController::class, 'fetchRates'])->name('currencies.rates.fetch');
    Route::resource('valute', CurrencyController::class)->parameters(['valute' => 'currency'])->names('currencies')->except('show');

    Route::get('/profil', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profil', [ProfileController::class, 'update'])->name('profile.update');

    Route::view('/pomoc', 'help')->name('help');

    Route::get('/podesavanja/kompanija', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
    Route::put('/podesavanja/kompanija', [CompanySettingsController::class, 'update'])->name('settings.company.update');
    Route::post('/podesavanja/kompanija/sa-kase', [CompanySettingsController::class, 'importFromDevice'])->name('settings.company.import');

    Route::get('/podesavanja/generalno', [GeneralSettingsController::class, 'edit'])->name('settings.general.edit');
    Route::put('/podesavanja/generalno', [GeneralSettingsController::class, 'update'])->name('settings.general.update');
    Route::get('/podesavanja/predlosci/{template}/pregled', DocumentTemplatePreviewController::class)->name('settings.templates.preview');

    Route::get('/podesavanja/mail', [MailSettingsController::class, 'edit'])->name('settings.mail.edit');
    Route::put('/podesavanja/mail', [MailSettingsController::class, 'update'])->name('settings.mail.update');

    Route::get('/podesavanja/pocetak', [SetupController::class, 'edit'])->name('settings.setup.edit');
    Route::post('/podesavanja/pocetak/vrati', [SetupController::class, 'restore'])->name('setup.restore');
    Route::post('/pocetak/sakrij', [SetupController::class, 'dismiss'])->name('setup.dismiss');

    Route::get('/podesavanja/arhiva', [BackupController::class, 'edit'])->name('settings.backup.edit');
    Route::put('/podesavanja/arhiva', [BackupController::class, 'update'])->name('settings.backup.update');
    Route::post('/podesavanja/arhiva/posalji', [BackupController::class, 'send'])->name('settings.backup.send');
    Route::get('/podesavanja/backup-aplikacije', [DatabaseBackupController::class, 'edit'])->name('settings.database.edit');
    Route::get('/podesavanja/backup-aplikacije/preuzmi', [DatabaseBackupController::class, 'download'])->name('settings.database.download');
    Route::post('/podesavanja/backup-aplikacije/vrati', [DatabaseBackupController::class, 'restore'])->name('settings.database.restore');
    Route::post('/podesavanja/backup-aplikacije/reset', [DatabaseBackupController::class, 'reset'])->name('settings.database.reset');

    Route::get('/podesavanja/dijagnostika', [DiagnosticsController::class, 'edit'])->name('settings.diagnostics.edit');
    Route::put('/podesavanja/dijagnostika', [DiagnosticsController::class, 'update'])->name('settings.diagnostics.update');
    Route::post('/podesavanja/dijagnostika/posalji', [DiagnosticsController::class, 'send'])->name('settings.diagnostics.send');

    Route::get('/podesavanja/fiskalizacija', [FiscalSettingsController::class, 'edit'])->name('settings.fiscal.edit');
    Route::get('/provjere', BackgroundChecksController::class)->name('checks');
    Route::put('/podesavanja/fiskalizacija', [FiscalSettingsController::class, 'update'])->name('settings.fiscal.update');
    Route::post('/podesavanja/fiskalizacija/provjera', [FiscalSettingsController::class, 'test'])->name('settings.fiscal.test');
    Route::post('/podesavanja/fiskalizacija/stope', [FiscalSettingsController::class, 'syncTaxRates'])->name('settings.fiscal.tax-rates.sync');
    Route::post('/podesavanja/fiskalizacija/skeniraj', [FiscalSettingsController::class, 'scan'])->name('settings.fiscal.scan');
    Route::post('/podesavanja/fiskalizacija/pin', [FiscalSettingsController::class, 'pin'])->name('settings.fiscal.pin');
    Route::post('/podesavanja/fiskalizacija/zahtjev', [FiscalSettingsController::class, 'findRequest'])->name('settings.fiscal.find-request');
    Route::post('/podesavanja/fiskalizacija/uvoz/pretraga', [FiscalImportController::class, 'search'])->name('settings.fiscal.import.search');
    Route::post('/podesavanja/fiskalizacija/uvoz', [FiscalImportController::class, 'store'])->name('settings.fiscal.import.store');

    Route::get('/podesavanja/meni', [MenuSettingsController::class, 'edit'])->name('settings.menu.edit');
    Route::put('/podesavanja/meni', [MenuSettingsController::class, 'update'])->name('settings.menu.update');

    Route::get('/podesavanja/pin', [PinSettingsController::class, 'edit'])->name('settings.pin.edit');
    Route::put('/podesavanja/pin', [PinSettingsController::class, 'update'])->name('settings.pin.update');
    Route::put('/podesavanja/pin/zakljucavanje', [PinSettingsController::class, 'updateLock'])->name('settings.pin.update-lock');
    Route::delete('/podesavanja/pin', [PinSettingsController::class, 'destroy'])->name('settings.pin.destroy');
});
