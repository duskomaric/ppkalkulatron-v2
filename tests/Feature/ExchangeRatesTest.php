<?php

use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateUpdater;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;

/** Odgovor Centralne banke; JPY se kotira na 100 jedinica. */
function cbbhList(string $date = '2026-08-06T00:00:00'): array
{
    return [
        'CurrencyExchangeItems' => [
            ['Country' => 'EMU', 'AlphaCode' => 'EUR', 'Units' => '1', 'Middle' => '1.955830'],
            ['Country' => 'USA', 'AlphaCode' => 'USD', 'Units' => '1', 'Middle' => '1.692773'],
            ['Country' => 'Japan', 'AlphaCode' => 'JPY', 'Units' => '100', 'Middle' => '1.074160'],
        ],
        'Date' => $date,
        'Number' => 153,
    ];
}

it('preuzima kursnu listu za valute koje aplikacija koristi', function (): void {
    Currency::create(['code' => 'USD', 'name' => 'Dolar', 'symbol' => '$']);
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList())]);

    $result = app(ExchangeRateUpdater::class)->refresh();

    expect($result)->toMatchArray(['state' => 'ok', 'rate_date' => '2026-08-06', 'updated' => 2])
        ->and(ExchangeRate::pluck('rate_to_bam', 'currency')->all())
        ->toBe(['EUR' => '1.95583000', 'USD' => '1.69277300']);
});

it('kurs valute koja se kotira na sto jedinica dijeli na jednu', function (): void {
    Currency::create(['code' => 'JPY', 'name' => 'Jen', 'symbol' => '¥']);
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList())]);

    app(ExchangeRateUpdater::class)->refresh();

    // 1,074160 KM za 100 jena → 0,01074160 KM za jedan.
    expect(ExchangeRate::where('currency', 'JPY')->value('rate_to_bam'))->toBe('0.01074160');
});

it('uzima datum liste iz odgovora, ne iz upita', function (): void {
    // Za vikend ili praznik banka vrati posljednju objavljenu listu.
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList('2026-08-07T00:00:00'))]);

    app(ExchangeRateUpdater::class)->refresh();

    expect(ExchangeRate::where('currency', 'EUR')->value('rate_date')->toDateString())->toBe('2026-08-07');
});

it('kad lista nije dostupna zadržava posljednji poznati kurs', function (): void {
    ExchangeRate::create(['currency' => 'EUR', 'rate_to_bam' => '1.95583', 'rate_date' => '2026-08-01']);
    Http::fake(['*cbbh.ba*' => Http::response('', 503)]);

    $result = app(ExchangeRateUpdater::class)->refresh();

    expect($result['state'])->toBe('unavailable')
        ->and($result['rate_date'])->toBe('2026-08-01')
        ->and(ExchangeRate::where('currency', 'EUR')->value('rate_to_bam'))->toBe('1.95583000');
});

it('listu provjerava jednom dnevno', function (): void {
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList())]);
    $updater = app(ExchangeRateUpdater::class);

    $updater->refreshIfStale();
    $updater->refreshIfStale();

    Http::assertSentCount(1);
});

it('bez stranih valuta ne poziva banku', function (): void {
    Currency::query()->where('is_default', false)->delete();
    Http::fake();

    expect(app(ExchangeRateUpdater::class)->refreshIfStale()['state'])->toBe('off');

    Http::assertNothingSent();
});

it('jedan zahtjev nosi i status kase i kursnu listu', function (): void {
    Http::fake([
        '*/api/attention' => Http::response('', 200),
        '*/api/status' => Http::response(['gsc' => []], 200),
        '*cbbh.ba*' => Http::response(cbbhList()),
    ]);

    unlocked()->getJson(route('checks'))
        ->assertSuccessful()
        ->assertJson([
            'fiscal' => ['state' => 'ready'],
            'rates' => ['state' => 'ok', 'rate_date' => '2026-08-06'],
        ]);
});

it('komanda preuzima listu i ispisuje kurseve', function (): void {
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList())]);

    Artisan::call('app:exchange-rates', ['--force' => true]);
    $output = Artisan::output();

    expect($output)->toContain('Kursevi sa 06.08.2026.')
        ->and($output)->toContain('EUR')
        ->and($output)->toContain('1.95583 KM');
});

it('nudi ručno preuzimanje kursne liste na valutama', function (): void {
    Http::fake(['*cbbh.ba*' => Http::response(cbbhList())]);

    $this->post(route('currencies.rates.fetch'))
        ->assertRedirect(route('currencies.index'))
        ->assertSessionHas('status', 'Preuzeta je kursna lista sa danom 06.08.2026. — sačuvano kurseva: 1.');

    $this->get(route('currencies.index'))
        ->assertSuccessful()
        ->assertSee('Kursna lista Centralne banke BiH sa danom 06.08.2026.');
});
