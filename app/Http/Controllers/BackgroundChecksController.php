<?php

namespace App\Http\Controllers;

use App\Services\BackgroundChecks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/** Jedan zahtjev za sve pozadinske provjere koje stranica pokreće. */
class BackgroundChecksController extends Controller
{
    public function __invoke(Request $request, BackgroundChecks $checks): JsonResponse|RedirectResponse
    {
        $result = $checks->refresh();

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        return redirect()->route('invoices.index');
    }
}
