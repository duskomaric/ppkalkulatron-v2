<?php

use App\Models\FiscalTaxRate;
use App\Services\Diagnostics;
use App\Services\FiscalDeviceHealth;
use App\Services\NetworkScanner;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    app(FiscalDeviceHealth::class)->forget();
});

it('vraća spreman status uređaja i kešira ga jednu minutu', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['gsc' => []], 200),
    ]);

    unlocked()->getJson(route('checks'))
        ->assertSuccessful()
        ->assertJson(['fiscal' => [
            'state' => 'ready',
            'label' => 'Uređaj povezan',
            'is_stale' => false,
        ]]);

    unlocked()->getJson(route('checks'))->assertSuccessful();

    // Uređaj se provjeri jednom (drugi put ide iz keša), a uz to ide i kursna lista.
    Http::assertSentCount(3);
});

it('ne prikazuje interni JSON status kao stranicu', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 503),
    ]);

    unlocked()->get(route('checks'))
        ->assertRedirect(route('invoices.index'));
});

it('prijavljuje uređaj koji traži PIN', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['gsc' => ['1500']], 200),
    ]);

    unlocked()->getJson(route('checks'))
        ->assertSuccessful()
        ->assertJson(['fiscal' => ['state' => 'pin_required', 'label' => 'Potreban PIN uređaja']]);
});

it('ažurira indikator nakon ručne provjere uređaja', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response([
            'gsc' => [], 'uid' => 'test-device',
            'currentTaxRates' => ['groupId' => 1, 'taxCategories' => [[
                'name' => 'ECAL', 'taxRates' => [['label' => 'F', 'rate' => 11]],
            ]]],
        ], 200),
    ]);

    unlocked()->post(route('settings.fiscal.test'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('status');

    expect(app(FiscalDeviceHealth::class)->current())
        ->toMatchArray(['state' => 'ready', 'label' => 'Uređaj povezan', 'is_stale' => false]);
});

it('ručna provjera ne preuzima stope bez izričite radnje', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response([
            'gsc' => [],
            'uid' => 'esir-123',
            'currentTaxRates' => ['groupId' => 1, 'taxCategories' => [[
                'taxRates' => [['label' => 'F', 'rate' => 11]],
            ]]],
        ], 200),
    ]);

    unlocked()->post(route('settings.fiscal.test'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('status', 'Fiskalna kasa je dostupna i spremna za fiskalizaciju.');

    expect(FiscalTaxRate::query()->count())->toBe(1);
});

it('preuzima poreske oznake bez promjene ćirilice', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response([
            'currentTaxRates' => [
                'groupId' => 7,
                'taxCategories' => [[
                    'categoryType' => 0,
                    'name' => 'О-ПДВ',
                    'taxRates' => [['label' => 'Ђ', 'rate' => 20]],
                ]],
            ],
            'allTaxRates' => [[
                'groupId' => 7,
                'validFrom' => '2026-01-01T00:00:00+01:00',
                'taxCategories' => [[
                    'categoryType' => 0,
                    'name' => 'О-ПДВ',
                    'taxRates' => [['label' => 'Ђ', 'rate' => 20]],
                ]],
            ]],
        ]),
    ]);

    unlocked()->post(route('settings.fiscal.tax-rates.sync'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('status', 'Preuzeto poreskih stopa: 1.');

    expect(FiscalTaxRate::query()->sole())
        ->label->toBe('Ђ')
        ->category_name->toBe('О-ПДВ')
        ->rate->toBe('20.00')
        ->and(FiscalTaxRate::query()->count())->toBe(1)
        ->and(FiscalTaxRate::query()->where('label', 'F')->exists())->toBeFalse();
});

it('ne mijenja katalog kada kasa nije dostupna', function (): void {
    Http::fake(['*/api/attention' => Http::response('', 503)]);

    unlocked()->post(route('settings.fiscal.tax-rates.sync'))
        ->assertRedirect()
        ->assertSessionHas('error', 'Fiskalna kasa nije dostupna. Provjerite mrežnu vezu i podatke za pristup.');

    expect(FiscalTaxRate::query()->count())->toBe(1);
});

it('ne mijenja katalog kada status kase nije dostupan', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response('', 503),
    ]);

    unlocked()->post(route('settings.fiscal.tax-rates.sync'))
        ->assertRedirect()
        ->assertSessionHas('error', 'Fiskalna kasa trenutno nije spremna za fiskalizaciju. Provjerite status uređaja i PIN.');
});

it('odbija katalog bez trenutno važećih stopa', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['currentTaxRates' => ['groupId' => 1]], 200),
    ]);

    unlocked()->post(route('settings.fiscal.tax-rates.sync'))
        ->assertRedirect()
        ->assertSessionHas('error', 'Fiskalni uređaj nije poslao trenutno važeće poreske stope.');
});

