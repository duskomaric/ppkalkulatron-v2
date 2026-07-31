<?php

use App\Models\BankAccount;
use App\Models\Currency;
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
