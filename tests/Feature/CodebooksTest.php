<?php

use App\Enums\DocumentTemplate;
use App\Models\Article;
use App\Models\BankAccount;
use App\Models\Client;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\FiscalTaxRate;
use App\Services\PinLock;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use App\Settings\NumberingSettings;
use App\Settings\UserSettings;

it('prikazuje šifarnike', function (string $route): void {
    $this->get(route($route))->assertSuccessful();
})->with(['bank-accounts.index', 'bank-accounts.create', 'currencies.index', 'currencies.create']);

it('nudi sljedeći korak na praznim listama', function (string $route, string $label): void {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee($label);
})->with([
    'artikli' => ['articles.index', 'Dodaj artikl'],
    'klijenti' => ['clients.index', 'Dodaj klijenta'],
    'bankovni računi' => ['bank-accounts.index', 'Dodaj račun'],
    'računi' => ['invoices.index', 'Novi račun'],
]);

it('drži glavne forme podešavanja u čitljivoj širini', function (string $route): void {
    $html = $this->get(route($route))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('max-w-3xl');
})->with([
    'kompanija' => 'settings.company.edit',
    'generalno' => 'settings.general.edit',
    'mail' => 'settings.mail.edit',
    'fiskalizacija' => 'settings.fiscal.edit',
    'meni' => 'settings.menu.edit',
]);

it('dodaje bankovni račun', function (): void {
    $this->post(route('bank-accounts.store'), [
        'bank_name' => 'UniCredit', 'account_number' => '5510010000000000', 'show_on_documents' => '1',
    ])->assertRedirect(route('bank-accounts.index'));

    expect(BankAccount::sole())->bank_name->toBe('UniCredit')->show_on_documents->toBeTrue();
});

it('sortira, mijenja i briše bankovni račun kroz standardne rute', function (): void {
    $zaba = BankAccount::create([
        'bank_name' => 'ZABA',
        'account_number' => '5510010000000001',
    ]);
    $addiko = BankAccount::create([
        'bank_name' => 'Addiko',
        'account_number' => '5510010000000002',
    ]);

    $this->get(route('bank-accounts.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Addiko', 'ZABA']);
    $this->get(route('bank-accounts.edit', $zaba))
        ->assertSuccessful()
        ->assertViewHas('account', fn (BankAccount $account): bool => $account->is($zaba));

    $this->put(route('bank-accounts.update', $zaba), [
        'bank_name' => 'UniCredit',
        'account_number' => '5510010000000003',
        'swift' => 'UNCRBA22',
        'show_on_documents' => '1',
    ])->assertRedirect(route('bank-accounts.index'))
        ->assertSessionHas('status', 'Izmjene su sačuvane.');

    expect($zaba->fresh()->bank_name)->toBe('UniCredit')
        ->and($zaba->fresh()->show_on_documents)->toBeTrue();

    $this->delete(route('bank-accounts.destroy', $addiko))
        ->assertRedirect(route('bank-accounts.index'))
        ->assertSessionHas('status', 'Bankovni račun je obrisan.');

    $this->assertModelMissing($addiko);
});