it('ne preuzima stope pri otvaranju ili spremanju artikla', function (): void {
    $this->app['env'] = 'production';
    $this->withoutMiddleware(PreventRequestForgery::class);
    Http::fake();

    unlocked()->get(route('articles.create'))->assertSuccessful();

    unlocked()->post(route('articles.store'), [
        'name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F',
    ])->assertRedirect(route('articles.index'));

    Http::assertNothingSent();
});

it('ne preuzima stope pri otvaranju novog računa', function (): void {
    $this->app['env'] = 'production';
    Http::fake();

    unlocked()->get(route('invoices.create'))->assertSuccessful();

    Http::assertNothingSent();
});

it('označava uređaj nedostupnim kada ručna provjera ne prođe', function (): void {
    Http::fake(['*/api/attention' => Http::response('', 503)]);

    unlocked()->post(route('settings.fiscal.test'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Fiskalna kasa nije dostupna. Provjerite mrežnu vezu i podatke za pristup.');

    expect(app(FiscalDeviceHealth::class)->current()['state'])->toBe('unavailable');
});

it('označava uređaj nedostupnim kada attention prođe, a status ne prođe', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response('', 503),
    ]);

    unlocked()->post(route('settings.fiscal.test'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Fiskalna kasa trenutno nije spremna za fiskalizaciju. Provjerite status uređaja i PIN.');

    expect(app(FiscalDeviceHealth::class)->current()['state'])->toBe('unavailable');
});

it('prikaže zahtjev za PIN iz ručne provjere', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['gsc' => ['1500']], 200),
    ]);

    unlocked()->post(route('settings.fiscal.test'))
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Uređaj traži PIN sigurnosnog elementa.');

    expect(app(FiscalDeviceHealth::class)->current()['state'])->toBe('pin_required');
});

it('označava uređaj kao nedostupan kada provjera ne uspije', function (): void {
    Http::fake(['*/api/attention' => Http::response('', 503)]);

    expect(app(FiscalDeviceHealth::class)->refresh())
        ->toMatchArray(['state' => 'unavailable', 'label' => 'Uređaj nije dostupan']);
});

it('označava uređaj nedostupnim kada status endpoint ne uspije', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response('', 503),
    ]);

    expect(app(FiscalDeviceHealth::class)->refresh())
        ->toMatchArray(['state' => 'unavailable', 'label' => 'Uređaj nije dostupan']);
});

it('označava uređaj nedostupnim kada mrežni poziv baci izuzetak', function (): void {
    Http::fake(['*/api/attention' => Http::failedConnection('Nema mreže')]);

    expect(app(FiscalDeviceHealth::class)->refresh())
        ->toMatchArray(['state' => 'unavailable', 'label' => 'Uređaj nije dostupan']);
});

it('prikaže posljednji poznati fiskalni status bez čekanja na listi računa', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['gsc' => []], 200),
    ]);
    app(FiscalDeviceHealth::class)->refresh();

    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('Uređaj povezan')
        ->assertSee('backgroundChecks(')
        ->assertSee("url: '\\/provjere'", false);

    Http::assertSentCount(2);
});

it('prikazuje status uz provjeru uređaja i fiskalizaciju računa', function (): void {
    $settings = $this->get(route('settings.fiscal.edit'))
        ->assertSuccessful()
        ->getContent();
    $invoice = $this->get(route('invoices.show', makeInvoice()))
        ->assertSuccessful()
        ->getContent();

    expect($settings)->toContain('backgroundChecks(')
        ->and($invoice)->toContain('backgroundChecks(');
});

/**
 * Skener kojem su svi portovi „otvoreni".
 *
 * Pregled porta ide preko stvarnih konekcija, pa se u testu preskače — provjerava
 * se dio koji prepoznaje kasu po odgovoru na `/api/attention`.
 */
function fakeOpenPorts(): void
{
    app()->instance(NetworkScanner::class, new class(app(Diagnostics::class)) extends NetworkScanner
    {
        protected function openPorts(array $addresses, int $port, float $deadline): ?array
        {
            return $addresses;
        }
    });
}

it('skenira ispravan opseg i javlja rezultat kao JSON', function (): void {
    fakeOpenPorts();

    Http::fake([
        'http://10.0.0.1:3566/api/attention' => Http::response('', 200),
        'http://10.0.0.2:3566/api/attention' => Http::response('', 503),
    ]);

    unlocked()->postJson(route('settings.fiscal.scan'), ['range' => '10.0.0.1-2'])
        ->assertSuccessful()
        ->assertJson([
            'devices' => ['http://10.0.0.1:3566'],
            'message' => 'Pronađeno uređaja: 1.',
        ]);
});

it('odbija neprepoznat opseg prije mrežnog skeniranja', function (): void {
    unlocked()->postJson(route('settings.fiscal.scan'), ['range' => '999.0.0.1-2'])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Opseg nije prepoznat. Primjer: 192.168.31.100-105 ili 192.168.31.']);
});

