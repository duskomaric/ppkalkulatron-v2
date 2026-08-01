<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendInvoiceEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'attach_pdf' => ['boolean'],
            'attach_fiscal_record_ids' => ['array'],
            'attach_fiscal_record_ids.*' => ['integer'],
        ];
    }

    public function attributes(): array
    {
        return ['to' => 'primalac', 'subject' => 'naslov', 'body' => 'poruka'];
    }
}
