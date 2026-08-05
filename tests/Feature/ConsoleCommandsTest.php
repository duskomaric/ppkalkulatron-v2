<?php

use Illuminate\Support\Facades\Http;

it('dijagnostička OFS komanda prikaže status, PIN i poreske oznake uređaja', function (): void {
    Http::fake([
        'http://esir.test/api/attention' => Http::response('spreman', 200),
        'http://esir.test/api/status' => Http::response([
            'uid' => 'esir-123',
            'gsc' => ['1500'],
            'currentTaxRates' => ['taxCategories' => [[
                'taxRates' => [['label' => 'F', 'rate' => 11]],
            ]]],
        ], 200),
    ]);

    $this->artisan('ofs:ping', ['--url' => 'http://esir.test'])
        ->expectsOutputToContain('Uređaj: http://esir.test')
        ->expectsOutputToContain('attention → HTTP 200')
        ->expectsOutputToContain('status → HTTP 200')
        ->expectsOutputToContain('uid: esir-123')
        ->expectsOutputToContain('Uređaj traži PIN sigurnosnog elementa.')
        ->expectsOutputToContain('poreske oznake: F = 11%')
        ->assertExitCode(0);
});

it('dijagnostička OFS komanda ne skriva nedostupan attention endpoint', function (): void {
    Http::fake(['http://esir.test/api/attention' => Http::response('nije dostupan', 503)]);

    $this->artisan('ofs:ping', ['--url' => 'http://esir.test'])
        ->expectsOutputToContain('attention → HTTP 503')
        ->assertExitCode(1);
});

it('dijagnostička OFS komanda prijavi prekid veze sa uređajem', function (): void {
    Http::fake(['http://esir.test/api/attention' => Http::failedConnection('Nema veze')]);

    $this->artisan('ofs:ping', ['--url' => 'http://esir.test'])
        ->expectsOutputToContain('Uređaj nije dostupan: Fiskalna kasa nije dostupna.')
        ->assertExitCode(1);
});

it('dijagnostička OFS komanda jasno pokaže kada status endpoint ne odgovara', function (): void {
    Http::fake([
        'http://esir.test/api/attention' => Http::response('', 200),
        'http://esir.test/api/status' => Http::response('', 503),
    ]);

    $this->artisan('ofs:ping', ['--url' => 'http://esir.test'])
        ->expectsOutputToContain('status → HTTP 503')
        ->assertExitCode(0);
});

it('drži ikonu, favicon i splash ekrane u veličinama koje NativePHP i pregledač traže', function (): void {
    // Same crtanje pokriva BrandTest; ovdje se čuvaju datoteke koje idu u build.
    expect(getimagesize(public_path('icon.png')))->toMatchArray([0 => 1024, 1 => 1024])
        ->and(getimagesize(public_path('apple-touch-icon.png')))->toMatchArray([0 => 180, 1 => 180])
        ->and(getimagesize(public_path('splash.png')))->toMatchArray([0 => 1080, 1 => 1920])
        ->and(getimagesize(public_path('splash-dark.png')))->toMatchArray([0 => 1080, 1 => 1920])
        ->and(filesize(public_path('favicon.ico')))->toBeGreaterThan(0);
});