it('traži ručni opseg kada uređaj nema lokalnu IP adresu', function (): void {
    $this->mock(NetworkScanner::class, function ($mock): void {
        $mock->shouldReceive('localIp')->once()->andReturnNull();
    });

    unlocked()->postJson(route('settings.fiscal.scan'), [])
        ->assertUnprocessable()
        ->assertJson(['message' => 'Nije moguće pročitati lokalnu adresu uređaja. Unesite opseg ručno.']);
});

it('vraća jasnu poruku kada skeniranje ne pronađe fiskalni uređaj', function (): void {
    fakeOpenPorts();

    Http::fake(['http://10.0.0.1:3566/api/attention' => Http::response('', 503)]);

    unlocked()->postJson(route('settings.fiscal.scan'), ['range' => '10.0.0.1-1'])
        ->assertSuccessful()
        ->assertJson(['devices' => [], 'message' => 'Nijedan uređaj nije pronađen. Provjerite da su ovaj uređaj i kasa na istoj mreži, ili unesite opseg ručno.']);
});

it('pronađe prethodni fiskalni zahtjev po RequestId-u', function (): void {
    Http::fake(['*/api/invoices/request/*' => Http::response([
        'invoiceNumber' => 'ABCD-1',
        'invoiceCounter' => '1/2ПП',
    ])]);

    unlocked()->post(route('settings.fiscal.find-request'), ['request_id' => 'ponovljeni-zahtjev'])
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('status', 'Pronađen račun ABCD-1, brojač 1/2ПП.');
});

it('jasno prijavi kada fiskalni uređaj ne pronađe prethodni zahtjev', function (): void {
    Http::fake(['*/api/invoices/request/*' => Http::response([])]);

    unlocked()->post(route('settings.fiscal.find-request'), ['request_id' => 'nepostojeci-zahtjev'])
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('status', 'Zahtjev nije pronađen — fiskalizacija vjerovatno nije prošla.');
});

it('prijavi HTTP grešku pri potrazi za prethodnim fiskalnim zahtjevom', function (): void {
    Http::fake(['*/api/invoices/request/*' => Http::response('', 503)]);

    unlocked()->post(route('settings.fiscal.find-request'), ['request_id' => 'nedostupni-uredjaj'])
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Prethodni zahtjev nije moguće provjeriti. Provjerite vezu sa kasom, pa pokušajte ponovo.');
});

it('ne otkriva tehnički detalj pri neočekivanoj grešci potrage po RequestId-u', function (): void {
    Http::fake(fn () => throw new LogicException('tajni detalj uređaja'));

    unlocked()->post(route('settings.fiscal.find-request'), ['request_id' => 'ponovljeni-zahtjev'])
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Fiskalni uređaj trenutno nije dostupan. Pokušajte ponovo.');
});

it('vrati korisnu poruku kada je veza prekinuta pri unosu fiskalnog PIN-a', function (): void {
    Http::fake(['*/api/pin' => Http::failedConnection('Nema mreže')]);

    unlocked()->post(route('settings.fiscal.pin'), ['security_pin' => '1234'])
        ->assertRedirect(route('settings.fiscal.edit'))
        ->assertSessionHas('error', 'Fiskalna kasa nije dostupna. Provjerite da je uključena i na istoj mreži, pa pokušajte ponovo.');
});

it('uz rezultat skeniranja vraća i šta je pregledano', function (): void {
    fakeOpenPorts();

    Http::fake(['http://10.0.0.1:3566/api/attention' => Http::response('', 200)]);

    $report = unlocked()->postJson(route('settings.fiscal.scan'), ['range' => '10.0.0.1-2'])
        ->assertSuccessful()
        ->json('report');

    expect($report)->toMatchArray(['addresses' => 2, 'port' => 3566, 'open_ports' => 2, 'pass' => 1])
        ->and($report['seconds'])->toBeNumeric();
});

it('prikazuje tok skeniranja sa podmrežom i rokovima', function (): void {
    $html = unlocked()->get(route('settings.fiscal.edit'))->assertSuccessful()->getContent();

    // Prikaz toka prati stvarne rokove skenera, pa se dovlače iz njega.
    expect($html)->toContain('networkScan({ subnet:')
        ->and($html)->toContain(json_encode(NetworkScanner::deadlines()))
        ->and($html)->toContain('Otvaram veze prema svim adresama odjednom');
});

it('kad ništa ne sluša na portu, kaže šta dalje', function (): void {
    $html = unlocked()->get(route('settings.fiscal.edit'))->assertSuccessful()->getContent();

    expect($html)->toContain('ali nijedan ne sluša na portu')
        ->and($html)->toContain('nije javio nijedan uređaj');
});