it('čuva opšta, kompanijska i profilna podešavanja kroz njihove forme', function (): void {
    $this->get(route('settings.general.edit'))
        ->assertSuccessful()
        ->assertViewHasAll(['numbering', 'document', 'company']);

    $this->put(route('settings.general.update'), [
        'pad_zeros' => 5,
        'invoice_prefix' => 'RAC',
        'invoice_starting_number' => 42,
        'reset_yearly' => '1',
        'template' => 'modern',
        'language' => 'bs',
        'invoice_due_days' => 30,
        'invoice_notes' => 'Hvala na povjerenju.',
    ])->assertRedirect(route('settings.general.edit'))
        ->assertSessionHas('status', 'Podešavanja su sačuvana.');

    expect(app(NumberingSettings::class)->pad_zeros)->toBe(5)
        ->and(app(NumberingSettings::class)->invoice_prefix)->toBe('RAC')
        ->and(app(NumberingSettings::class)->invoice_starting_number)->toBe(42)
        ->and(app(NumberingSettings::class)->reset_yearly)->toBeTrue()
        ->and(app(DocumentSettings::class)->template)->toBe('modern')
        ->and(app(DocumentSettings::class)->language)->toBe('bs')
        ->and(app(DocumentSettings::class)->invoice_due_days)->toBe(30)
        ->and(app(DocumentSettings::class)->invoice_notes)->toBe('Hvala na povjerenju.');

    $this->get(route('settings.general.edit'))
        ->assertSuccessful()
        ->assertSee('Nije u PDV sistemu')
        ->assertSee('Validna bez pečata')
        ->assertSee('Predložak računa')
        ->assertSee('Svi stilovi')
        ->assertSee('Kod i editor')
        ->assertSee('Tamni')
        ->assertSee('template-preview-frame', false)
        ->assertSee(route('settings.templates.preview', DocumentTemplate::OpsConsole), false)
        ->assertSee('name="template" value="ops-console"', false)
        ->assertSee('name="template" value="workstation"', false);

    $this->get(route('settings.company.edit'))
        ->assertSuccessful()
        ->assertViewHas('settings');

    $this->put(route('settings.company.update'), [
        'name' => 'Kalkulatron d.o.o.',
        'email' => 'firma@example.test',
        'city' => 'Doboj',
        'is_small_entrepreneur' => '1',
    ])->assertRedirect(route('settings.company.edit'))
        ->assertSessionHas('status', 'Podaci kompanije su sačuvani.');

    expect(app(CompanySettings::class)->name)->toBe('Kalkulatron d.o.o.')
        ->and(app(CompanySettings::class)->is_small_entrepreneur)->toBeTrue()
        ->and(app(CompanySettings::class)->is_vat_obligor)->toBeFalse();

    $this->get(route('profile.edit'))
        ->assertSuccessful()
        ->assertViewHas('user');

    $this->put(route('profile.update'), [
        'first_name' => 'Ana',
        'last_name' => 'Anić',
        'email' => 'ana@example.test',
    ])->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'Podaci su sačuvani.');

    expect(app(UserSettings::class)->fullName())->toBe('Ana Anić')
        ->and(app(UserSettings::class)->email)->toBe('ana@example.test');
});

it('prikazuje stvarni izgled odabranog PDF predloška sa oglednim podacima', function (): void {
    BankAccount::query()->create([
        'bank_name' => 'Addiko Bank a.d. Banja Luka',
        'account_number' => '5510010000000003',
        'swift' => 'HAABBA22',
        'show_on_documents' => true,
    ]);

    $this->get(route('settings.templates.preview', DocumentTemplate::OpsConsole))
        ->assertSuccessful()
        ->assertSee('◆ / RAČUN')
        ->assertSee('Primjer kupac d.o.o.')
        ->assertSee('Konsultantska usluga')
        ->assertSee('Addiko Bank a.d. Banja Luka')
        ->assertSee('Izdao')
        ->assertSee('Primio')
        ->assertSee(config('app.name'))
        ->assertSee('v'.config('nativephp.version'))
        ->assertSee('build '.config('nativephp.version_code'));
});

it('vraća pregled predloška bez okvira aplikacije', function (): void {
    $response = $this->get(route('settings.templates.preview', DocumentTemplate::OpsConsole))
        ->assertSuccessful()
        ->assertSee('Primjer kupac d.o.o.');

    expect($response->getContent())->not->toContain('template-gallery');
});

it('grupiše predloške po stilu i temi za filtere', function (): void {
    expect(DocumentTemplate::Classic->family())->toBe(DocumentTemplate::FAMILY_BUSINESS)
        ->and(DocumentTemplate::PhpSource->family())->toBe(DocumentTemplate::FAMILY_EDITOR)
        ->and(DocumentTemplate::Shell->family())->toBe(DocumentTemplate::FAMILY_TERMINAL)
        ->and(DocumentTemplate::NetworkPacket->family())->toBe(DocumentTemplate::FAMILY_SIGNAL)
        ->and(DocumentTemplate::PhpSourceDark->tone())->toBe('dark')
        ->and(DocumentTemplate::PhpSource->tone())->toBe('light');

    foreach (DocumentTemplate::cases() as $template) {
        expect($template->familyLabel())->not->toBeEmpty();
    }
});

