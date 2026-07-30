<?php

namespace App\Http\Controllers;

use App\Services\PinLock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UnlockController extends Controller
{
    public function __construct(private PinLock $pin) {}

    public function show(Request $request)
    {
        // Bez PIN-a nema šta otključavati, a otključan korisnik ne treba ekran.
        if (! $this->pin->isEnabled() || $request->session()->has(PinLock::SESSION_KEY)) {
            return redirect()->route('home');
        }

        return view('unlock', [
            'lockedForSeconds' => $this->pin->secondsUntilUnlock(),
            'attemptsLeft' => $this->pin->attemptsLeft(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $this->pin->isEnabled()) {
            return redirect()->route('home');
        }

        if ($seconds = $this->pin->secondsUntilUnlock()) {
            throw ValidationException::withMessages([
                'pin' => "Previše pogrešnih pokušaja. Pokušajte za {$seconds} s.",
            ]);
        }

        $validated = $request->validate(['pin' => ['required', 'string']]);

        if (! $this->pin->verify($validated['pin'])) {
            Log::warning('Neuspjelo otključavanje PIN-om', ['ip' => $request->ip()]);

            throw ValidationException::withMessages([
                'pin' => $this->pin->isLockedOut()
                    ? 'Previše pogrešnih pokušaja. Aplikacija je zaključana na '.PinLock::LOCKOUT_SECONDS.' s.'
                    : 'Pogrešan PIN. Preostalo pokušaja: '.$this->pin->attemptsLeft().'.',
            ]);
        }

        // Novi session id nakon otključavanja — stara sesija ne smije ostati upotrebljiva.
        $request->session()->regenerate();
        $request->session()->put(PinLock::SESSION_KEY, now()->toIso8601String());

        return redirect()->intended(route('home'));
    }

    public function destroy(Request $request)
    {
        $request->session()->forget(PinLock::SESSION_KEY);

        return redirect()->route('unlock');
    }
}
