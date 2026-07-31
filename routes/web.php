<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanySettingsController;
use App\Http\Controllers\FiscalSettingsController;
use App\Http\Controllers\GeneralSettingsController;
use App\Http\Controllers\MailSettingsController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PinSettingsController;
use App\Http\Controllers\UnlockController;
use App\Http\Middleware\EnsureUnlocked;
use Illuminate\Support\Facades\Route;

// Ekran za otključavanje mora biti dostupan i zaključanoj aplikaciji.
Route::get('/unlock', [UnlockController::class, 'show'])->name('unlock');
Route::post('/unlock', [UnlockController::class, 'store'])->name('unlock.store');

Route::middleware(EnsureUnlocked::class)->group(function () {
    Route::redirect('/', '/racuni');

    Route::post('/lock', [UnlockController::class, 'destroy'])->name('unlock.destroy');

    Route::get('/racuni/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
    Route::post('/racuni/{invoice}/mail', [InvoiceController::class, 'email'])->name('invoices.email');
    Route::get('/fiskalni-racun/{record}', [InvoiceController::class, 'receipt'])->name('invoices.receipt');
    Route::resource('racuni', InvoiceController::class)->parameters(['racuni' => 'invoice'])->names('invoices');
    Route::resource('klijenti', ClientController::class)->parameters(['klijenti' => 'client'])->names('clients')->except('show');
    Route::resource('artikli', ArticleController::class)->parameters(['artikli' => 'article'])->names('articles')->except('show');

    Route::resource('bankovni-racuni', BankAccountController::class)->parameters(['bankovni-racuni' => 'bankAccount'])->names('bank-accounts')->except('show');
    Route::resource('valute', CurrencyController::class)->parameters(['valute' => 'currency'])->names('currencies')->except('show');

    Route::view('/pomoc', 'help')->name('help');

    Route::get('/podesavanja/kompanija', [CompanySettingsController::class, 'edit'])->name('settings.company.edit');
    Route::put('/podesavanja/kompanija', [CompanySettingsController::class, 'update'])->name('settings.company.update');

    Route::get('/podesavanja/generalno', [GeneralSettingsController::class, 'edit'])->name('settings.general.edit');
    Route::put('/podesavanja/generalno', [GeneralSettingsController::class, 'update'])->name('settings.general.update');

    Route::get('/podesavanja/mail', [MailSettingsController::class, 'edit'])->name('settings.mail.edit');
    Route::put('/podesavanja/mail', [MailSettingsController::class, 'update'])->name('settings.mail.update');

    Route::get('/podesavanja/fiskalizacija', [FiscalSettingsController::class, 'edit'])->name('settings.fiscal.edit');
    Route::put('/podesavanja/fiskalizacija', [FiscalSettingsController::class, 'update'])->name('settings.fiscal.update');
    Route::post('/podesavanja/fiskalizacija/provjera', [FiscalSettingsController::class, 'test'])->name('settings.fiscal.test');

    Route::get('/podesavanja/pin', [PinSettingsController::class, 'edit'])->name('settings.pin.edit');
    Route::put('/podesavanja/pin', [PinSettingsController::class, 'update'])->name('settings.pin.update');
    Route::delete('/podesavanja/pin', [PinSettingsController::class, 'destroy'])->name('settings.pin.destroy');
});
