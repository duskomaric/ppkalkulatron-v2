<?php

it('records a mobile WebView diagnostic event', function (): void {
    $this->postJson(route('mobile.diagnostics.store'), [
        'event' => 'fiscal_receipt_response',
        'context' => ['kind' => 'image', 'status' => 200],
    ])
        ->assertSuccessful()
        ->assertJson(['logged' => true]);
});

it('requires a diagnostic event name', function (): void {
    $this->postJson(route('mobile.diagnostics.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('event');
});

it('čuva grešku i bez detaljne dijagnostike, ali ne čuva običan događaj', function (): void {
    $normalEvent = 'diagnostic_test_normal_'.uniqid();
    $errorEvent = 'diagnostic_test_'.uniqid().'_failed';
    $path = storage_path('logs/support-diagnostics-'.now()->format('Y-m-d').'.log');
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    $this->postJson(route('mobile.diagnostics.store'), ['event' => $normalEvent])->assertSuccessful();
    $this->postJson(route('mobile.diagnostics.store'), ['event' => $errorEvent])->assertSuccessful();

    $after = is_file($path) ? (string) file_get_contents($path) : '';
    $newEntries = substr($after, strlen($before));

    expect($newEntries)
        ->not->toContain($normalEvent)
        ->toContain($errorEvent);
});
