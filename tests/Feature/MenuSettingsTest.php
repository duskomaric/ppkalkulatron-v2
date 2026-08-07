<?php

use App\Settings\MenuSettings;

/** Raspored menija sačuvan onako kako to radi ekran podešavanja. */
function saveMenuLayout(array $menu, array $drawer, int $max = 4)
{
    return test()->put(route('settings.menu.update'), [
        'menu_modules' => $menu,
        'drawer_modules' => $drawer,
        'max_menu_items' => $max,
    ]);
}

it('premješta modul iz menija u drawer', function (): void {
    saveMenuLayout(['invoices', 'currencies'], ['bank-accounts', 'clients', 'articles'])
        ->assertRedirect(route('settings.menu.edit'));

    $settings = app(MenuSettings::class);

    expect($settings->menu_modules)->toBe(['invoices', 'currencies'])
        ->and($settings->drawerModules())->toBe(['bank-accounts', 'clients', 'articles']);
});

it('prikazuje temu i opcije navigacije', function (): void {
    $this->get(route('settings.menu.edit'))
        ->assertSuccessful()
        ->assertSee('Izgled aplikacije')
        ->assertSee('Svijetla')
        ->assertSee('Sistemska')
        ->assertSee('Navigacija')
        ->assertSee('Maksimalno stavki u meniju')
        ->assertSee('Više')
        ->assertSee('aria-label="Pomjeri modul gore"', false)
        ->assertSee('aria-label="Pomjeri modul dolje"', false);
});

it('čuva redoslijed i ograničenje menija', function (): void {
    saveMenuLayout(['articles', 'invoices', 'clients'], ['currencies', 'bank-accounts'], max: 2)
        ->assertRedirect(route('settings.menu.edit'));

    $settings = app(MenuSettings::class);

    expect($settings->menu_modules)->toBe(['articles', 'invoices'])
        ->and($settings->drawerModules())->toBe(['currencies', 'bank-accounts', 'clients']);
});

it('donji meni nosi samo stavke do ograničenja, ostalo ide u više modula', function (): void {
    saveMenuLayout(['invoices', 'clients', 'articles'], ['bank-accounts', 'currencies'], max: 2);

    $html = $this->get(route('invoices.index'))->assertSuccessful()->getContent();

    // Atribut title= emituje samo donji meni, po jednoj stavci iz navItems.
    expect($html)->toContain('title="Računi"')
        ->and($html)->toContain('title="Klijenti"')
        ->and($html)->not->toContain('title="Artikli"')
        ->and($html)->not->toContain('title="Bankovni računi"')
        ->and($html)->not->toContain('title="Valute"')
        // Višak se ne gubi: dugme „Više" otvara drawer sa ostatkom.
        ->and($html)->toContain('title="Više modula"')
        ->and(app(MenuSettings::class)->drawerModules())
        ->toBe(['bank-accounts', 'currencies', 'articles']);
});

it('drži modul u meniju izvan drawera i kad je poslat u oba', function (): void {
    saveMenuLayout(['invoices', 'clients'], ['clients', 'currencies']);

    $settings = app(MenuSettings::class);

    expect($settings->drawer_modules)->not->toContain('clients')
        ->and($settings->drawerModules())->not->toContain('clients');
});

it('spušta u drawer modul koji nije nigdje naveden', function (): void {
    saveMenuLayout(['invoices'], ['clients']);

    // Ništa se ne sakriva: articles, bank-accounts i currencies nisu poslati,
    // pa se moraju naći u draweru.
    expect(app(MenuSettings::class)->drawerModules())
        ->toBe(['clients', 'articles', 'bank-accounts', 'currencies']);
});

it('dozvoljava prazan meni i sve u draweru', function (): void {
    saveMenuLayout([], ['invoices', 'clients', 'articles', 'bank-accounts', 'currencies'], max: 1)
        ->assertRedirect(route('settings.menu.edit'));

    expect(app(MenuSettings::class)->menu_modules)->toBe([])
        ->and(app(MenuSettings::class)->drawerModules())->toHaveCount(5);
});

it('redoslijed za ekran podešavanja ide meni pa drawer', function (): void {
    saveMenuLayout(['currencies', 'invoices'], ['articles', 'clients']);

    expect(app(MenuSettings::class)->orderedModules())
        ->toBe(['currencies', 'invoices', 'articles', 'clients', 'bank-accounts']);
});

it('ne prima nepoznat modul', function (array $payload): void {
    $this->put(route('settings.menu.update'), $payload + ['max_menu_items' => 4])
        ->assertSessionHasErrors();
})->with([
    'u meniju' => [['menu_modules' => ['invoices', 'izmisljeno'], 'drawer_modules' => []]],
    'u draweru' => [['menu_modules' => [], 'drawer_modules' => ['izmisljeno']]],
    'dupli u draweru' => [['menu_modules' => [], 'drawer_modules' => ['clients', 'clients']]],
]);

it('ne prima više od četiri stavke u meniju', function (): void {
    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['invoices', 'clients', 'articles', 'bank-accounts', 'currencies'],
        'drawer_modules' => [],
        'max_menu_items' => 4,
    ])->assertSessionHasErrors('menu_modules');
});

it('ne prima ograničenje menija van jedan do četiri', function (mixed $max): void {
    $this->put(route('settings.menu.update'), [
        'menu_modules' => ['invoices'],
        'drawer_modules' => [],
        'max_menu_items' => $max,
    ])->assertSessionHasErrors('max_menu_items');
})->with([0, 5, 99, -1, 'dva', '']);

it('poruka o čuvanju pokriva cijelu stranicu, ne samo meni', function (): void {
    $payload = [
        'menu_modules' => ['invoices'],
        'drawer_modules' => ['clients'],
        'max_menu_items' => 4,
        'primary_color' => app(MenuSettings::class)->primary_color,
    ];

    // Ista boja: mijenja se samo raspored.
    $this->put(route('settings.menu.update'), $payload)
        ->assertSessionHas('status', 'Izgled i navigacija su sačuvani.');

    $this->put(route('settings.menu.update'), [...$payload, 'primary_color' => '#2563EB'])
        ->assertSessionHas('status', 'Boja i raspored su sačuvani.');
});
