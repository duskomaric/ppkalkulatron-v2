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

it('na svježoj instalaciji sam otvara vodič, a lista računa ostaje', function (): void {
    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('Podesite aplikaciju za rad')
        ->assertSee('Veza sa fiskalnom kasom')
        // Vodič je panel, pa stranica i dalje pokazuje svoje.
        ->assertSee('Nema pronađenih računa')
        ->assertSee('setupDrawer = true', false);
});

it('poslije klika na korak se ne otvara preko stranice na koju vodi', function (): void {
    $this->get(route('settings.fiscal.edit'))
        ->assertSuccessful()
        // Sadržaj vodiča i dalje stoji u meniju, ali se panel ne otvara sam.
        ->assertSee('setupDrawer = false', false)
        ->assertSee('Podesite aplikaciju za rad');
});

it('kad je sve podešeno, vodič se ne otvara sam', function (): void {
    finishSetup();

    expect(app(SetupProgress::class)->isComplete())->toBeTrue();

    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('setupDrawer = false', false);
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

it('sklonjen vodič se ne otvara sam, ali ostaje dostupan iz menija', function (): void {
    $this->from(route('invoices.index'))->post(route('setup.dismiss'))
        ->assertRedirect(route('invoices.index'))
        ->assertSessionHas('status', 'Vodič se više neće otvarati sam. Stoji u Podešavanja → Početno podešavanje.');

    $this->get(route('invoices.index'))
        ->assertSuccessful()
        // Panel se ne otvara sam, ali stavka u meniju i sadržaj ostaju.
        ->assertSee('setupDrawer = false', false)
        ->assertSee('Podesite aplikaciju za rad')
        ->assertSee('Neka se opet otvara sam');

    $this->from(route('invoices.index'))->post(route('setup.restore'))->assertRedirect(route('invoices.index'));

    $this->get(route('invoices.index'))->assertSee('setupDrawer = true', false);
});

it('ko već ima račune, ne dobija vodič', function (): void {
    $this->post(route('invoices.store'), invoicePayload());

    expect(app(SetupProgress::class)->isComplete())->toBeFalse()
        ->and(app(SetupProgress::class)->shouldShow())->toBeFalse();
});

it('demo podaci se nude iz vodiča, dok ih ne uklonimo', function (): void {
    // PRIVREMENO: dugme postoji samo za interno testiranje.
    // Vodič je u layoutu, pa je dugme dostupno svuda gdje se panel može otvoriti.
    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('Demo podaci')
        ->assertSee('Popuni demo podacima');

    unlocked()->post(route('setup.demo'))->assertSessionHas('status');
});
