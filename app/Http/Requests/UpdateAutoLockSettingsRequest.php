<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAutoLockSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['auto_lock_minutes' => ['required', 'integer', 'in:0,1,5,15,30,60']];
    }

    public function attributes(): array
    {
        return ['auto_lock_minutes' => 'zaključavanje'];
    }
}
