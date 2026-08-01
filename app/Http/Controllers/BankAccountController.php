<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankAccountRequest;
use App\Models\BankAccount;

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

    public function store(BankAccountRequest $request)
    {
        BankAccount::create($request->validated());

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je dodat.');
    }

    public function edit(BankAccount $bankAccount)
    {
        return view('bank-accounts.form', ['account' => $bankAccount]);
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount)
    {
        $bankAccount->update($request->validated());

        return redirect()->route('bank-accounts.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(BankAccount $bankAccount)
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je obrisan.');
    }
}
