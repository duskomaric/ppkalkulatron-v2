<?php

use App\Services\PinLock;
use App\Settings\SecuritySettings;
use Illuminate\Support\Facades\Schema;

// setPin(), unlocked() i pretendPersistentRuntime() stoje u tests/Pest.php.

// Oznaka trajnog procesa je statična, pa se poslije svakog testa vraća na false.
afterEach(fn () => pretendPersistentRuntime(false));

it('pušta u račune kad PIN nije podešen', function () {
    $this->get('/')->assertRedirect(route('invoices.index'));
    $this->get(route('invoices.index'))->assertSuccessful();
});

it('koristi PIN zaključavanje bez Laravel auth tabela', function () {
    expect(Schema::hasTable('users'))->toBeFalse()
        ->and(Schema::hasTable('password_reset_tokens'))->toBeFalse()
        ->and(Schema::hasTable('sessions'))->toBeTrue();
});

it('traži PIN kad je podešen', function () {
    setPin();

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('prikazuje verziju i build na ekranu za otključavanje', function () {
    setPin();

    $this->get(route('unlock'))
        ->assertSuccessful()
        ->assertSee('v'.config('nativephp.version'))
        ->assertSee('build '.config('nativephp.version_code'))
        ->assertViewHasAll(['appReleaseVersion', 'appBuildCode', 'assetBuildHash']);
});

it('šalje PIN automatski bez dugmeta za otključavanje', function () {
    setPin();

    $html = $this->get(route('unlock'))->assertSuccessful()->getContent();

    expect($html)->toContain('pinEntry()')
        ->and($html)->toContain(':autofocus="index === 1"')
        ->and($html)->toContain('enterkeyhint="done"')
        ->and($html)->not->toContain('>Otključaj<');
});

it('otključava ispravnim PIN-om', function () {
    setPin('4321');

    $this->post(route('unlock.store'), ['pin' => '4321'])->assertRedirect(route('invoices.index'));
    $this->get(route('invoices.index'))->assertSuccessful();
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
    $this->get(route('invoices.index'))->assertSuccessful();
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

    unlocked()->delete(route('settings.pin.destroy'))->assertRedirect(route('settings.pin.edit'));

    expect(app(PinLock::class)->isEnabled())->toBeFalse();
    $this->get(route('invoices.index'))->assertSuccessful();
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
    $this->get(route('invoices.index'))->assertSuccessful();

    $this->travel(16)->minutes();

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('ne zaključava dok se koristi', function () {
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);

    foreach (range(1, 3) as $ignored) {
        $this->travel(14)->minutes();
        $this->get(route('invoices.index'))->assertSuccessful();
    }
});

it('ne zaključava kad je automatsko zaključavanje isključeno', function () {
    setPin('1111');
    $settings = app(SecuritySettings::class);
    $settings->auto_lock_minutes = 0;
    $settings->save();

    $this->post(route('unlock.store'), ['pin' => '1111']);
    $this->travel(3)->hours();

    $this->get(route('invoices.index'))->assertSuccessful();
});

it('mijenja vrijeme automatskog zaključavanja', function () {
    setPin('1111');

    unlocked()->put(route('settings.pin.update-lock'), ['auto_lock_minutes' => 30])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(SecuritySettings::class)->auto_lock_minutes)->toBe(30);
});

it('prihvata samo ponuđene intervale zaključavanja', function (int $minutes) {
    setPin('1111');

    unlocked()->put(route('settings.pin.update-lock'), ['auto_lock_minutes' => $minutes])
        ->assertRedirect(route('settings.pin.edit'));

    expect(app(SecuritySettings::class)->auto_lock_minutes)->toBe($minutes);
})->with([0, 1, 5, 15, 30, 60]);

it('odbija interval zaključavanja koji nije na listi', function (mixed $minutes) {
    setPin('1111');

    unlocked()->put(route('settings.pin.update-lock'), ['auto_lock_minutes' => $minutes])
        ->assertSessionHasErrors('auto_lock_minutes');
})->with([2, 7, 45, 120, -1, 'pola sata', '']);

it('ostaje na ekranu za otključavanje kad je PIN pogrešan', function () {
    setPin('1111');

    // Validaciona greška se vraća na formu sa porukom u sesiji.
    $this->post(route('unlock.store'), ['pin' => '9999'])
        ->assertRedirect(route('unlock'))
        ->assertSessionHasErrors('pin');
});

it('vraća standardni 405 kad se GET-om pogodi POST ruta', function () {
    $this->get('/lock')->assertMethodNotAllowed();
    $this->get('/podesavanja/fiskalizacija/provjera')->assertMethodNotAllowed();
});

it('traži PIN ponovo poslije restarta aplikacije', function () {
    pretendPersistentRuntime();
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);
    $this->get(route('invoices.index'))->assertSuccessful();

    // Na uređaju i sesija i kolačići prežive restart, pa je oznaka procesa
    // jedino što razlikuje „isto pokretanje" od „aplikacija je ponovo startovana".
    $this->withSession([
        PinLock::SESSION_KEY => true,
        PinLock::BOOT_KEY => 'staro-pokretanje',
    ]);

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('ne pušta ni kad je automatsko zaključavanje isključeno a aplikacija restartovana', function () {
    pretendPersistentRuntime();
    setPin('1111');
    $settings = app(SecuritySettings::class);
    $settings->auto_lock_minutes = 0;
    $settings->save();

    $this->withSession([
        PinLock::SESSION_KEY => true,
        PinLock::BOOT_KEY => 'staro-pokretanje',
    ]);

    $this->get(route('invoices.index'))->assertRedirect(route('unlock'));
});

it('ograničava broj pokušaja PIN-a', function () {
    setPin('1111');

    foreach (range(1, 5) as $ignored) {
        $this->post(route('unlock.store'), ['pin' => '9999']);
    }

    $this->post(route('unlock.store'), ['pin' => '9999'])->assertTooManyRequests();
});

it('ne traži PIN na svaki klik van trajnog procesa', function () {
    // Pod php-fpm i ugrađenim serverom svaki zahtjev je novi proces; da se oznaka
    // pokretanja tamo provjeravala, korisnik bi bio zaključan odmah poslije PIN-a.
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);

    $this->get(route('invoices.index'))->assertSuccessful();
    $this->get(route('clients.index'))->assertSuccessful();
});

it('ne pokazuje ekran za otključavanje kad nema šta da se otključa', function () {
    // Bez podešenog PIN-a ekran bi bio ćorsokak: unos ne bi imao sa čim da se poredi.
    $this->get(route('unlock'))->assertRedirect(route('invoices.index'));
    $this->post(route('unlock.store'), ['pin' => '1111'])->assertRedirect(route('invoices.index'));
});

it('ne pokazuje ekran za otključavanje otključanoj aplikaciji', function () {
    setPin('1111');

    unlocked()->get(route('unlock'))->assertRedirect(route('invoices.index'));
});

it('traži PIN u zahtjevu za otključavanje', function () {
    setPin('1111');

    $this->post(route('unlock.store'), [])->assertSessionHasErrors('pin');
});

it('razlikuje postavljanje PIN-a od promjene', function () {
    $this->put(route('settings.pin.update'), ['pin' => '1111', 'pin_confirmation' => '1111'])
        ->assertSessionHas('status', 'PIN je postavljen.');

    $this->put(route('settings.pin.update'), ['pin' => '2222', 'pin_confirmation' => '2222'])
        ->assertSessionHas('status', 'PIN je promijenjen.');

    // Stari PIN više ne otvara aplikaciju.
    $this->post(route('unlock.destroy'));
    $this->post(route('unlock.store'), ['pin' => '1111'])->assertSessionHasErrors('pin');
    $this->post(route('unlock.store'), ['pin' => '2222'])->assertRedirect(route('invoices.index'));
});

it('ne provjerava PIN koji nije podešen', function () {
    expect(app(PinLock::class)->verify('1111'))->toBeFalse();
});

it('poslije neaktivnosti kaže zašto je zaključano', function () {
    setPin('1111');
    $this->post(route('unlock.store'), ['pin' => '1111']);
    $this->get(route('invoices.index'))->assertSuccessful();

    $this->travel(16)->minutes();

    $this->get(route('invoices.index'))
        ->assertRedirect(route('unlock'))
        ->assertSessionHas('error', 'Aplikacija je zaključana zbog neaktivnosti.');
});
