<?php

use App\Models\AppSetting;
use App\Services\PinLock;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * PIN je opcionalan: prvi put nije podešen i ulazi se direktno. Kad se podesi,
 * traži se pri svakom pokretanju.
 */
function setPin(string $pin = '1234'): void
{
    app(PinLock::class)->set($pin);
}

/**
 * Postavke PIN-a su iza zaključavanja — mijenjati ga se može samo iz otključane
 * aplikacije, pa se tek onda traži i trenutni PIN kao druga brana.
 */
function unlockWith(): void
{
    test()->withSession([PinLock::SESSION_KEY => now()->toIso8601String()]);
}

it('pušta u aplikaciju kad PIN nije podešen', function () {
    $this->get('/')->assertStatus(200)->assertSee('ppKalkulatron v2');
});

it('traži otključavanje kad je PIN podešen', function () {
    setPin();

    $this->get('/')->assertRedirect(route('unlock'));
});

it('otključava ispravnim PIN-om', function () {
    setPin('4321');

    $this->post(route('unlock.store'), ['pin' => '4321'])->assertRedirect(route('home'));

    $this->get('/')->assertStatus(200);
});

it('ne otključava pogrešnim PIN-om', function () {
    setPin('1234');

    $this->post(route('unlock.store'), ['pin' => '9999'])
        ->assertSessionHasErrors('pin');

    $this->get('/')->assertRedirect(route('unlock'));
});

it('čuva PIN kao hash, nikad kao tekst', function () {
    setPin('1234');

    $stored = AppSetting::get('pin.hash');

    expect($stored)->not->toBe('1234')
        ->and($stored)->toStartWith('$2y$')
        ->and(AppSetting::all()->pluck('value')->implode('|'))->not->toContain('1234');
});

it('zaključava nakon pet promašaja i pamti to izvan sesije', function () {
    setPin('1234');
    $pin = app(PinLock::class);

    foreach (range(1, PinLock::MAX_ATTEMPTS) as $i) {
        $this->post(route('unlock.store'), ['pin' => '0000']);
    }

    expect($pin->isLockedOut())->toBeTrue();

    // Nova sesija ne pomaže — brojač je u bazi, ne u sesiji.
    $this->flushSession();

    expect(app(PinLock::class)->isLockedOut())->toBeTrue();

    // Čak i ispravan PIN ne prolazi dok zaključavanje traje.
    $this->post(route('unlock.store'), ['pin' => '1234'])->assertSessionHasErrors('pin');
});

it('resetuje brojač promašaja nakon uspješnog otključavanja', function () {
    setPin('1234');

    $this->post(route('unlock.store'), ['pin' => '0000']);
    expect(app(PinLock::class)->attemptsLeft())->toBe(PinLock::MAX_ATTEMPTS - 1);

    $this->post(route('unlock.store'), ['pin' => '1234']);
    expect(app(PinLock::class)->attemptsLeft())->toBe(PinLock::MAX_ATTEMPTS);
});

it('postavlja PIN prvi put bez trenutnog PIN-a', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1234', 'pin_confirmation' => '1234'])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(PinLock::class)->isEnabled())->toBeTrue();
});

it('ostavlja onoga ko je postavio PIN otključanim', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1234', 'pin_confirmation' => '1234']);

    $this->get('/')->assertStatus(200);
});

it('traži potvrdu PIN-a', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1234', 'pin_confirmation' => '5678'])
        ->assertSessionHasErrors('pin');

    expect(app(PinLock::class)->isEnabled())->toBeFalse();
});

it('prihvata samo cifre, 4 do 8', function (string $candidate) {
    $this->put(route('settings.pin.update'), ['pin' => $candidate, 'pin_confirmation' => $candidate])
        ->assertSessionHasErrors('pin');

    expect(app(PinLock::class)->isEnabled())->toBeFalse();
})->with(['123', '123456789', 'abcd', '12a4', '']);

it('traži trenutni PIN za promjenu', function () {
    setPin('1111');
    unlockWith();

    $this->put(route('settings.pin.update'), [
        'pin' => '2222',
        'pin_confirmation' => '2222',
    ])->assertSessionHasErrors('current_pin');

    expect(app(PinLock::class)->verify('1111'))->toBeTrue();
});

it('mijenja PIN uz ispravan trenutni', function () {
    setPin('1111');
    unlockWith();

    $this->put(route('settings.pin.update'), [
        'current_pin' => '1111',
        'pin' => '2222',
        'pin_confirmation' => '2222',
    ])->assertRedirect(route('settings.pin.edit'));

    $pin = app(PinLock::class);
    expect($pin->verify('2222'))->toBeTrue()
        ->and($pin->verify('1111'))->toBeFalse();
});

it('traži trenutni PIN za uklanjanje', function () {
    setPin('1111');
    unlockWith();

    $this->delete(route('settings.pin.destroy'), ['current_pin' => '9999'])
        ->assertSessionHasErrors('current_pin');

    expect(app(PinLock::class)->isEnabled())->toBeTrue();
});

it('uklanja PIN uz ispravan trenutni i pušta bez zaključavanja', function () {
    setPin('1111');
    unlockWith();

    $this->delete(route('settings.pin.destroy'), ['current_pin' => '1111'])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(PinLock::class)->isEnabled())->toBeFalse();

    $this->get('/')->assertStatus(200);
});

it('drži postavke PIN-a iza zaključavanja', function () {
    setPin('1111');

    // Bez otključavanja se PIN ne može ni vidjeti ni promijeniti ni ukloniti.
    $this->get(route('settings.pin.edit'))->assertRedirect(route('unlock'));

    $this->put(route('settings.pin.update'), [
        'current_pin' => '1111',
        'pin' => '2222',
        'pin_confirmation' => '2222',
    ])->assertRedirect(route('unlock'));

    $this->delete(route('settings.pin.destroy'), ['current_pin' => '1111'])
        ->assertRedirect(route('unlock'));

    $pin = app(PinLock::class);
    expect($pin->isEnabled())->toBeTrue()
        ->and($pin->verify('1111'))->toBeTrue();
});

it('vraća sa ekrana za otključavanje kad PIN nije podešen', function () {
    $this->get(route('unlock'))->assertRedirect(route('home'));
});

it('zaključava na zahtjev i traži PIN ponovo', function () {
    setPin('1234');
    $this->post(route('unlock.store'), ['pin' => '1234']);

    $this->post(route('unlock.destroy'))->assertRedirect(route('unlock'));

    $this->get('/')->assertRedirect(route('unlock'));
});
