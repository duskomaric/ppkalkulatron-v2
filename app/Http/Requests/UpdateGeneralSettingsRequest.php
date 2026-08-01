<?php

namespace App\Http\Requests;

use App\Enums\DocumentLanguage;
use App\Enums\DocumentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pad_zeros' => ['required', 'integer', 'min:1', 'max:10'],
            'invoice_prefix' => ['nullable', 'string', 'max:16'],
            'invoice_starting_number' => ['required', 'integer', 'min:1'],
            'reset_yearly' => ['boolean'],
            'template' => ['required', Rule::enum(DocumentTemplate::class)],
            'language' => ['required', Rule::enum(DocumentLanguage::class)],
            'invoice_due_days' => ['required', 'integer', 'min:0', 'max:365'],
            'invoice_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'pad_zeros' => 'broj nula',
            'template' => 'predložak',
            'language' => 'jezik',
            'invoice_due_days' => 'rok plaćanja',
            'invoice_notes' => 'napomena',
        ];
    }
}
