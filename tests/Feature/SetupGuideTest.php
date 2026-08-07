<?php

use App\Models\Article;
use App\Models\BankAccount;
use App\Models\Client;
use App\Services\SetupProgress;
use App\Settings\CompanySettings;
use App\Settings\FiscalSettings;

/** Podešena aplikacija: firma, kasa, stope (iz TestCase), artikal i klijent. */
function finishSetup(): void
{
    $company = app(CompanySettings::class);
    $company->name = 'Kalkulatron d.o.o.';
    $company->identification_number = '4403927160006';
    $company->save();

    $fiscal = app(FiscalSettings::class);
    $fiscal->base_url = 'http://esir.test';
    $fiscal->api_key = 'kljuc';
    $fiscal->save();

    Article::create(['name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F', 'is_active' => true]);
    Client::create(['name' => 'Kupac d.o.o.']);
    BankAccount::create(['bank_name' => 'Banka', 'account_number' => '5551000000000000', 'show_on_documents' => true]);
}

it('na svježoj instalaciji nudi korake umjesto prazne liste računa', function (): void {
    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('Podesite aplikaciju za rad')
        ->assertSee('Veza sa fiskalnom kasom')
        // Dugme za novi račun nema smisla dok kasa nije podešena.
        ->assertDontSee('Nema pronađenih računa');
});

it('kad je sve podešeno, vodiča nema', function (): void {
    finishSetup();

    expect(app(SetupProgress::class)->isComplete())->toBeTrue();

    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertDontSee('Podesite aplikaciju za rad')
        ->assertSee('Nema pronađenih računa');
});

it('koraci prate stvarno stanje aplikacije', function (): void {
    $steps = collect(app(SetupProgress::class)->steps())->keyBy('key');

    // Veza sa kasom je prvi korak; bankovni račun je obavezan, ne preporučen.
    expect(array_key_first($steps->all()))->toBe('device')
        ->and($steps['company']['done'])->toBeFalse()
        ->and($steps['tax_rates']['done'])->toBeTrue()   // stopa dolazi iz TestCase
        ->and($steps['bank_account']['done'])->toBeFalse()
        ->and(app(SetupProgress::class)->remaining())->toBe(5);

    Article::create(['name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F']);

    expect(app(SetupProgress::class)->remaining())->toBe(4);
});

it('sklonjen vodič se ne vraća sam, ali stoji u podešavanjima', function (): void {
    $this->post(route('setup.dismiss'))
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('status', 'Vodič je sklonjen. Stoji u Podešavanja → Početno podešavanje.');

    $this->get(route('invoices.index'))->assertDontSee('Podesite aplikaciju za rad');

    $this->get(route('settings.setup.edit'))
        ->assertSuccessful()
        ->assertSee('Podesite aplikaciju za rad')
        ->assertSee('Vrati vodič na račune');

    $this->post(route('setup.restore'))->assertRedirect(route('settings.setup.edit'));

    $this->get(route('invoices.index'))->assertSee('Podesite aplikaciju za rad');
});

it('ko već ima račune, ne dobija vodič', function (): void {
    $this->post(route('invoices.store'), invoicePayload());

    expect(app(SetupProgress::class)->isComplete())->toBeFalse()
        ->and(app(SetupProgress::class)->shouldShow())->toBeFalse();
});
