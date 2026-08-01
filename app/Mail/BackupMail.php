<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class BackupMail extends Mailable
{
    public function __construct(
        public string $archivePath,
        public string $archiveName,
        public int $invoiceCount,
        public int $fiscalDocumentCount,
        public string $deliveryFormat = 'zip',
        /** @var array<int, array{name: string, mime: string, contents: string}> */
        public array $backupAttachments = [],
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: 'Kalkulatron backup — '.now()->format('d.m.Y.'));

        if ($this->fromAddress) {
            $envelope->from($this->fromAddress, $this->fromName ?? '');
        }

        return $envelope;
    }

    public function content(): Content
    {
        return new Content(view: 'emails.backup');
    }

    public function attachments(): array
    {
        if ($this->deliveryFormat === 'raw') {
            return array_map(
                fn (array $attachment): Attachment => Attachment::fromData(
                    fn (): string => $attachment['contents'],
                    $attachment['name'],
                )->withMime($attachment['mime']),
                $this->backupAttachments,
            );
        }

        return [Attachment::fromPath($this->archivePath)
            ->as($this->archiveName)
            ->withMime('application/zip')];
    }
}
