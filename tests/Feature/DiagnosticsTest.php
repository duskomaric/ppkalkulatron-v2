<?php

use App\Mail\DiagnosticsMail;
use App\Services\Diagnostics;
use App\Services\DiagnosticsArchive;
use App\Settings\DiagnosticsSettings;
use Illuminate\Support\Facades\Log;
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
        'error' => 'tajni-tehnički-detalj',
        'status' => 500,
    ]);

    expect($context)->toBe([
        'api_key' => '[redacted]',
        'document_contents' => '[redacted]',
        'error' => '[redacted]',
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

it('prikaže jasnu grešku kada se dijagnostički prilog ne može pripremiti', function () {
    $settings = app(DiagnosticsSettings::class);
    $settings->email = 'podrska@example.com';
    $settings->save();

    $this->mock(DiagnosticsArchive::class, function ($mock): void {
        $mock->shouldReceive('create')->andThrow(new RuntimeException('Prilog nije dostupan.'));
    });

    $this->post(route('settings.diagnostics.send'))
        ->assertRedirect(route('settings.diagnostics.edit'))
        ->assertSessionHas('error', 'Dijagnostički izvještaj nije poslat. Provjerite e-mail podešavanja i pokušajte ponovo.');
});

it('ne otkriva tehnički detalj neočekivane greške pri slanju dijagnostike', function () {
    $settings = app(DiagnosticsSettings::class);
    $settings->email = 'podrska@example.com';
    $settings->save();

    $this->mock(DiagnosticsArchive::class, function ($mock): void {
        $mock->shouldReceive('create')->andThrow(new LogicException('tajni tehnički detalj'));
    });

    $this->post(route('settings.diagnostics.send'))
        ->assertRedirect(route('settings.diagnostics.edit'))
        ->assertSessionHas('error', 'Slanje dijagnostike trenutno nije uspjelo. Pokušajte ponovo.');
});

it('renderuje dijagnostički email u zajedničkom okviru i dodaje tekstualni prilog', function () {
    $path = storage_path('app/private/dijagnostika-test-'.uniqid().'.log');
    file_put_contents($path, 'siguran test');

    $mail = new DiagnosticsMail($path, 'dijagnostika.log');

    expect($mail->render())
        ->toContain('Dijagnostički izvještaj')
        ->toContain(config('app.name').' podrška')
        ->and($mail->attachments()[0]->as)->toBe('dijagnostika.log');

    @unlink($path);
});

it('gradi izvještaj samo iz sigurnog kanala, bez dokumenata i tajni', function () {
    $safeSecret = 'sigurna-tajna-'.uniqid();
    $rawSecret = 'interna-tajna-'.uniqid();

    app(Diagnostics::class)->error('Testirana sigurna greška', [
        'api_key' => $safeSecret,
    ]);
    Log::channel('single')->error('Sirova interna greška', [
        'exception' => $rawSecret,
    ]);

    $archive = app(DiagnosticsArchive::class)->create();
    $contents = file_get_contents($archive['path']);

    expect($contents)
        ->toContain('ne sadrži račune')
        ->toContain('Testirana sigurna greška')
        ->not->toContain($safeSecret)
        ->not->toContain($rawSecret)
        ->not->toContain('stacktrace');

    @unlink($archive['path']);
});

it('bilježi samo sigurnu oznaku neprijavljene greške', function () {
    $secretMessage = 'poruka-koja-ne-smije-biti-poslana-'.uniqid();

    report(new RuntimeException($secretMessage));

    $archive = app(DiagnosticsArchive::class)->create();
    $contents = file_get_contents($archive['path']);

    expect($contents)
        ->toContain('Unhandled application exception')
        ->toContain(RuntimeException::class)
        ->not->toContain($secretMessage)
        ->not->toContain('stacktrace');

    @unlink($archive['path']);
});

it('bilježi tehnički ishod svake promjene bez sadržaja forme', function () {
    $settings = app(DiagnosticsSettings::class);
    $settings->detailed_until = now()->addDay();
    $settings->save();

    $path = storage_path('logs/support-diagnostics-'.now()->format('Y-m-d').'.log');
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['invoices'],
        'drawer_modules' => ['clients', 'articles', 'bank-accounts', 'currencies'],
        'max_menu_items' => 4,
    ])->assertRedirect(route('settings.menu.edit'));

    $contents = substr((string) file_get_contents($path), strlen($before));

    expect($contents)
        ->toContain('Application action completed')
        ->toContain('settings.menu.update')
        ->toContain('"method":"PUT"')
        ->toContain('"status":302')
        ->not->toContain('drawer_modules');
});

it('bilježi izmjenu klijenta po ruti, bez podataka klijenta', function () {
    $settings = app(DiagnosticsSettings::class);
    $settings->detailed_until = now()->addDay();
    $settings->save();

    $clientName = 'Privatni naziv '.uniqid();
    $path = storage_path('logs/support-diagnostics-'.now()->format('Y-m-d').'.log');
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    $this->post(route('clients.store'), ['name' => $clientName, 'is_active' => '1'])
        ->assertRedirect(route('clients.index'));

    $contents = substr((string) file_get_contents($path), strlen($before));

    expect($contents)
        ->toContain('clients.store')
        ->not->toContain($clientName);
});

it('bilježi neuspjelo PIN otključavanje bez PIN-a', function () {
    setPin('1111');
    $path = storage_path('logs/support-diagnostics-'.now()->format('Y-m-d').'.log');
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    $this->post(route('unlock.store'), ['pin' => '9999'])->assertSessionHasErrors('pin');

    $contents = substr((string) file_get_contents($path), strlen($before));

    expect($contents)
        ->toContain('PIN unlock failed')
        ->not->toContain('9999');
});

it('bilježi neuspjelu promjenu i kada detaljna dijagnostika nije uključena', function () {
    $path = storage_path('logs/support-diagnostics-'.now()->format('Y-m-d').'.log');
    $before = is_file($path) ? (string) file_get_contents($path) : '';

    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['nepoznat-modul'],
        'drawer_modules' => [],
        'max_menu_items' => 4,
    ])->assertSessionHasErrors('menu_modules.0');

    $contents = substr((string) file_get_contents($path), strlen($before));

    expect($contents)
        ->toContain('Application action failed')
        ->toContain('settings.menu.update')
        ->not->toContain('nepoznat-modul');
});
