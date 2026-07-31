<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DrawerForms;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CurrencyController extends Controller
{
    use DrawerForms;

    public function index()
    {
        return view('currencies.index', [
            'currencies' => Currency::orderByDesc('is_default')->orderBy('code')->get(),
            'rates' => ExchangeRate::orderBy('currency')->get()->keyBy('currency'),
        ]);
    }

    public function create(Request $request)
    {
        return $this->formView($request, 'currencies.form-fields', 'currencies.form', ['currency' => null, 'rates' => collect()]);
    }

    public function store(Request $request)
    {
        $this->save($request, new Currency);

        return $this->saved($request, 'currencies.index', 'Valuta je dodata.');
    }

    public function edit(Request $request, Currency $currency)
    {
        return $this->formView($request, 'currencies.form-fields', 'currencies.form', [
            'currency' => $currency,
            'rates' => ExchangeRate::where('currency', $currency->code)
                ->orderByDesc('rate_date')->limit(10)->get(),
        ]);
    }

    public function update(Request $request, Currency $currency)
    {
        $this->save($request, $currency);

        return $this->saved($request, 'currencies.index', 'Izmjene su sačuvane.');
    }

    public function destroy(Currency $currency)
    {
        if ($currency->is_default) {
            return redirect()->route('currencies.index')->with('error', 'Podrazumijevana valuta se ne može obrisati.');
        }

        $currency->delete();

        return redirect()->route('currencies.index')->with('status', 'Valuta je obrisana.');
    }

    /** Kurs prema KM na određeni dan; fiskalizacija ga traži za strane valute. */
    public function storeRate(Request $request, Currency $currency)
    {
        if ($currency->is_default) {
            return redirect()->route('currencies.index')->with('error', 'Podrazumijevana valuta nema kurs prema samoj sebi.');
        }

        $data = $request->validate([
            'rate_to_bam' => ['required', 'numeric', 'min:0.00001'],
            'rate_date' => ['required', 'date'],
        ], [], ['rate_to_bam' => 'kurs', 'rate_date' => 'datum']);

        // whereDate, ne updateOrCreate: na SQLite-u kolona datuma nosi i vrijeme,
        // pa poređenje sa golim „Y-m-d" ne bi našlo postojeći zapis.
        $rate = ExchangeRate::where('currency', $currency->code)
            ->whereDate('rate_date', $data['rate_date'])
            ->first() ?? new ExchangeRate(['currency' => $currency->code, 'rate_date' => $data['rate_date']]);

        $rate->rate_to_bam = $data['rate_to_bam'];
        $rate->save();

        return $this->saved($request, 'currencies.index', 'Kurs je sačuvan.');
    }

    private function save(Request $request, Currency $currency): void
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'size:3', Rule::unique('currencies', 'code')->ignore($currency)],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:8'],
        ], [], ['code' => 'oznaka', 'name' => 'naziv', 'symbol' => 'simbol']);

        $data['code'] = strtoupper($data['code']);

        DB::transaction(function () use ($request, $currency, $data) {
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
