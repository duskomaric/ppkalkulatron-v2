<?php

namespace App\Services;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Settings\CompanySettings;
use Illuminate\Support\Str;

class InvoiceEmailSender
{
    public function __construct(
        private MailService $mail,
        private InvoicePdfService $pdf,
        private FiscalReceiptStore $receipts,
        private CompanySettings $company,
        private Diagnostics $diagnostics,
    ) {}

    /** @return array{missing_fiscal_documents: bool} */
    public function send(Invoice $invoice, string $to, string $subject, string $body, bool $attachPdf, array $fiscalRecordIds): array
    {
        $invoice->load(['client', 'items', 'fiscalRecords.receipt']);

        [$available, $missing] = collect($fiscalRecordIds)
            ->map(fn (int $id) => $invoice->fiscalRecords->firstWhere('id', $id))
            ->filter()
            ->partition(fn ($record) => $this->receipts->has($record));

        $pdfPath = $attachPdf ? $this->createPdf($invoice) : null;

        try {
            [$fromAddress, $fromName] = $this->mail->from();

            $this->mail->send($to, new InvoiceMail(
                invoice: $invoice,
                emailSubject: $subject,
                body: $body,
                verificationUrl: $invoice->fiscalRecords->last()?->verification_url,
                pdfPath: $pdfPath,
                attachFiscalRecordIds: $available->pluck('id')->values()->all(),
                fromAddress: $fromAddress,
                fromName: $fromName,
                company: $this->company,
                receipts: $this->receipts,
                diagnostics: $this->diagnostics,
            ));
        } finally {
            if ($pdfPath && is_file($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        return ['missing_fiscal_documents' => $missing->isNotEmpty()];
    }

    private function createPdf(Invoice $invoice): string
    {
        $path = storage_path('app/private/racun-'.Str::random(16).'.pdf');
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, $this->pdf->contents($invoice));

        return $path;
    }
}
