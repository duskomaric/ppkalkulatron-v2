<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MobileDiagnosticsController extends Controller
{
    /** Prima samo tehničke signale iz mobilnog WebViewa, nikad sadržaj dokumenta. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event' => ['required', 'string', 'max:100'],
            'context' => ['nullable', 'array'],
        ]);

        Log::channel('mobile')->info('Mobile WebView: '.$data['event'], [
            ...($data['context'] ?? []),
            'jump' => getenv('JUMP_BRIDGE_PORT') !== false,
        ]);

        return response()->json(['logged' => true]);
    }
}