it('uzima katalog predložaka isključivo iz enum-a', function (): void {
    foreach (DocumentTemplate::cases() as $template) {
        expect($template->view())->toBe('pdf.invoice-'.$template->value)
            ->and(view()->exists($template->view()))->toBeTrue();
    }

    $this->put(route('settings.general.update'), [
        'pad_zeros' => 4,
        'invoice_prefix' => '',
        'invoice_starting_number' => 1,
        'template' => 'uklonjeni-predlozak',
        'language' => 'bs',
        'invoice_due_days' => 15,
    ])->assertSessionHasErrors('template');
});

it('traži naziv banke i broj računa', function (): void {
    $this->post(route('bank-accounts.store'), [])->assertSessionHasErrors(['bank_name', 'account_number']);
});

it('dodaje valutu velikim slovima', function (): void {
    $this->post(route('currencies.store'), ['code' => 'usd', 'name' => 'Dolar', 'symbol' => '$'])
        ->assertRedirect(route('currencies.index'));

    expect(Currency::where('code', 'USD')->exists())->toBeTrue();
});

it('ne dozvoljava dvije valute sa istom oznakom', function (): void {
    // EUR dolazi iz migracije šifarnika.
    $this->post(route('currencies.store'), ['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€'])
        ->assertSessionHasErrors('code');
});

it('drži tačno jednu podrazumijevanu valutu', function (): void {
    $bam = Currency::where('code', 'BAM')->sole();
    $eur = Currency::where('code', 'EUR')->sole();

    $this->put(route('currencies.update', $eur), [
        'code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'is_default' => '1',
    ])->assertRedirect(route('currencies.index'));

    expect($eur->refresh()->is_default)->toBeTrue()
        ->and($bam->refresh()->is_default)->toBeFalse();
});

it('ne briše podrazumijevanu valutu', function (): void {
    $bam = Currency::where('code', 'BAM')->sole();

    $this->delete(route('currencies.destroy', $bam))->assertSessionHas('error');

    expect($bam->fresh())->not->toBeNull();
});

it('čuva kurs valute prema KM', function (): void {
    $eur = Currency::where('code', 'EUR')->sole();

    $this->post(route('currencies.rates.store', $eur), [
        'rate_to_bam' => '1.95583', 'rate_date' => '2026-07-31',
    ])->assertRedirect(route('currencies.index'));

    expect((float) ExchangeRate::where('currency', 'EUR')->value('rate_to_bam'))->toBe(1.95583);
});

it('prepisuje kurs za isti dan umjesto da doda drugi', function (): void {
    $eur = Currency::where('code', 'EUR')->sole();

    foreach (['1.90000', '1.95583'] as $rate) {
        $this->post(route('currencies.rates.store', $eur), ['rate_to_bam' => $rate, 'rate_date' => '2026-07-31']);
    }

    expect(ExchangeRate::where('currency', 'EUR')->count())->toBe(1)
        ->and((float) ExchangeRate::where('currency', 'EUR')->value('rate_to_bam'))->toBe(1.95583);
});

it('nema kursa za podrazumijevanu valutu', function (): void {
    $bam = Currency::where('code', 'BAM')->sole();

    $this->post(route('currencies.rates.store', $bam), ['rate_to_bam' => '1', 'rate_date' => '2026-07-31'])
        ->assertSessionHas('error');
});

it('otvara sve sekcije pomoći na koje podešavanja upućuju', function (): void {
    $help = $this->get(route('help'))->assertSuccessful()->getContent();

    foreach (['pocetak', 'profil-kompanije', 'fiskalizacija', 'numeracija', 'meni', 'pin', 'mail', 'backup', 'backup-aplikacije'] as $anchor) {
        expect($help)->toContain('id="'.$anchor.'"');
    }

    expect($help)->toContain('Napravi i pošalji arhivu')
        ->and($help)->toContain('Backup aplikacije')
        ->and($help)->toContain('Reset aplikacije')
        ->and($help)->toContain('Moj nalog')
        ->and($help)->toContain(config('app.name').' služi za izdavanje računa');
});

