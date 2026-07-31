<?php

use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Settings\MenuSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prikazuje šifarnike', function (string $route) {
    $this->get(route($route))->assertStatus(200);
})->with(['bank-accounts.index', 'bank-accounts.create', 'currencies.index', 'currencies.create']);

it('dodaje bankovni račun', function () {
    $this->post(route('bank-accounts.store'), [
        'bank_name' => 'UniCredit', 'account_number' => '5510010000000000', 'show_on_documents' => '1',
    ])->assertRedirect(route('bank-accounts.index'));

    expect(BankAccount::sole())->bank_name->toBe('UniCredit')->show_on_documents->toBeTrue();
});

it('traži naziv banke i broj računa', function () {
    $this->post(route('bank-accounts.store'), [])->assertSessionHasErrors(['bank_name', 'account_number']);
});

it('dodaje valutu velikim slovima', function () {
    $this->post(route('currencies.store'), ['code' => 'usd', 'name' => 'Dolar', 'symbol' => '$'])
        ->assertRedirect(route('currencies.index'));

    expect(Currency::where('code', 'USD')->exists())->toBeTrue();
});

it('ne dozvoljava dvije valute sa istom oznakom', function () {
    // EUR dolazi iz migracije šifarnika.
    $this->post(route('currencies.store'), ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'])
        ->assertSessionHasErrors('code');
});

it('drži tačno jednu podrazumijevanu valutu', function () {
    $bam = Currency::where('code', 'BAM')->sole();
    $eur = Currency::where('code', 'EUR')->sole();

    $this->put(route('currencies.update', $eur), [
        'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => '1',
    ])->assertRedirect(route('currencies.index'));

    expect($eur->refresh()->is_default)->toBeTrue()
        ->and($bam->refresh()->is_default)->toBeFalse();
});

it('ne briše podrazumijevanu valutu', function () {
    $bam = Currency::where('code', 'BAM')->sole();

    $this->delete(route('currencies.destroy', $bam))->assertSessionHas('error');

    expect($bam->fresh())->not->toBeNull();
});

it('čuva kurs valute prema KM', function () {
    $eur = Currency::where('code', 'EUR')->sole();

    $this->post(route('currencies.rates.store', $eur), [
        'rate_to_bam' => '1.95583', 'rate_date' => '2026-07-31',
    ])->assertRedirect(route('currencies.edit', $eur));

    expect((float) ExchangeRate::where('currency', 'EUR')->value('rate_to_bam'))->toBe(1.95583);
});

it('prepisuje kurs za isti dan umjesto da doda drugi', function () {
    $eur = Currency::where('code', 'EUR')->sole();

    foreach (['1.90000', '1.95583'] as $rate) {
        $this->post(route('currencies.rates.store', $eur), ['rate_to_bam' => $rate, 'rate_date' => '2026-07-31']);
    }

    expect(ExchangeRate::where('currency', 'EUR')->count())->toBe(1)
        ->and((float) ExchangeRate::where('currency', 'EUR')->value('rate_to_bam'))->toBe(1.95583);
});

it('nema kursa za podrazumijevanu valutu', function () {
    $bam = Currency::where('code', 'BAM')->sole();

    $this->post(route('currencies.rates.store', $bam), ['rate_to_bam' => '1', 'rate_date' => '2026-07-31'])
        ->assertSessionHas('error');
});

it('otvara sve sekcije pomoći na koje podešavanja upućuju', function () {
    $help = $this->get(route('help'))->assertStatus(200)->getContent();

    foreach (['profil-kompanije', 'fiskalizacija', 'numeracija', 'meni', 'pin', 'mail'] as $anchor) {
        expect($help)->toContain('id="'.$anchor.'"');
    }
});

it('premješta modul iz menija u drawer', function () {
    $this->put(route('settings.menu.update'), ['menu_modules' => ['invoices', 'currencies']])
        ->assertRedirect(route('settings.menu.edit'));

    $settings = app(MenuSettings::class);

    expect($settings->menu_modules)->toBe(['invoices', 'currencies'])
        ->and($settings->drawerModules())->toBe(['clients', 'articles', 'bank-accounts']);
});

it('servira formu šifarnika kao dio drawera', function (string $route) {
    $partial = $this->get(route($route, ['partial' => 1]));

    $partial->assertStatus(200)->assertDontSee('<!DOCTYPE html>', false);
})->with(['clients.create', 'articles.create', 'bank-accounts.create', 'currencies.create']);

it('ne ugnježdava formu za brisanje u formu za čuvanje', function () {
    $client = Client::create(['name' => 'Za brisanje']);

    $html = $this->get(route('clients.edit', [$client, 'partial' => 1]))->getContent();

    // Ugniježdenu formu preglednik izmjesti, pa čuvanje ode na rutu za brisanje.
    expect($html)->toMatch('/<\/form>\s*\n[\s\S]*id="delete-entity"/')
        ->and(substr_count($html, '<form'))->toBe(2);
});

it('čuva klijenta iz drawera i vraća poruku', function () {
    $client = Client::create(['name' => 'Stari naziv']);

    $this->putJson(route('clients.update', $client), ['name' => 'Novi naziv', 'is_active' => '1'])
        ->assertStatus(200)
        ->assertJson(['message' => 'Izmjene su sačuvane.']);

    expect($client->fresh()->name)->toBe('Novi naziv');
});

it('vraća greške validacije kao JSON za drawer', function () {
    $this->postJson(route('clients.store'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors('name');
});
