<?php

namespace App\Http\Requests;

use App\Enums\Unit;
use App\Models\FiscalTaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', Rule::enum(Unit::class)],
            'tax_label' => ['required', Rule::in(array_keys(FiscalTaxRate::basisPointsByLabel()))],
            'gtin' => ['nullable', 'string', 'min:8', 'max:14'],
            'last_unit_price' => ['nullable', 'numeric', 'min:0'],
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
            'name' => 'naziv', 'description' => 'opis', 'unit' => 'jedinica mjere',
            'tax_label' => 'poreska oznaka', 'gtin' => 'GTIN', 'last_unit_price' => 'cijena',
        ];
    }
}
