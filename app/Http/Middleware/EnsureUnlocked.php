<?php

namespace App\Http\Middleware;

use App\Services\PinLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pusti dalje ako PIN nije podešen, inače traži otključavanje.
 *
 * Sesija nosi otključanost, pa je novo pokretanje aplikacije opet zaključano.
 * Uz to se pamti vrijeme posljednjeg zahtjeva: kad prođe podešeni broj minuta
 * bez aktivnosti — telefon zaključan, aplikacija u pozadini, ekran ostavljen —
 * sljedeći zahtjev ponovo traži PIN.
 */
class EnsureUnlocked
{
    public function __construct(private PinLock $pin) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->pin->isEnabled()) {
            return $next($request);
        }

        if (! $request->session()->has(PinLock::SESSION_KEY)) {
            return redirect()->route('unlock');
        }

        $minutes = $this->pin->autoLockMinutes();
        $lastSeen = $request->session()->get(PinLock::SEEN_KEY);

        if ($minutes > 0 && $lastSeen && now()->diffInMinutes($lastSeen, true) >= $minutes) {
            $request->session()->forget([PinLock::SESSION_KEY, PinLock::SEEN_KEY]);

            return redirect()->route('unlock')->with('error', 'Aplikacija je zaključana zbog neaktivnosti.');
        }

        $request->session()->put(PinLock::SEEN_KEY, now());

        return $next($request);
    }
}
