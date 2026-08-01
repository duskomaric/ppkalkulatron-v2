<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
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
            'identification_number' => ['nullable', 'string', 'max:32'],
            'vat_number' => ['nullable', 'string', 'max:32'],
            'small_entrepreneur_note' => ['nullable', 'string', 'max:255'],
            'is_small_entrepreneur' => ['boolean'],
            'is_vat_obligor' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'naziv kompanije',
            'email' => 'email',
            'phone' => 'telefon',
            'address' => 'adresa',
            'city' => 'grad',
            'zip' => 'poštanski broj',
            'country' => 'država',
            'identification_number' => 'JIB',
            'vat_number' => 'PIB',
            'small_entrepreneur_note' => 'napomena',
        ];
    }
}
