<?php

use App\Mail\DiagnosticsMail;
use App\Services\Diagnostics;
use App\Services\DiagnosticsArchive;
use App\Settings\DiagnosticsSettings;
use Illuminate\Support\Facades\Mail;

it('jasno objašnjava sigurnost dijagnostike', function () {
    $this->get(route('settings.diagnostics.edit'))
        ->assertSuccessful()
        ->assertSee('Računi i privatni podaci se ne šalju.')
        ->assertSee('API ključ, PAK, PIN ni SMTP lozinku');
});

it('uključuje detaljnu dijagnostiku najviše 24 sata', function () {
    $this->put(route('settings.diagnostics.update'), [
        'email' => 'podrska@example.com',
        'detailed_logging' => '1',
    ])->assertRedirect(route('settings.diagnostics.edit'));

    $settings = app(DiagnosticsSettings::class);

    expect($settings->email)->toBe('podrska@example.com')
        ->and($settings->detailedLoggingEnabled())->toBeTrue()
        ->and($settings->detailed_until)->toBeBetween(now()->addHours(23), now()->addHours(25));
});

it('sanitizuje tajne prije upisa u dijagnostički log', function () {
    $context = app(Diagnostics::class)->sanitize([
        'api_key' => 'ne-smije-izaći',
        'document_contents' => 'binarni-racun',
        'status' => 500,
    ]);

    expect($context)->toBe([
        'api_key' => '[redacted]',
        'document_contents' => '[redacted]',
        'status' => 500,
    ]);
});

it('šalje samo tekstualni dijagnostički prilog na podešenu adresu', function () {
    Mail::fake();
    $settings = app(DiagnosticsSettings::class);
    $settings->email = 'podrska@example.com';
    $settings->save();

    $this->post(route('settings.diagnostics.send'))
        ->assertRedirect(route('settings.diagnostics.edit'))
        ->assertSessionHas('status', 'Sigurni dijagnostički izvještaj je poslat.');

    Mail::assertSent(DiagnosticsMail::class, function (DiagnosticsMail $mail): bool {
        return $mail->hasTo('podrska@example.com')
            && str_ends_with($mail->reportName, '.log')
            && $mail->attachments()[0]->as === $mail->reportName;
    });

    expect(app(DiagnosticsSettings::class)->last_sent_at)->not->toBeNull();
});

it('ne šalje dijagnostiku bez odredišnog emaila', function () {
    $this->post(route('settings.diagnostics.send'))
        ->assertRedirect(route('settings.diagnostics.edit'))
        ->assertSessionHas('error', 'Prvo unesite email za dijagnostiku.');
});

it('gradi izvještaj bez dokumenata i tajni', function () {
    $archive = app(DiagnosticsArchive::class)->create();
    $contents = file_get_contents($archive['path']);

    expect($contents)
        ->toContain('ne sadrži račune')
        ->not->toContain('ne-smije-izaći');

    @unlink($archive['path']);
});
