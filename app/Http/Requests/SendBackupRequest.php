<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SendBackupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'include_invoices' => $this->has('include_invoices') ? $this->boolean('include_invoices') : true,
            'include_fiscal_documents' => $this->has('include_fiscal_documents') ? $this->boolean('include_fiscal_documents') : true,
            'include_manifest' => $this->has('include_manifest') ? $this->boolean('include_manifest') : true,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'delivery_format' => ['required', Rule::in(['zip', 'raw'])],
            'include_invoices' => ['boolean'],
            'include_fiscal_documents' => ['boolean'],
            'include_manifest' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('include_invoices') && ! $this->boolean('include_fiscal_documents')) {
                $validator->errors()->add('include_invoices', 'Odaberite PDF račune ili fiskalne dokumente.');
            }
        }];
    }
}
