<?php

namespace App\Http\Requests;

use App\Enums\PaymentType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFiscalSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_url' => ['required', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255', 'required_if:device_mode,cloud'],
            'pac' => ['nullable', 'string', 'max:32', 'required_if:device_mode,cloud'],
            'security_pin' => ['nullable', 'digits:4'],
            'cashier' => ['required', 'string', 'max:64'],
            'device_mode' => ['required', Rule::in(['cloud', 'local'])],
            'receipt_layout' => ['required', Rule::in(['Slip', 'Invoice'])],
            'receipt_document_format' => ['required', Rule::in(['Png', 'Pdf', 'Html'])],
            'default_payment_type' => ['required', Rule::enum(PaymentType::class)],
            'receipt_header_text_lines' => ['nullable', 'string'],
            'wholesale' => ['boolean'],
            'print_receipt' => ['boolean'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $allowedFormats = $this->string('receipt_layout')->toString() === 'Invoice'
                ? ['Pdf', 'Html']
                : ['Png', 'Pdf', 'Html'];

            if (! in_array($this->string('receipt_document_format')->toString(), $allowedFormats, true)) {
                $validator->errors()->add(
                    'receipt_document_format',
                    'Izabrani izgled računa ne podržava odabrani format slike.',
                );
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'base_url' => 'base URL',
            'device_mode' => 'način uređaja',
            'serial_number' => 'serijski broj',
            'pac' => 'PAK',
            'receipt_layout' => 'izgled računa',
            'receipt_document_format' => 'format fiskalnog dokumenta',
        ];
    }
}
