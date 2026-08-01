<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'bank_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:64'],
            'swift' => ['nullable', 'string', 'max:32'],
            'show_on_documents' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['show_on_documents' => $this->boolean('show_on_documents')]);
    }

    public function attributes(): array
    {
        return [
            'bank_name' => 'naziv banke', 'account_number' => 'broj računa', 'swift' => 'SWIFT',
        ];
    }
}
