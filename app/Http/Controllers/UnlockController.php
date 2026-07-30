<?php

namespace App\Http\Controllers;

use App\Services\PinLock;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UnlockController extends Controller
{
    public function __construct(private PinLock $pin) {}

    public function show(Request $request)
    {
        if (! $this->pin->isEnabled() || $request->session()->has(PinLock::SESSION_KEY)) {
            return redirect()->route('invoices.index');
        }

        return view('unlock');
    }

    public function store(Request $request)
    {
        if (! $this->pin->isEnabled()) {
            return redirect()->route('invoices.index');
        }

        $validated = $request->validate(['pin' => ['required', 'string']]);

        if (! $this->pin->verify($validated['pin'])) {
            throw ValidationException::withMessages(['pin' => 'Pogrešan PIN.']);
        }

        $request->session()->put(PinLock::SESSION_KEY, true);

        return redirect()->intended(route('invoices.index'));
    }

    public function destroy(Request $request)
    {
        $request->session()->forget(PinLock::SESSION_KEY);

        return redirect()->route('unlock');
    }
}
