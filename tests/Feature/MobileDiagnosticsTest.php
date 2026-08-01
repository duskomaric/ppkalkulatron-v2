<?php

it('records a mobile WebView diagnostic event', function () {
    $this->postJson(route('mobile.diagnostics.store'), [
        'event' => 'fiscal_receipt_response',
        'context' => ['kind' => 'image', 'status' => 200],
    ])
        ->assertSuccessful()
        ->assertJson(['logged' => true]);
});

it('requires a diagnostic event name', function () {
    $this->postJson(route('mobile.diagnostics.store'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('event');
});
