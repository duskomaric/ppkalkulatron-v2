<?php

namespace App\Http\Controllers;

use App\Services\Diagnostics;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileDiagnosticsController extends Controller
{
    /** Prima samo tehničke signale iz mobilnog WebViewa, nikad sadržaj dokumenta. */
    public function store(Request $request, Diagnostics $diagnostics): JsonResponse
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'context' => ['nullable', 'array'],
        ]);

        $context = [
            ...($data['context'] ?? []),
            'jump' => getenv('JUMP_BRIDGE_PORT') !== false,
        ];

        if (str_ends_with($data['event'], '_failed') || str_ends_with($data['event'], '_error')) {
            $diagnostics->error('Mobile WebView: '.$data['event'], $context);
        } else {
            $diagnostics->debug('Mobile WebView: '.$data['event'], $context);
        }

        return response()->json(['logged' => true]);
    }
}
