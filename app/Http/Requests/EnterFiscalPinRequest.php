<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EnterFiscalPinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['security_pin' => ['required', 'digits:4']];
    }

    public function attributes(): array
    {
        return ['security_pin' => 'PIN'];
    }
}
