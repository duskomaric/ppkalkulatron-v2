<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
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

    public function create()
    {
        return view('clients.form', ['client' => null]);
    }

    public function store(ClientRequest $request)
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('status', 'Klijent je kreiran.');
    }

    public function edit(Client $client)
    {
        return view('clients.form', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client)
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(Client $client)
    {
        if ($client->invoices()->exists()) {
            return redirect()->route('clients.index')->with('error', 'Klijent ima račune i ne može se obrisati.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Klijent je obrisan.');
    }
}
