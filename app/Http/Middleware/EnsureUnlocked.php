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
 */
class EnsureUnlocked
{
    public function __construct(private PinLock $pin) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->pin->isEnabled()) {
            return $next($request);
        }

        if ($request->session()->has(PinLock::SESSION_KEY)) {
            return $next($request);
        }

        return redirect()->route('unlock');
    }
}
