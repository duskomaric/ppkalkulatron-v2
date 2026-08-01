<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:64'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:120'],
            'zip' => ['nullable', 'string', 'max:16'],
            'country' => ['nullable', 'string', 'max:120'],
            'vat_id' => ['nullable', 'string', 'max:32'],
            'tax_id' => ['nullable', 'string', 'max:32'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'naziv', 'email' => 'email', 'phone' => 'telefon', 'address' => 'adresa',
            'city' => 'grad', 'zip' => 'poštanski broj', 'country' => 'država',
            'vat_id' => 'JIB', 'tax_id' => 'PDV',
        ];
    }
}
