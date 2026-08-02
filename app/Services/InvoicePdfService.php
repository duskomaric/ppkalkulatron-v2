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
 * PDF računa se renderuje dompdf-om, koji je dostupan i na uređaju.
 */
class InvoicePdfService
{
    private const VIEWS = [
        'classic' => 'pdf.invoice',
        'modern' => 'pdf.invoice-modern',
        'minimal' => 'pdf.invoice-minimal',
        'standard' => 'pdf.invoice-standard',
        'programmer' => 'pdf.invoice-programmer',
        'blueprint' => 'pdf.invoice-blueprint',
        'terminal' => 'pdf.invoice-terminal',
        'protocol' => 'pdf.invoice-protocol',
        'kernel' => 'pdf.invoice-kernel',
        'terminal-light' => 'pdf.invoice-terminal-light',
        'editor' => 'pdf.invoice-editor',
        'signal' => 'pdf.invoice-signal',
        'ops-console' => 'pdf.invoice-ops-console',
        'shell' => 'pdf.invoice-shell',
        'workstation' => 'pdf.invoice-workstation',
        'terminal-paper' => 'pdf.invoice-light-lab',
        'programmer-paper' => 'pdf.invoice-light-lab',
        'programmer-grid' => 'pdf.invoice-light-lab',
        'editor-daylight' => 'pdf.invoice-light-lab',
        'editor-solarized' => 'pdf.invoice-light-lab',
        'signal-pastel' => 'pdf.invoice-light-lab',
        'signal-studio' => 'pdf.invoice-light-lab',
        'ops-ice' => 'pdf.invoice-light-lab',
        'ops-graph' => 'pdf.invoice-light-lab',
    ];

    public function __construct(private CompanySettings $company) {}

    public function filename(Invoice $invoice): string
    {
        return 'faktura-'.Str::of($invoice->invoice_number)->replace('/', '-')->slug().'.pdf';
    }

    public function contents(Invoice $invoice, ?DocumentTemplate $template = null): string
    {
        return $this->render($invoice, $template)->output();
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
        return self::VIEWS[$template->value];
    }

    private function render(Invoice $invoice, ?DocumentTemplate $template)
    {
        // `loadMissing`, ne `load`: pozivalac koji je već učitao `fiscalRecords.receipt`
        // (slanje mailom) inače dobije zapise bez slika, pa se svaka slika čita ponovo
        // — jedan upit po fiskalnom zapisu, i to nad base64 sadržajem od stotinak kilobajta.
        $invoice->loadMissing(['client', 'currencyDefinition', 'items', 'fiscalRecords']);

        $template ??= $invoice->template ?? DocumentTemplate::Classic;

        return Pdf::loadView($this->viewFor($template), [
            'invoice' => $invoice,
            'company' => $this->company,
            'bankAccounts' => BankAccount::where('show_on_documents', true)->orderBy('id')->get(),
        ])->setPaper('a4')->setOption('isFontSubsettingEnabled', true);
    }
}
