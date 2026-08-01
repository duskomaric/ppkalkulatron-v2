<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_address' => ['nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:255'],
            'host' => ['nullable', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'encryption' => ['nullable', Rule::in(['tls', 'ssl'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'from_address' => 'adresa pošiljaoca',
            'from_name' => 'ime pošiljaoca',
            'host' => 'SMTP host',
            'port' => 'port',
            'username' => 'korisničko ime',
            'password' => 'lozinka',
            'encryption' => 'enkripcija',
        ];
    }
}
