<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Settings\UserSettings;

class ProfileController extends Controller
{
    public function edit(UserSettings $settings)
    {
        return view('profile', ['user' => $settings]);
    }

    public function update(UpdateProfileRequest $request, UserSettings $settings)
    {
        $data = $request->validated();

        $settings->first_name = $data['first_name'];
        $settings->last_name = $data['last_name'] ?? '';
        $settings->email = $data['email'] ?? null;
        $settings->save();

        return redirect()->route('profile.edit')->with('status', 'Podaci su sačuvani.');
    }
}
