<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Services\Diagnostics;
use App\Services\FiscalReceiptStore;
use App\Settings\CompanySettings;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Invoice $invoice,
        public string $emailSubject,
        public string $body,
        public ?string $verificationUrl = null,
        public ?string $pdfPath = null,
        /** @var int[] Id-jevi fiskalnih zapisa čije račune treba priložiti. */
        public array $attachFiscalRecordIds = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: $this->emailSubject);

        if ($this->fromAddress) {
            $envelope->from($this->fromAddress, $this->fromName ?? '');
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(view: 'emails.invoice', with: [
            'company' => app(CompanySettings::class),
        ]);
    }

    public function attachments(): array
    {
        $attachments = [];

        // Broj računa je oblika 0007/2026 — kosa crta u imenu priloga ne prolazi.
        $number = str_replace('/', '-', $this->invoice->invoice_number);

        if ($this->pdfPath && file_exists($this->pdfPath)) {
            $attachments[] = Attachment::fromPath($this->pdfPath)
                ->as('racun_'.$number.'.pdf')
                ->withMime('application/pdf');
        }

        if ($this->attachFiscalRecordIds === []) {
            return $attachments;
        }

        // Prilozi čitaju i zapis i njegovu sliku; bez ovoga se slika dovlači po
        // zapisu, jednim upitom za svaki.
        $this->invoice->loadMissing('fiscalRecords.receipt');

        $receipts = app(FiscalReceiptStore::class);

        foreach ($this->attachFiscalRecordIds as $recordId) {
            $record = $this->invoice->fiscalRecords->firstWhere('id', $recordId);

            if (! $record) {
                continue;
            }

            $binary = $receipts->binary($record);

            if ($binary === null) {
                app(Diagnostics::class)->error('Fiskalni račun nije priložen, sadržaja nema', [
                    'invoice_id' => $this->invoice->id,
                    'fiscal_record_id' => $record->id,
                ]);

                continue;
            }

            $suffix = match ($record->type->value) {
                'copy' => '-kopija',
                'refund' => '-refundacija',
                default => '',
            };

            // Uređaj vraća PNG, PDF ili HTML zavisno od podešavanja — prilog se
            // imenuje po onome što je stvarno sačuvano.
            $attachments[] = Attachment::fromData(fn () => $binary,
                'fiskalni-racun_'.$number.$suffix.'.'.$receipts->extension($record))
                ->withMime($receipts->mime($record));
        }

        return $attachments;
    }
}
