<?php

namespace App\Http\Controllers;

use App\Services\PinLock;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Podešavanje PIN-a. Prvi put ga nema, pa se postavlja bez ičega; mijenjanje i
 * uklanjanje traže trenutni PIN.
 */
class PinSettingsController extends Controller
{
    /** Dužina koju prihvatamo — dovoljno za tastaturu na telefonu, a ne prekratko. */
    private const RULES = ['required', 'string', 'digits_between:4,8', 'confirmed'];

    public function __construct(private PinLock $pin) {}

    public function edit()
    {
        return view('settings.pin', ['enabled' => $this->pin->isEnabled()]);
    }

    public function update(Request $request)
    {
        $enabled = $this->pin->isEnabled();

        $request->validate([
            'pin' => self::RULES,
            'current_pin' => $enabled ? ['required', 'string'] : ['nullable'],
        ], [], [
            'pin' => 'novi PIN',
            'current_pin' => 'trenutni PIN',
        ]);

        $this->assertCurrentPin($request, $enabled);

        $this->pin->set($request->string('pin')->toString());

        // Onaj koji je upravo postavio PIN je time i otključan.
        $request->session()->put(PinLock::SESSION_KEY, now()->toIso8601String());

        return redirect()
            ->route('settings.pin.edit')
            ->with('status', $enabled ? 'PIN je promijenjen.' : 'PIN je postavljen. Tražit će se pri svakom pokretanju.');
    }

    public function destroy(Request $request)
    {
        if (! $this->pin->isEnabled()) {
            return redirect()->route('settings.pin.edit');
        }

        $request->validate(['current_pin' => ['required', 'string']], [], ['current_pin' => 'trenutni PIN']);

        $this->assertCurrentPin($request, true);

        $this->pin->disable();
        $request->session()->forget(PinLock::SESSION_KEY);

        return redirect()
            ->route('settings.pin.edit')
            ->with('status', 'PIN je uklonjen. Aplikacija se više ne zaključava.');
    }

    private function assertCurrentPin(Request $request, bool $enabled): void
    {
        if (! $enabled) {
            return;
        }

        if ($seconds = $this->pin->secondsUntilUnlock()) {
            throw ValidationException::withMessages([
                'current_pin' => "Previše pogrešnih pokušaja. Pokušajte za {$seconds} s.",
            ]);
        }

        if (! $this->pin->verify($request->string('current_pin')->toString())) {
            throw ValidationException::withMessages(['current_pin' => 'Trenutni PIN nije ispravan.']);
        }
    }
}
