<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateMailSettingsRequest;
use App\Settings\MailSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MailSettingsController extends Controller
{
    public function edit(MailSettings $settings): View
    {
        return view('settings.mail', ['settings' => $settings]);
    }

    public function update(UpdateMailSettingsRequest $request, MailSettings $settings): RedirectResponse
    {
        $data = $request->validated();

        // Prazna lozinka ne briše postojeću — polje se nikad ne popunjava nazad.
        if (blank($data['password'])) {
            unset($data['password']);
        }

        $settings->fill($data);
        $settings->save();

        return redirect()->route('settings.mail.edit')->with('status', 'Mail podešavanja su sačuvana.');
    }
}
