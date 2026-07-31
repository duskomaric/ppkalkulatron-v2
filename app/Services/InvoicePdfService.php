<?php

namespace App\Services;

use App\Enums\DocumentTemplate;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Settings\CompanySettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * PDF računa, po v1 predlošcima.
 *
 * v1 renderuje kroz spatie/laravel-pdf, dakle Browsershot i headless Chrome. To na
 * telefonu ne postoji, pa v2 koristi dompdf. Predlošci su preneseni nepromijenjeni
 * jer su i u v1 pisani tabelama i DejaVu Sans fontom — dompdf ih čita kako treba.
 */
class InvoicePdfService
{
    private const VIEWS = [
        'classic' => 'pdf.invoice',
        'modern' => 'pdf.invoice-modern',
        'minimal' => 'pdf.invoice-minimal',
        'standard' => 'pdf.invoice-standard',
    ];

    public function __construct(private CompanySettings $company) {}

    public function filename(Invoice $invoice): string
    {
        return 'faktura-'.Str::slug($invoice->invoice_number).'.pdf';
    }

    public function contents(Invoice $invoice, ?DocumentTemplate $template = null): string
    {
        return $this->render($invoice, $template)->output();
    }

    public function download(Invoice $invoice, ?DocumentTemplate $template = null): Response
    {
        return $this->render($invoice, $template)->download($this->filename($invoice));
    }

    private function render(Invoice $invoice, ?DocumentTemplate $template)
    {
        $invoice->load(['client', 'items', 'fiscalRecords']);

        $template ??= $invoice->template ?? DocumentTemplate::Classic;

        return Pdf::loadView(self::VIEWS[$template->value], [
            'invoice' => $invoice,
            'company' => $this->company,
            'bankAccounts' => BankAccount::where('show_on_documents', true)->orderBy('id')->get(),
        ])->setPaper('a4');
    }
}
