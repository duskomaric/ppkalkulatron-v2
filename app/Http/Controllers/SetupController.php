<?php

namespace App\Http\Controllers;

use App\Services\SetupProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Vodič kroz početno podešavanje. */
class SetupController extends Controller
{
    public function edit(SetupProgress $setup): View
    {
        return view('settings.setup', ['setup' => $setup]);
    }

    public function dismiss(SetupProgress $setup): RedirectResponse
    {
        $setup->dismiss();

        return redirect()->route('invoices.index')
            ->with('status', 'Vodič je sklonjen. Stoji u Podešavanja → Početno podešavanje.');
    }

    public function restore(SetupProgress $setup): RedirectResponse
    {
        $setup->restore();

        return redirect()->route('settings.setup.edit')->with('status', 'Vodič je ponovo na računima.');
    }
}
