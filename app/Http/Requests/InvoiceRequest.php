<?php

namespace App\Http\Requests;

use App\Enums\DocumentLanguage;
use App\Enums\DocumentTemplate;
use App\Enums\PaymentType;
use App\Enums\Unit;
use App\Models\FiscalTaxRate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'currency' => ['required', 'exists:currencies,code'],
            'template' => ['required', Rule::enum(DocumentTemplate::class)],
            'language' => ['required', Rule::enum(DocumentLanguage::class)],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['nullable', 'exists:articles,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.unit' => ['required', Rule::enum(Unit::class)],
            'items.*.tax_label' => ['required', Rule::in(array_keys(FiscalTaxRate::basisPointsByLabel()))],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.after_or_equal' => 'Rok dospijeća ne može biti prije datuma računa.',
            'items.required' => 'Račun mora imati bar jednu stavku.',
            'items.min' => 'Račun mora imati bar jednu stavku.',
            'items.*.name.required' => 'Svaka stavka mora imati odabran artikal.',
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'klijent',
            'payment_type' => 'način plaćanja',
            'currency' => 'valuta',
            'template' => 'predložak',
            'language' => 'jezik',
            'date' => 'datum',
            'due_date' => 'rok dospijeća',
            'notes' => 'napomena',
            'items' => 'stavke',
            'items.*.name' => 'naziv stavke',
            'items.*.quantity' => 'količina',
            'items.*.unit_price' => 'cijena',
        ];
    }
}