it('povezuje svaki radni obrazac sa odgovarajućom pomoći', function (string $route, string $anchor): void {
    $html = $this->get(route($route))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain(route('help').'#'.$anchor);
})->with([
    'račun' => ['invoices.create', 'racuni'],
    'klijent' => ['clients.create', 'klijenti'],
    'artikal' => ['articles.create', 'artikli'],
    'bankovni račun' => ['bank-accounts.create', 'bankovni-racuni'],
    'valuta' => ['currencies.create', 'valute'],
    'opšta podešavanja' => ['settings.general.edit', 'numeracija'],
    'fiskalizacija' => ['settings.fiscal.edit', 'fiskalizacija'],
    'mail' => ['settings.mail.edit', 'mail'],
    'backup' => ['settings.backup.edit', 'backup'],
    'meni' => ['settings.menu.edit', 'meni'],
    'PIN' => ['settings.pin.edit', 'pin'],
    'profil' => ['profile.edit', 'profil-kompanije'],
]);

it('prikazuje kontekstualnu pomoć u zaglavlju svakog radnog ekrana', function (string $route, string $anchor): void {
    $html = $this->get(route($route))
        ->assertSuccessful()
        ->getContent();

    expect($html)->toContain('title="Pomoć za ovu stranicu"')
        ->and($html)->toContain(route('help').'#'.$anchor);
})->with([
    'lista računa' => ['invoices.index', 'racuni'],
    'lista klijenata' => ['clients.index', 'klijenti'],
    'lista artikala' => ['articles.index', 'artikli'],
    'lista bankovnih računa' => ['bank-accounts.index', 'bankovni-racuni'],
    'lista valuta' => ['currencies.index', 'valute'],
    'profil kompanije' => ['settings.company.edit', 'profil-kompanije'],
    'fiskalna podešavanja' => ['settings.fiscal.edit', 'fiskalizacija'],
    'mail podešavanja' => ['settings.mail.edit', 'mail'],
    'backup podešavanja' => ['settings.backup.edit', 'backup'],
    'backup aplikacije' => ['settings.database.edit', 'backup-aplikacije'],
    'opšta podešavanja' => ['settings.general.edit', 'numeracija'],
    'meni podešavanja' => ['settings.menu.edit', 'meni'],
    'PIN podešavanja' => ['settings.pin.edit', 'pin'],
]);

// Podešavanja menija stoje u MenuSettingsTest.

it('servira pune forme šifarnika', function (string $route): void {
    $this->get(route($route))
        ->assertSuccessful()
        ->assertSee('<!DOCTYPE html>', false);
})->with(['clients.create', 'articles.create', 'bank-accounts.create', 'currencies.create']);

it('ne ugnježdava formu za brisanje u formu za čuvanje', function (): void {
    $client = Client::create(['name' => 'Za brisanje']);

    $html = $this->get(route('clients.edit', $client))->getContent();

    // Ugniježdenu formu preglednik izmjesti, pa čuvanje ode na rutu za brisanje.
    expect($html)->toMatch('/<\/form>\s*\n[\s\S]*id="delete-entity"/')
        ->and(substr_count($html, '<form'))->toBeGreaterThanOrEqual(2);
});

it('koristi zajedničku potvrdu za svaku destruktivnu radnju', function (): void {
    $client = Client::create(['name' => 'Za brisanje']);

    $clientEdit = $this->get(route('clients.edit', $client))
        ->assertSuccessful()
        ->getContent();

    setPin();
    $pinSettings = unlocked()->get(route('settings.pin.edit'))
        ->assertSuccessful()
        ->getContent();

    expect($clientEdit)->toContain('data-confirm="Obrisati klijenta Za brisanje?"')
        ->and($clientEdit)->toContain('x-show="$store.confirmation.open"')
        ->and($clientEdit)->not->toContain('onsubmit="return confirm(')
        ->and($pinSettings)->toContain('data-confirm="Ukloniti PIN?"')
        ->and($pinSettings)->not->toContain('onsubmit="return confirm(');
});

