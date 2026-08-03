<?php

namespace App\Services;

use App\Enums\DocumentTemplate;
use App\Models\BankAccount;
use App\Models\Invoice;
use App\Settings\CompanySettings;
use App\Settings\DocumentSettings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

/**
 * PDF računa se renderuje dompdf-om, koji je dostupan i na uređaju.
 */
class InvoicePdfService
{
    public function __construct(
        private CompanySettings $company,
        private DocumentSettings $documents,
    ) {}

    public function filename(Invoice $invoice): string
    {
        return 'faktura-'.Str::of($invoice->invoice_number)->replace('/', '-')->slug().'.pdf';
    }

    public function contents(Invoice $invoice, ?DocumentTemplate $template = null): string
    {
        return $this->render($invoice, $template)->output();
    }

    public function html(Invoice $invoice, ?DocumentTemplate $template = null): string
    {
        $invoice->loadMissing(['client', 'currencyDefinition', 'items', 'fiscalRecords']);

        // Predložak nije dio računa: uvijek se uzima onaj postavljen u podešavanjima.
        $template ??= DocumentTemplate::tryFrom($this->documents->template) ?? DocumentTemplate::Classic;

        $html = view($this->viewFor($template), [
            'invoice' => $invoice,
            'template' => $template,
            'company' => $this->company,
            'bankAccounts' => BankAccount::query()->where('show_on_documents', true)->orderBy('id')->get(),
        ])->render();

        $footer = view('pdf.partials.app-brand', [
            'appName' => config('app.name'),
            'appVersion' => config('nativephp.version'),
            'buildCode' => config('nativephp.version_code'),
        ])->render();

        $footer .= view('pdf.partials.signature')->render();

        return str_replace('</body>', $footer.'</body>', $html);
    }

    public function download(Invoice $invoice, ?DocumentTemplate $template = null): Response
    {
        return $this->render($invoice, $template)->download($this->filename($invoice));
    }

    public function inline(Invoice $invoice, ?DocumentTemplate $template = null): Response
    {
        return $this->render($invoice, $template)->stream($this->filename($invoice));
    }

    public function viewFor(DocumentTemplate $template): string
    {
        return $template->view();
    }

    private function render(Invoice $invoice, ?DocumentTemplate $template)
    {
        // `loadMissing`, ne `load`: pozivalac koji je već učitao `fiscalRecords.receipt`
        // (slanje mailom) inače dobije zapise bez slika, pa se svaka slika čita ponovo
        // — jedan upit po fiskalnom zapisu, i to nad base64 sadržajem od stotinak kilobajta.
        return Pdf::loadHtml($this->html($invoice, $template))
            ->setPaper('a4')
            ->setOption('isFontSubsettingEnabled', true);
    }
}
