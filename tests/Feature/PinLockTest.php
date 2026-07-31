<?php

use App\Services\PinLock;
use App\Settings\SecuritySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PIN je opcionalan: prvi put nije podešen i ulazi se direktno u račune.
 * Kad se podesi, traži se pri pokretanju. Ništa više od toga.
 */
function setPin(string $pin = '1111'): void
{
    app(PinLock::class)->set($pin);
}

it('pušta u račune kad PIN nije podešen', function () {
    $this->get('/')->assertRedirect(route('invoices.index'));
    $this->get(route('invoices.index'))->assertStatus(200);
});

it('traži PIN kad je podešen', function () {
    setPin();

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('otključava ispravnim PIN-om', function () {
    setPin('4321');

    $this->post(route('unlock.store'), ['pin' => '4321'])->assertRedirect(route('invoices.index'));
    $this->get(route('invoices.index'))->assertStatus(200);
});

it('ne otključava pogrešnim PIN-om', function () {
    setPin('1111');

    $this->post(route('unlock.store'), ['pin' => '9999'])->assertSessionHasErrors('pin');
    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('čuva PIN kao hash, nikad kao tekst', function () {
    setPin('1111');

    expect(app(SecuritySettings::class)->pin_hash)->toStartWith('$2y$')
        ->and(DB::table('settings')->pluck('payload')->implode('|'))->not->toContain('1111');
});

it('postavlja PIN i ostavlja korisnika otključanim', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1111', 'pin_confirmation' => '1111'])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(PinLock::class)->isEnabled())->toBeTrue();
    $this->get(route('invoices.index'))->assertStatus(200);
});

it('traži potvrdu PIN-a', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1111', 'pin_confirmation' => '2222'])
        ->assertSessionHasErrors('pin');

    expect(app(PinLock::class)->isEnabled())->toBeFalse();
});

it('prihvata samo četiri cifre', function (string $candidate) {
    $this->put(route('settings.pin.update'), ['pin' => $candidate, 'pin_confirmation' => $candidate])
        ->assertSessionHasErrors('pin');
})->with(['123', '12345', 'abcd', '12a4', '']);

it('uklanja PIN i pušta bez zaključavanja', function () {
    setPin('1111');
    $this->withSession([PinLock::SESSION_KEY => true]);

    $this->delete(route('settings.pin.destroy'))->assertRedirect(route('settings.pin.edit'));

    expect(app(PinLock::class)->isEnabled())->toBeFalse();
    $this->get(route('invoices.index'))->assertStatus(200);
});

it('drži podešavanja iza zaključavanja', function () {
    setPin('1111');

    $this->get(route('settings.pin.edit'))->assertRedirect(route('unlock'));
});

it('zaključava na zahtjev', function () {
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);

    $this->post(route('unlock.destroy'))->assertRedirect(route('unlock'));
    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('zaključava poslije neaktivnosti', function () {
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);
    $this->get(route('invoices.index'))->assertStatus(200);

    $this->travel(6)->minutes();

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('ne zaključava dok se koristi', function () {
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);

    foreach (range(1, 3) as $ignored) {
        $this->travel(4)->minutes();
        $this->get(route('invoices.index'))->assertStatus(200);
    }
});

it('ne zaključava kad je automatsko zaključavanje isključeno', function () {
    setPin('1111');
    $settings = app(SecuritySettings::class);
    $settings->auto_lock_minutes = 0;
    $settings->save();

    $this->post(route('unlock.store'), ['pin' => '1111']);
    $this->travel(3)->hours();

    $this->get(route('invoices.index'))->assertStatus(200);
});

it('mijenja vrijeme automatskog zaključavanja', function () {
    setPin('1111');
    $this->withSession([PinLock::SESSION_KEY => true]);

    $this->put(route('settings.pin.update-lock'), ['auto_lock_minutes' => 30])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(SecuritySettings::class)->auto_lock_minutes)->toBe(30);
});

it('ostaje na ekranu za otključavanje kad je PIN pogrešan', function () {
    setPin('1111');

    // Bez eksplicitnog preusmjerenja Laravel bi vratio na „prethodni URL", a to je
    // u webviewu znala biti POST-only ruta — otud 405 na telefonu.
    $this->post(route('unlock.store'), ['pin' => '9999'])
        ->assertRedirect(route('unlock'))
        ->assertSessionHasErrors('pin');
});

it('vraća na početak umjesto 405 kad se GET-om pogodi POST ruta', function () {
    $this->get('/lock')->assertRedirect(route('invoices.index'));
    $this->get('/podesavanja/fiskalizacija/provjera')->assertRedirect(route('invoices.index'));
});
