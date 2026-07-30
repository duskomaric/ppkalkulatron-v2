<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * Spike: dokazuje da PHP na uređaju može pozvati HTTP kasu na lokalnoj mreži —
 * ono što preglednik iz HTTPS stranice ne smije. Briše se kad se dokaže.
 */
Route::get('/spike', fn () => view('spike', ['baseUrl' => config('ofs.base_url')]))->name('spike');

Route::post('/spike/attention', function (\Illuminate\Http\Request $request) {
    $service = new \App\Services\OFSService(
        baseUrl: $request->string('base_url')->toString() ?: null,
        apiKey: $request->string('api_key')->toString() ?: null,
    );

    try {
        $response = $service->testAttention();
        $result = [
            'ok' => $response->successful(),
            'text' => 'HTTP '.$response->status()."\n\n".$response->body(),
        ];
    } catch (\Throwable $e) {
        $result = ['ok' => false, 'text' => get_class($e)."\n\n".$e->getMessage()];
    }

    return view('spike', [
        'baseUrl' => $request->string('base_url')->toString(),
        'result' => $result,
    ]);
})->name('spike.attention');
