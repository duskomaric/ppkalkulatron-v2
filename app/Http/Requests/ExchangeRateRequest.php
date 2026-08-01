<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rate_to_bam' => ['required', 'numeric', 'min:0.00001'],
            'rate_date' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return ['rate_to_bam' => 'kurs', 'rate_date' => 'datum'];
    }
}
