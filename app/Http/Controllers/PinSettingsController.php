<?php

namespace App\Http\Controllers;

use App\Services\PinLock;
use Illuminate\Http\Request;

/**
 * Podešavanje PIN-a. Do ovog ekrana se dolazi iz otključane aplikacije, pa se
 * trenutni PIN ne traži ponovo.
 */
class PinSettingsController extends Controller
{
    public function __construct(private PinLock $pin) {}

    public function edit()
    {
        return view('settings.pin', ['enabled' => $this->pin->isEnabled()]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate(
            ['pin' => ['required', 'digits:4', 'confirmed']],
            [],
            ['pin' => 'PIN'],
        );

        $enabled = $this->pin->isEnabled();
        $this->pin->set($validated['pin']);
        $request->session()->put(PinLock::SESSION_KEY, true);

        return redirect()
            ->route('settings.pin.edit')
            ->with('status', $enabled ? 'PIN je promijenjen.' : 'PIN je postavljen.');
    }

    public function destroy(Request $request)
    {
        $this->pin->disable();
        $request->session()->forget(PinLock::SESSION_KEY);

        return redirect()->route('settings.pin.edit')->with('status', 'PIN je uklonjen.');
    }
}
