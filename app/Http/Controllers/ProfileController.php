<?php

namespace App\Http\Controllers;

use App\Settings\UserSettings;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(UserSettings $settings)
    {
        return view('profile', ['user' => $settings]);
    }

    public function update(Request $request, UserSettings $settings)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:64'],
            'last_name' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ], [], ['first_name' => 'ime', 'last_name' => 'prezime', 'email' => 'email']);

        $settings->first_name = $data['first_name'];
        $settings->last_name = $data['last_name'] ?? '';
        $settings->email = $data['email'] ?? null;
        $settings->save();

        return redirect()->route('profile.edit')->with('status', 'Podaci su sačuvani.');
    }
}
