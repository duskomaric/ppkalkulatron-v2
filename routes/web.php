<?php

use App\Http\Controllers\ArticleController;
use App\Http\Controllers\ClientController;
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

    Route::resource('racuni', InvoiceController::class)->parameters(['racuni' => 'invoice'])->names('invoices');
    Route::resource('klijenti', ClientController::class)->parameters(['klijenti' => 'client'])->names('clients')->except('show');
    Route::resource('artikli', ArticleController::class)->parameters(['artikli' => 'article'])->names('articles')->except('show');

    Route::view('/pomoc', 'help')->name('help');

    Route::get('/podesavanja/pin', [PinSettingsController::class, 'edit'])->name('settings.pin.edit');
    Route::put('/podesavanja/pin', [PinSettingsController::class, 'update'])->name('settings.pin.update');
    Route::delete('/podesavanja/pin', [PinSettingsController::class, 'destroy'])->name('settings.pin.destroy');
});
