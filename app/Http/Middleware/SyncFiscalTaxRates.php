<?php

namespace App\Http\Middleware;

use App\Services\FiscalTaxRateSynchronizer;
use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class SyncFiscalTaxRates
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function __construct(private FiscalTaxRateSynchronizer $taxRates) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Feature testovi zasebno lažiraju OFS tok; njihov katalog je testni
        // podatak, nikad fallback produkcijske aplikacije.
        if (app()->environment('testing')) {
            return $next($request);
        }

        try {
            $this->taxRates->syncFromDevice();
        } catch (RuntimeException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return redirect()->back()->withInput()->with('error', $exception->getMessage());
        }

        return $next($request);
    }
}
