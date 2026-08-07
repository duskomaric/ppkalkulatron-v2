<?php

namespace App\Http\Controllers;

use App\Services\SetupProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;

/** Vodič kroz početno podešavanje; prikazuje se kao panel, pa ovdje stoje samo radnje. */
class SetupController extends Controller
{
    public function dismiss(SetupProgress $setup): RedirectResponse
    {
        $setup->dismiss();

        return back()->with('status', 'Vodič se više neće otvarati sam. Stoji u Podešavanja → Početno podešavanje.');
    }

    public function restore(SetupProgress $setup): RedirectResponse
    {
        $setup->restore();

        return back()->with('status', 'Vodič će se ponovo otvarati dok podešavanje ne bude gotovo.');
    }

    /**
     * PRIVREMENO: popunjavanje aplikacije podacima za interno testiranje.
     *
     * Podaci stoje u komandi `app:demo-data`, na jednom mjestu; ranije su bili
     * razasuti po seederima i settings migraciji, pa su se vraćali i poslije reseta.
     */
    public function seedDemo(): RedirectResponse
    {
        $seeded = Artisan::call('app:demo-data') === 0;

        return back()->with(
            $seeded ? 'status' : 'error',
            $seeded
                ? 'Demo podaci su upisani: testna kasa sa stopama, firma iz Banje Luke, banka, klijenti i artikli.'
                : 'Demo podaci se upisuju samo u praznu aplikaciju. Prvo uradite reset.',
        );
    }
}
