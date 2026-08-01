<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:64'],
            'last_name' => ['nullable', 'string', 'max:64'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['first_name' => 'ime', 'last_name' => 'prezime', 'email' => 'email'];
    }
}
