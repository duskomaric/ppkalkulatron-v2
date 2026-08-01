<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScanFiscalNetworkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['range' => ['nullable', 'string', 'max:32']];
    }
}
