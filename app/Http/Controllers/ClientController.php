<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(Request $request): View
    {
        return view('clients.index', [
            'clients' => Client::when($request->string('q')->toString(), fn ($q, $term) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('clients.form', ['client' => null]);
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        Client::create($request->validated());

        return redirect()->route('clients.index')->with('status', 'Klijent je kreiran.');
    }

    public function edit(Client $client): View
    {
        return view('clients.form', ['client' => $client]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        if ($client->invoices()->exists()) {
            return redirect()->route('clients.index')->with('error', 'Klijent ima račune i ne može se obrisati.');
        }

        $client->delete();

        return redirect()->route('clients.index')->with('status', 'Klijent je obrisan.');
    }
}
