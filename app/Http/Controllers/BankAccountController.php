<?php

namespace App\Http\Controllers;

use App\Http\Requests\BankAccountRequest;
use App\Models\BankAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BankAccountController extends Controller
{
    public function index(): View
    {
        return view('bank-accounts.index', ['accounts' => BankAccount::orderBy('bank_name')->get()]);
    }

    public function create(): View
    {
        return view('bank-accounts.form', ['account' => null]);
    }

    public function store(BankAccountRequest $request): RedirectResponse
    {
        BankAccount::create($request->validated());

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je dodat.');
    }

    public function edit(BankAccount $bankAccount): View
    {
        return view('bank-accounts.form', ['account' => $bankAccount]);
    }

    public function update(BankAccountRequest $request, BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->update($request->validated());

        return redirect()->route('bank-accounts.index')->with('status', 'Izmjene su sačuvane.');
    }

    public function destroy(BankAccount $bankAccount): RedirectResponse
    {
        $bankAccount->delete();

        return redirect()->route('bank-accounts.index')->with('status', 'Bankovni račun je obrisan.');
    }
}