it('ostavlja prostor za indikator na svakom tipu select polja', function (): void {
    $invoiceForm = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();
    $settingsForm = $this->get(route('settings.general.edit'))
        ->assertSuccessful()
        ->getContent();

    expect($invoiceForm)->toContain('appearance-none')
        ->and($invoiceForm)->toContain('pr-10')
        ->and($settingsForm)->toContain('appearance-none')
        ->and($settingsForm)->toContain('pr-10');
});

it('koristi jedinstveni select za formu i filtere', function (): void {
    $invoiceForm = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();
    $invoiceList = $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->getContent();

    expect($invoiceForm)->toMatch('/<select[^>]*id="payment_type"[^>]*>\s*<option value="Cash"/s')
        ->and($invoiceList)->toContain('name="status"')
        ->and($invoiceList)->toContain('onchange="this.form.requestSubmit()"')
        ->and($invoiceList)->toContain('h-11');
});

it('koristi kanonske filtere bez praznih prikaza u pomoći', function (): void {
    $invoiceList = $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->getContent();
    $help = $this->get(route('help'))
        ->assertSuccessful()
        ->getContent();

    expect($invoiceList)->toMatch('/<input[^>]*type="search"[^>]*class="[^"]*h-11[^"]*pl-10[^"]*"/')
        ->and($invoiceList)->toMatch('/<input[^>]*type="date"[^>]*class="[^"]*h-11[^"]*"/')
        ->and($help)->not->toContain('Slika još nije dodana')
        ->and($help)->toContain('Format fiskalnog dokumenta');
});

it('koristi kanonski input i u kompaktnoj formi računa', function (): void {
    $invoiceForm = $this->get(route('invoices.create'))
        ->assertSuccessful()
        ->getContent();

    expect($invoiceForm)->toMatch('/<input[^>]*id="date"[^>]*class="[^"]*h-11[^"]*"/')
        ->and($invoiceForm)->toMatch('/<input[^>]*id="due_date"[^>]*class="[^"]*h-11[^"]*"/');
});

it('čuva izmjene klijenta standardnim zahtjevom', function (): void {
    $client = Client::create(['name' => 'Stari naziv']);

    $this->put(route('clients.update', $client), ['name' => 'Novi naziv', 'is_active' => '1'])
        ->assertRedirect(route('clients.index'));

    expect($client->fresh()->name)->toBe('Novi naziv');
});

it('dodaje klijenta sa nazivom preko standardnog zahtjeva', function (): void {
    $this->post(route('clients.store'), ['name' => 'Novi kupac', 'is_active' => '1'])
        ->assertRedirect(route('clients.index'));

    expect(Client::where('name', 'Novi kupac')->exists())->toBeTrue();
});

it('vraća greške validacije kroz standardni Laravel odgovor', function (): void {
    $this->from(route('clients.create'))
        ->post(route('clients.store'), ['name' => ''])
        ->assertRedirect(route('clients.create'))
        ->assertSessionHasErrors('name');
});

it('pretražuje klijente po imenu i održava abecedni redoslijed', function (): void {
    Client::create(['name' => 'Zidarstvo Doboj']);
    Client::create(['name' => 'Alfa trgovina']);

    $this->get(route('clients.index'))
        ->assertSuccessful()
        ->assertSeeInOrder(['Alfa trgovina', 'Zidarstvo Doboj']);
    $this->get(route('clients.index', ['q' => 'Zidarstvo']))
        ->assertSuccessful()
        ->assertSee('Zidarstvo Doboj')
        ->assertDontSee('Alfa trgovina');
});

it('ne briše klijenta koji već ima račun, ali briše nepovezanog', function (): void {
    $linked = makeInvoice()->client;
    $unlinked = Client::create(['name' => 'Bez računa']);

    $this->delete(route('clients.destroy', $linked))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('error', 'Klijent ima račune i ne može se obrisati.');

    $this->assertModelExists($linked);

    $this->delete(route('clients.destroy', $unlinked))
        ->assertRedirect(route('clients.index'))
        ->assertSessionHas('status', 'Klijent je obrisan.');

    $this->assertModelMissing($unlinked);
});

