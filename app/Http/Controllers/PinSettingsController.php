<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAutoLockSettingsRequest;
use App\Http\Requests\UpdatePinRequest;
use App\Services\PinLock;
use App\Settings\SecuritySettings;
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
        return view('settings.pin', [
            'enabled' => $this->pin->isEnabled(),
            'autoLockMinutes' => $this->pin->autoLockMinutes(),
        ]);
    }

    public function updateLock(UpdateAutoLockSettingsRequest $request, SecuritySettings $settings)
    {
        $data = $request->validated();

        $settings->auto_lock_minutes = $data['auto_lock_minutes'];
        $settings->save();

        return redirect()->route('settings.pin.edit')->with('status', 'Podešavanje je sačuvano.');
    }

    public function update(UpdatePinRequest $request)
    {
        $validated = $request->validated();

        $enabled = $this->pin->isEnabled();
        $this->pin->set($validated['pin']);
        $this->pin->markUnlocked();

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
