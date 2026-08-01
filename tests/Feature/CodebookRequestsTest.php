<?php

use App\Enums\Unit;
use App\Http\Requests\EnterFiscalPinRequest;
use App\Http\Requests\FindFiscalRequestRequest;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\ScanFiscalNetworkRequest;
use App\Http\Requests\SendInvoiceEmailRequest;
use App\Http\Requests\UnlockRequest;
use App\Http\Requests\UpdateAutoLockSettingsRequest;
use App\Http\Requests\UpdateCompanySettingsRequest;
use App\Http\Requests\UpdateFiscalSettingsRequest;
use App\Http\Requests\UpdateGeneralSettingsRequest;
use App\Http\Requests\UpdateMailSettingsRequest;
use App\Http\Requests\UpdateMenuSettingsRequest;
use App\Http\Requests\UpdatePinRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Article;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Currency;
use App\Models\FiscalTaxRate;

/*
 * Form Requesti šifarnika: ArticleRequest, BankAccountRequest, ClientRequest i
 * CurrencyRequest. Kontroleri im vjeruju bez ijedne dodatne provjere, pa je ovo
 * jedino mjesto gdje se pravila potvrđuju.
 */

it('traži obavezna polja šifarnika', function (string $route, array $errors) {
    $this->post(route($route), [])->assertSessionHasErrors($errors);
})->with([
    'artikal' => ['articles.store', ['name', 'unit', 'tax_label']],
    'bankovni račun' => ['bank-accounts.store', ['bank_name', 'account_number']],
    'klijent' => ['clients.store', ['name']],
    'valuta' => ['currencies.store', ['code', 'name', 'symbol']],
]);

it('eksplicitno odobrava interne forme aplikacije', function () {
    $requests = [
        new InvoiceRequest,
        new SendInvoiceEmailRequest,
        new UpdateCompanySettingsRequest,
        new UpdateFiscalSettingsRequest,
        new UpdateGeneralSettingsRequest,
        new UpdateMailSettingsRequest,
        new UpdateMenuSettingsRequest,
        new UpdateProfileRequest,
        new UpdateAutoLockSettingsRequest,
        new UpdatePinRequest,
        new UnlockRequest,
        new ScanFiscalNetworkRequest,
        new EnterFiscalPinRequest,
        new FindFiscalRequestRequest,
    ];

    expect(array_map(fn ($request) => $request->authorize(), $requests))->each->toBeTrue();
});

it('validira preostale konfiguracione zahtjeve prije rada kontrolera', function () {
    unlocked()->put(route('profile.update'), [])
        ->assertSessionHasErrors('first_name');
    unlocked()->post(route('settings.fiscal.pin'), [])
        ->assertSessionHasErrors('security_pin');
    unlocked()->post(route('settings.fiscal.find-request'), [])
        ->assertSessionHasErrors('request_id');
    unlocked()->postJson(route('settings.fiscal.scan'), ['range' => str_repeat('1', 33)])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('range');
});

it('odbija neispravan artikal', function (array $payload, string $error) {
    $this->post(route('articles.store'), $payload + ['name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F'])
        ->assertSessionHasErrors($error);
})->with([
    'predug naziv' => [['name' => str_repeat('a', 256)], 'name'],
    'predug opis' => [['description' => str_repeat('a', 2001)], 'description'],
    'nepoznata jedinica' => [['unit' => 'komad'], 'unit'],
    'nepoznata poreska oznaka' => [['tax_label' => 'Z'], 'tax_label'],
    'prekratak GTIN' => [['gtin' => '1234567'], 'gtin'],
    'predug GTIN' => [['gtin' => '123456789012345'], 'gtin'],
    'negativna cijena' => [['last_unit_price' => '-1'], 'last_unit_price'],
    'cijena nije broj' => [['last_unit_price' => 'skupo'], 'last_unit_price'],
]);

it('prima svaku jedinicu mjere iz šifarnika', function (Unit $unit) {
    $this->post(route('articles.store'), ['name' => 'Usluga', 'unit' => $unit->value, 'tax_label' => 'F'])
        ->assertSessionHasNoErrors();

    expect(Article::sole()->unit)->toBe($unit);
})->with(fn () => Unit::cases());

it('prima svaku poresku oznaku koju uređaj prijavljuje', function () {
    foreach (FiscalTaxRate::query()->pluck('label') as $label) {
        $this->post(route('articles.store'), ['name' => 'Usluga '.$label, 'unit' => 'kom', 'tax_label' => $label])
            ->assertSessionHasNoErrors();
    }

    expect(Article::count())->toBe(FiscalTaxRate::query()->count());
});