it('prikazuje prilagodljive kartice za telefon i desktop', function (string $route): void {
    Client::create(['name' => 'Kupac', 'city' => 'Doboj', 'is_active' => true]);
    Article::create(['name' => 'Usluga', 'unit' => 'kom', 'tax_label' => 'F', 'is_active' => true]);

    $html = $this->get(route($route))->assertSuccessful()->getContent();

    expect($html)->toContain('md:hidden')->and($html)->toContain('hidden md:block');
})->with(['clients.index', 'articles.index']);

it('priprema porezne stope u kontroleru artikala', function (): void {
    FiscalTaxRate::query()->where('label', 'F')->update(['rate' => 11]);
    $article = Article::create(['name' => 'Usluga s porezom', 'unit' => 'kom', 'tax_label' => 'F']);

    $this->get(route('articles.index'))
        ->assertSuccessful()
        ->assertViewHas('taxRates', fn (array $taxRates): bool => (float) $taxRates['F'] === 11.0)
        ->assertViewHas('fiscalHealth')
        ->assertSee($article->name)
        ->assertSee('F (11.00%)');

    $this->get(route('articles.create'))
        ->assertSuccessful()
        ->assertViewHas('taxRateOptions', fn (array $options): bool => $options['F'] === 'F — ECAL (11.00%)');
});

it('mijenja cijenu artikla u pfeninge i briše artikl', function (): void {
    $article = Article::create([
        'name' => 'Stara usluga',
        'unit' => 'kom',
        'last_unit_price' => 100,
    ]);

    $this->get(route('articles.edit', $article))
        ->assertSuccessful()
        ->assertViewHas('article', fn (Article $value): bool => $value->is($article));

    $this->put(route('articles.update', $article), [
        'name' => 'Nova usluga',
        'unit' => 'sat',
        'tax_label' => 'F',
        'last_unit_price' => '80.55',
        'is_active' => '1',
    ])->assertRedirect(route('articles.index'))
        ->assertSessionHas('status', 'Izmjene su sačuvane.');

    expect($article->fresh()->last_unit_price)->toBe(8055)
        ->and($article->fresh()->unit->value)->toBe('sat');

    $this->delete(route('articles.destroy', $article))
        ->assertRedirect(route('articles.index'))
        ->assertSessionHas('status', 'Artikl je obrisan.');

    $this->assertModelMissing($article);
});

it('prikazuje istoriju kursa i briše valutu koja nije podrazumijevana', function (): void {
    $usd = Currency::create(['code' => 'USD', 'name' => 'Dolar', 'symbol' => '$']);
    ExchangeRate::create(['currency' => 'USD', 'rate_to_bam' => '1.80100', 'rate_date' => '2026-01-01']);
    ExchangeRate::create(['currency' => 'USD', 'rate_to_bam' => '1.80200', 'rate_date' => '2026-02-01']);

    $this->get(route('currencies.edit', $usd))
        ->assertSuccessful()
        // Kurs se čuva na osam decimala zbog valuta koje se kotiraju na 100 jedinica.
        ->assertViewHas('rates', fn ($rates): bool => $rates->pluck('rate_to_bam')->all() === ['1.80200000', '1.80100000']);

    $this->delete(route('currencies.destroy', $usd))
        ->assertRedirect(route('currencies.index'))
        ->assertSessionHas('status', 'Valuta je obrisana.');

    $this->assertModelMissing($usd);
});

it('daje layout komponentama podatke iz view composera', function (): void {
    $company = app(CompanySettings::class);
    $company->name = 'Kalkulatron d.o.o.';
    $company->save();

    $user = app(UserSettings::class);
    $user->first_name = 'Ana';
    $user->last_name = 'Anić';
    $user->save();

    $this->get(route('invoices.index'))
        ->assertSuccessful()
        ->assertSee('Kalkulatron d.o.o.')
        ->assertSee('Ana Anić');
});

it('otključava sa četiri polja za cifre', function (): void {
    app(PinLock::class)->set('1111');

    $html = $this->get(route('unlock'))->assertSuccessful()->getContent();

    expect($html)->toContain('pinEntry()')
        ->and($html)->toContain('autocomplete="one-time-code"')
        ->and($html)->toContain('maxlength="1"');
});

