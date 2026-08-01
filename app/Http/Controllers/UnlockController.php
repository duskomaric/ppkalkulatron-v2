<?php

namespace App\Http\Controllers;

use App\Http\Requests\UnlockRequest;
use App\Services\PinLock;
use Illuminate\Http\Request;

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

    public function store(UnlockRequest $request)
    {
        if (! $this->pin->isEnabled()) {
            return redirect()->route('invoices.index');
        }

        $validated = $request->validated();

        if (! $this->pin->verify($validated['pin'])) {
            return redirect()->route('unlock')->withErrors(['pin' => 'Pogrešan PIN.']);
        }

        $this->pin->markUnlocked();

        return redirect()->intended(route('invoices.index'));
    }

    public function destroy(Request $request)
    {
        $request->session()->forget(PinLock::SESSION_KEY);

        return redirect()->route('unlock');
    }
}
