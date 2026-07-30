<?php

use App\Http\Controllers\PinSettingsController;
use App\Http\Controllers\UnlockController;
use App\Http\Middleware\EnsureUnlocked;
use App\Services\PinLock;
use Illuminate\Support\Facades\Route;

// Ekran za otključavanje mora biti dostupan i zaključanoj aplikaciji.
Route::get('/unlock', [UnlockController::class, 'show'])->name('unlock');
Route::post('/unlock', [UnlockController::class, 'store'])->name('unlock.store');

Route::middleware(EnsureUnlocked::class)->group(function () {
    Route::get('/', fn (PinLock $pin) => view('home', ['pinEnabled' => $pin->isEnabled()]))->name('home');

    Route::post('/lock', [UnlockController::class, 'destroy'])->name('unlock.destroy');

    Route::get('/settings/pin', [PinSettingsController::class, 'edit'])->name('settings.pin.edit');
    Route::put('/settings/pin', [PinSettingsController::class, 'update'])->name('settings.pin.update');
    Route::delete('/settings/pin', [PinSettingsController::class, 'destroy'])->name('settings.pin.destroy');
});
