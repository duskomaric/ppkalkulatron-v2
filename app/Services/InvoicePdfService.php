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
        'terminal-matrix' => 'pdf.invoice-light-systems',
        'programmer-catalog' => 'pdf.invoice-light-systems',
        'editor-margin' => 'pdf.invoice-light-systems',
        'signal-plot' => 'pdf.invoice-light-systems',
        'ops-board' => 'pdf.invoice-light-systems',
        'git-diff' => 'pdf.invoice-git-diff',
        'network-packet' => 'pdf.invoice-network-packet',
        'vscode-dark' => 'pdf.invoice-vscode-dark',
        'vscode-light' => 'pdf.invoice-vscode-light',
        'phpstorm-dark' => 'pdf.invoice-phpstorm-dark',
        'phpstorm-light' => 'pdf.invoice-phpstorm-light',
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

    public function html(Invoice $invoice, ?DocumentTemplate $template = null): string
    {
        $invoice->loadMissing(['client', 'currencyDefinition', 'items', 'fiscalRecords']);

        $template ??= $invoice->template ?? DocumentTemplate::Classic;

        $html = view($this->viewFor($template), [
            'invoice' => $invoice,
            'company' => $this->company,
            'bankAccounts' => BankAccount::query()->where('show_on_documents', true)->orderBy('id')->get(),
        ])->render();

        $html = $this->localizedTemplateText($template, $html);

        $hasBuiltInSignatures = in_array($template, [
            DocumentTemplate::Classic,
            DocumentTemplate::Modern,
            DocumentTemplate::Minimal,
            DocumentTemplate::Standard,
        ], true);

        $footer = view('pdf.partials.app-brand', [
            'appName' => config('app.name'),
            'appVersion' => config('nativephp.version'),
            'buildCode' => config('nativephp.version_code'),
        ])->render();

        if (! $hasBuiltInSignatures) {
            $footer .= view('pdf.partials.signature')->render();
        }

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
        return self::VIEWS[$template->value];
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

    private function localizedTemplateText(DocumentTemplate $template, string $html): string
    {
        $replacements = match ($template) {
            DocumentTemplate::Terminal => [
                '$ ./billing --issue' => '▸ račun / izdan',
                'INVOICE.SESSION' => 'RAČUN',
                '// BILL_TO' => '// KUPAC',
                '// PARAMETERS' => '// PODACI',
                'RESOURCE' => 'STAVKA',
                'QTY' => 'KOL.',
                'UNIT' => 'CIJENA',
                'VALUE' => 'IZNOS',
                'subtotal' => 'osnovica',
                'TOTAL' => 'UKUPNO',
                '// NOTE' => '// NAPOMENA',
                '// PAYMENT_ENDPOINTS' => '// PLAĆANJE',
                'STATUS: ISSUED' => '✓',
            ],
            DocumentTemplate::Protocol => [
                'PROTOCOL / INVOICE' => '▣ / RAČUN',
                'DOCUMENT ID' => '#',
                'CLIENT_PROFILE' => 'KUPAC',
                'TRANSACTION' => 'PLAĆANJE',
                'ITEM' => 'STAVKA',
                'QTY' => 'KOL.',
                'RATE' => 'CIJENA',
                'AMOUNT' => 'IZNOS',
                'MESSAGE' => 'NAPOMENA',
                'PAYMENT ROUTING' => 'PLAĆANJE',
                'PROTOCOL VERIFIED' => '✓',
            ],
            DocumentTemplate::Kernel => [
                'KERNEL // BILLING' => '▣ // IZDAVALAC',
                'INVOICE' => 'RAČUN',
                'KERNEL /' => '▣ /',
            ],
            DocumentTemplate::Editor => [
                'editor / sačuvano' => '✓ /',
                'kupac.json' => 'kupac',
                'postavke.toml' => 'plaćanje',
            ],
            DocumentTemplate::Signal => [
                'SIGNAL_01 / RAČUN' => '◆ / RAČUN',
                'signal je potvrđen' => '◆ ✓',
            ],
            DocumentTemplate::OpsConsole => [
                'OPS::RAČUN / IZDAN' => '◆ / RAČUN',
                'ops konzola' => '◆',
            ],
            DocumentTemplate::Shell => [
                'shell / završeno bez greške' => '✓',
            ],
            DocumentTemplate::TerminalMatrix => [
                'TERMINAL_MATRIX' => '[●]',
            ],
            DocumentTemplate::ProgrammerCatalog => [
                'KATALOG_USLUGA' => '[■]',
            ],
            DocumentTemplate::EditorMargin => [
                'EDITOR_MARGIN' => '[◆]',
            ],
            DocumentTemplate::SignalPlot => [
                'SIGNAL_PLOT' => '[◇]',
            ],
            DocumentTemplate::OpsBoard => [
                'OPS_TABLA' => '[▲]',
            ],
            default => [],
        };

        return strtr($html, $replacements);
    }
}
