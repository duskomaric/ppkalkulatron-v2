<?php

namespace App\Http\Controllers;

use App\Settings\MailSettings;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MailSettingsController extends Controller
{
    public function edit(MailSettings $settings)
    {
        return view('settings.mail', ['settings' => $settings]);
    }

    public function update(Request $request, MailSettings $settings)
    {
        $data = $request->validate([
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
        ], [], [
            'from_address' => 'adresa pošiljaoca', 'from_name' => 'ime pošiljaoca',
            'host' => 'SMTP host', 'port' => 'port', 'username' => 'korisničko ime',
            'password' => 'lozinka', 'encryption' => 'enkripcija',
        ]);

        // Prazna lozinka ne briše postojeću — polje se nikad ne popunjava nazad.
        if (blank($data['password'])) {
            unset($data['password']);
        }

        $settings->fill($data);
        $settings->save();

        return redirect()->route('settings.mail.edit')->with('status', 'Mail podešavanja su sačuvana.');
    }
}