it('drži kurs po valuti i datumu, ne po valuti', function (): void {
    $eur = Currency::where('code', 'EUR')->sole();

    foreach (['2026-07-30' => '1.95000', '2026-07-31' => '1.95583'] as $date => $rate) {
        $this->post(route('currencies.rates.store', $eur), ['rate_to_bam' => $rate, 'rate_date' => $date]);
    }

    expect(ExchangeRate::where('currency', 'EUR')->count())->toBe(2);
});

it('nudi numeričku tastaturu na poljima u koja idu samo cifre', function (string $route, array $fields): void {
    $html = $this->get(route($route))->assertSuccessful()->getContent();

    foreach ($fields as $name => $mode) {
        // Polje se traži po imenu, pa se provjeri da mu inputmode stoji u istom tagu.
        $tag = str($html)->after('name="'.$name.'"')->before('>')->toString();

        expect($tag)->toContain('inputmode="'.$mode.'"');
    }
})->with([
    'klijent' => ['clients.create', [
        'phone' => 'tel', 'zip' => 'numeric', 'vat_id' => 'numeric', 'tax_id' => 'numeric',
    ]],
    'profil kompanije' => ['settings.company.edit', [
        'phone' => 'tel', 'zip' => 'numeric', 'identification_number' => 'numeric', 'vat_number' => 'numeric',
    ]],
    'artikal' => ['articles.create', ['last_unit_price' => 'decimal', 'gtin' => 'numeric']],
    'mail' => ['settings.mail.edit', ['port' => 'numeric']],
    'numeracija' => ['settings.general.edit', [
        'invoice_starting_number' => 'numeric', 'pad_zeros' => 'numeric', 'invoice_due_days' => 'numeric',
    ]],
    'fiskalizacija' => ['settings.fiscal.edit', ['pac' => 'numeric', 'security_pin' => 'numeric']],
    'PIN' => ['settings.pin.edit', ['pin' => 'numeric', 'pin_confirmation' => 'numeric']],
]);

it('preuzima podatke firme sa sertifikata kase', function (): void {
    Http::fake(['*/api/certificate' => Http::response([
        'name' => 'THROWCODE s.p.',
        'address' => 'Jefimijina 53',
        'city' => 'Prnjavor',
        'country' => 'BA',
        // JIB na sertifikatu zna imati vodeći razmak.
        'tin' => ' 4512171520008',
        'serialNumber' => '6CHWYBFU',
        'validTo' => '2030-04-28T09:11:59+02:00',
    ])]);

    $this->post(route('settings.company.import'))
        ->assertRedirect(route('settings.company.edit'))
        // Država je već „BA“, pa se ne prijavljuje kao izmjena.
        ->assertSessionHas('status', 'Preuzeto sa kase: naziv, adresa, grad, JIB.');

    $company = app(CompanySettings::class);

    expect($company->name)->toBe('THROWCODE s.p.')
        ->and($company->identification_number)->toBe('4512171520008')
        ->and($company->city)->toBe('Prnjavor');
});

it('kad se podaci firme poklapaju, ništa ne mijenja', function (): void {
    Http::fake(['*/api/certificate' => Http::response(['name' => 'THROWCODE s.p.'])]);

    $this->post(route('settings.company.import'));

    $this->post(route('settings.company.import'))
        ->assertSessionHas('status', 'Podaci sa kase se poklapaju sa unesenim — ništa nije mijenjano.');
});

it('dugme za preuzimanje podataka firme šalje formu', function (): void {
    // type="button" ne šalje ništa; bez type="submit" klik ne bi radio.
    $html = $this->get(route('settings.company.edit'))->assertSuccessful()->getContent();

    $button = str($html)->after('Preuzmi sa kase')->before('</form>')->toString();
    $trigger = str($html)->before('Preuzmi sa kase')->afterLast('<button')->toString();

    expect($html)->toContain('id="import-company"')
        ->and($trigger)->toContain('form="import-company"')
        ->and($trigger)->toContain('type="submit"')
        ->and($button)->not->toBeEmpty();
});
