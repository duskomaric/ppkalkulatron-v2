<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['pin' => ['required', 'digits:4', 'confirmed']];
    }

    public function attributes(): array
    {
        return ['pin' => 'PIN'];
    }
}
