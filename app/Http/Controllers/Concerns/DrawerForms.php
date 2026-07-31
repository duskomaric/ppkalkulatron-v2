<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * Šifarnici otvaraju formu u draweru: lista traži samo tijelo forme, a čuvanje
 * ide preko XHR-a. Puna stranica i dalje radi, za direktan link i bez JavaScripta.
 */
trait DrawerForms
{
    protected function formView(Request $request, string $partial, string $page, array $data)
    {
        return $request->boolean('partial') ? view($partial, $data) : view($page, $data);
    }

    protected function saved(Request $request, string $route, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route($route)->with('status', $message);
    }
}