it('čuva cijenu artikla u pfeningima', function () {
    $this->post(route('articles.store'), [
        'name' => 'Usluga', 'unit' => 'sat', 'tax_label' => 'F', 'last_unit_price' => '80.55',
    ])->assertRedirect(route('articles.index'));

    expect(Article::sole()->last_unit_price)->toBe(8055);
});

it('odbija neispravan bankovni račun', function (array $payload, string $error) {
    $this->post(route('bank-accounts.store'), $payload + [
        'bank_name' => 'UniCredit', 'account_number' => '5510010000000000',
    ])->assertSessionHasErrors($error);
})->with([
    'predug naziv banke' => [['bank_name' => str_repeat('a', 256)], 'bank_name'],
    'predug broj računa' => [['account_number' => str_repeat('1', 65)], 'account_number'],
    'predug SWIFT' => [['swift' => str_repeat('a', 33)], 'swift'],
]);

it('odbija neispravnog klijenta', function (array $payload, string $error) {
    $this->post(route('clients.store'), $payload + ['name' => 'Kupac'])
        ->assertSessionHasErrors($error);
})->with([
    'predug naziv' => [['name' => str_repeat('a', 256)], 'name'],
    'nije email' => [['email' => 'nije-email'], 'email'],
    'predug telefon' => [['phone' => str_repeat('1', 65)], 'phone'],
    'predugačka adresa' => [['address' => str_repeat('a', 501)], 'address'],
    'predug grad' => [['city' => str_repeat('a', 121)], 'city'],
    'predug poštanski broj' => [['zip' => str_repeat('1', 17)], 'zip'],
    'predug JIB' => [['vat_id' => str_repeat('1', 33)], 'vat_id'],
    'predug PDV broj' => [['tax_id' => str_repeat('1', 33)], 'tax_id'],
]);

it('odbija neispravnu valutu', function (array $payload, string $error) {
    $this->post(route('currencies.store'), $payload + ['code' => 'USD', 'name' => 'Dolar', 'symbol' => '$'])
        ->assertSessionHasErrors($error);
})->with([
    'kratka oznaka' => [['code' => 'US'], 'code'],
    'duga oznaka' => [['code' => 'USDX'], 'code'],
    'predug naziv' => [['name' => str_repeat('a', 256)], 'name'],
    'predug simbol' => [['symbol' => str_repeat('$', 9)], 'symbol'],
]);

it('ne prijavljuje valuti sopstvenu oznaku kao zauzetu', function () {
    $eur = Currency::where('code', 'EUR')->sole();

    $this->put(route('currencies.update', $eur), ['code' => 'EUR', 'name' => 'Evro', 'symbol' => '€'])
        ->assertSessionHasNoErrors();

    expect($eur->fresh()->name)->toBe('Evro');
});

it('ne pušta valutu na oznaku koju druga već nosi', function () {
    $eur = Currency::where('code', 'EUR')->sole();

    $this->put(route('currencies.update', $eur), ['code' => 'BAM', 'name' => 'Marka', 'symbol' => 'KM'])
        ->assertSessionHasErrors('code');
});

it('gasi prekidač koji forma nije poslala', function (string $route, string $field, string $model, array $payload) {
    // Neoznačen checkbox se u HTML-u ne šalje; bez prepareForValidation() bi
    // vrijednost ostala nedirnuta, pa bi „isključeno" bilo nemoguće sačuvati.
    $this->post(route($route), $payload)->assertSessionHasNoErrors();

    expect($model::sole()->{$field})->toBeFalse();
})->with([
    'artikal' => ['articles.store', 'is_active', Article::class, ['name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F']],
    'klijent' => ['clients.store', 'is_active', Client::class, ['name' => 'Kupac']],
    'bankovni račun' => [
        'bank-accounts.store', 'show_on_documents', BankAccount::class,
        ['bank_name' => 'UniCredit', 'account_number' => '5510010000000000'],
    ],
]);

it('normalizuje oznaku valute u velika slova', function (string $code) {
    $this->post(route('currencies.store'), ['code' => $code, 'name' => 'Dolar', 'symbol' => '$'])
        ->assertRedirect(route('currencies.index'));

    expect(Currency::where('code', 'USD')->exists())->toBeTrue();
})->with(['usd', 'Usd', 'uSD', 'USD']);
