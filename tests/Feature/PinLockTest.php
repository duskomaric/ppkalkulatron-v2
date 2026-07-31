<?php

use App\Settings\SecuritySettings;
use App\Services\PinLock;
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
        ->and(\DB::table('settings')->pluck('payload')->implode('|'))->not->toContain('1111');
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

it('prihvata samo cifre, 4 do 8', function (string $candidate) {
    $this->put(route('settings.pin.update'), ['pin' => $candidate, 'pin_confirmation' => $candidate])
        ->assertSessionHasErrors('pin');
})->with(['123', '123456789', 'abcd', '12a4', '']);

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
