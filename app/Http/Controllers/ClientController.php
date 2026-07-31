<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\DrawerForms;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use DrawerForms;

    public function index(Request $request)
    {
        return view('clients.index', [
            'clients' => Client::when($request->string('q')->toString(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(Request $request)
    {
        return $this->formView($request, 'clients.form-fields', 'clients.form', ['client' => null]);
    }

    public function store(Request $request)
    {
        Client::create($this->validated($request));

        return $this->saved($request, 'clients.index', 'Klijent je kreiran.');
    }

    public function edit(Request $request, Client $client)
    {
        return $this->formView($request, 'clients.form-fields', 'clients.form', ['client' => $client]);
    }

    public function update(Request $request, Client $client)
    {
        $client->update($this->validated($request));

        return $this->saved($request, 'clients.index', 'Izmjene su sačuvane.');
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists()) {
            return redirect()->route('clients.index')->with('error', 'Klijent ima račune i ne može se obrisati.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Klijent je obrisan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'max:120'],
            'vat_id' => ['nullable', 'string', 'max:32'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'is_active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'naziv', 'email' => 'email', 'phone' => 'telefon', 'address' => 'adresa',
            'city' => 'grad', 'zip' => 'poštanski broj', 'country' => 'država',
            'vat_id' => 'JIB', 'tax_id' => 'PDV',
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
