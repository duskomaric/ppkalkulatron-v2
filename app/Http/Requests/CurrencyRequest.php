<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CurrencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'size:3',
                Rule::unique('currencies', 'code')->ignore($this->route('currency')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'symbol' => ['required', 'string', 'max:8'],
            'is_default' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper((string) $this->input('code')),
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    public function attributes(): array
    {
        return ['code' => 'oznaka', 'name' => 'naziv', 'symbol' => 'simbol'];
    }
}
