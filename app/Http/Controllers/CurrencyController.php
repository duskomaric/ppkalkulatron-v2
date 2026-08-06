<?php

namespace App\Http\Controllers;

use App\Http\Requests\CurrencyRequest;
use App\Http\Requests\ExchangeRateRequest;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Services\ExchangeRateUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CurrencyController extends Controller
{
    public function index(ExchangeRateUpdater $updater): View
    {
        return view('currencies.index', [
            'currencies' => Currency::orderByDesc('is_default')->orderBy('code')->get(),
            'rates' => ExchangeRate::query()->orderBy('rate_date')->get()->keyBy('currency'),
            'rateCheck' => $updater->current(),
        ]);
    }

    /** Ručno preuzimanje kursne liste; inače se preuzima sama, jednom dnevno. */
    public function fetchRates(ExchangeRateUpdater $updater): RedirectResponse
    {
        $result = $updater->refresh();

        return redirect()->route('currencies.index')->with(
            $result['state'] === 'ok' ? 'status' : 'error',
            match ($result['state']) {
                'ok' => 'Preuzeta je kursna lista sa danom '.now()->parse($result['rate_date'])->format('d.m.Y.')." — sačuvano kurseva: {$result['updated']}.",
                'off' => 'Nema stranih valuta za koje bi se preuzimao kurs.',
                default => 'Kursna lista Centralne banke trenutno nije dostupna. Pokušajte kasnije ili unesite kurs ručno.',
            },
        );
    }

    public function create(): View
    {
        return view('currencies.form', ['currency' => null, 'rates' => collect()]);
    }

    public function store(CurrencyRequest $request): RedirectResponse
    {
        $this->save($request, new Currency);

        return redirect()->route('currencies.index')->with('status', 'Valuta je dodata.');
    }

    public function edit(Currency $currency): View
    {
        return view('currencies.form', [
            'currency' => $currency,
            'rates' => ExchangeRate::where('currency', $currency->code)
                ->orderByDesc('rate_date')->limit(10)->get(),
        ]);
    }

    public function update(CurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $this->save($request, $currency);

        return redirect()->route('currencies.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return redirect()->route('currencies.index')->with('error', 'Podrazumijevana valuta se ne može obrisati.');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('status', 'Valuta je obrisana.');
    }

    /** Kurs prema KM na određeni dan; fiskalizacija ga traži za strane valute. */
    public function storeRate(ExchangeRateRequest $request, Currency $currency): RedirectResponse
    {
        if ($currency->is_default) {
            return redirect()->route('currencies.index')->with('error', 'Podrazumijevana valuta nema kurs prema samoj sebi.');
        }

        $data = $request->validated();

        // whereDate, ne updateOrCreate: na SQLite-u kolona datuma nosi i vrijeme,
        // pa poređenje sa golim „Y-m-d" ne bi našlo postojeći zapis.
        $rate = ExchangeRate::where('currency', $currency->code)
            ->whereDate('rate_date', $data['rate_date'])
            ->first() ?? new ExchangeRate(['currency' => $currency->code, 'rate_date' => $data['rate_date']]);

        $rate->rate_to_bam = $data['rate_to_bam'];
        $rate->save();

        return redirect()->route('currencies.index')->with('status', 'Kurs je sačuvan.');
    }

    private function save(CurrencyRequest $request, Currency $currency): void
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $currency, $data): void {
            $currency->fill($data);

            // Tačno jedna valuta je podrazumijevana.
            if ($request->boolean('is_default')) {
                Currency::where('is_default', true)->update(['is_default' => false]);
                $currency->is_default = true;
            }

            $currency->save();
        });
    }
}
