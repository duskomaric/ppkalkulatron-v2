<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FindFiscalRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['request_id' => ['required', 'string', 'max:32']];
    }

    public function attributes(): array
    {
        return ['request_id' => 'RequestId'];
    }
}
