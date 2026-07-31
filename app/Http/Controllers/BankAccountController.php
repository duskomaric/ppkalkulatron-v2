<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    public function index()
    {
        return view('bank-accounts.index', ['accounts' => BankAccount::orderBy('bank_name')->get()]);
    }

    public function create()
    {
        return view('bank-accounts.form', ['account' => null]);
    }

    public function store(Request $request)
    {
        BankAccount::create($this->validated($request));

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je dodat.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.form', ['account' => $bankAccount]);
    }

    public function update(Request $request, BankAccount $bankAccount)
    {
        $bankAccount->update($this->validated($request));

        return redirect()->route('bank-accounts.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je obrisan.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:64'],
            'swift' => ['nullable', 'string', 'max:32'],
        ], [], [
            'bank_name' => 'naziv banke', 'account_number' => 'broj računa', 'swift' => 'SWIFT',
        ]) + ['show_on_documents' => $request->boolean('show_on_documents')];
    }
}
