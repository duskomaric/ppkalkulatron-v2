<?php

namespace App\Http\Requests;

use App\Enums\PaymentType;
use App\Enums\Unit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'client_id' => ['nullable', 'exists:clients,id'],
            'payment_type' => ['required', Rule::enum(PaymentType::class)],
            'date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:date'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.article_id' => ['nullable', 'exists:articles,id'],
            'items.*.name' => ['required', 'string', 'max:255'],
            'items.*.unit' => ['required', Rule::enum(Unit::class)],
            'items.*.tax_label' => ['nullable', Rule::in(array_keys(config('ofs.tax_labels')))],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'client_id' => 'klijent',
            'payment_type' => 'način plaćanja',
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
