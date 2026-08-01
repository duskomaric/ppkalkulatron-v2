<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class DiagnosticsMail extends Mailable
{
    public function __construct(
        public string $reportPath,
        public string $reportName,
        public ?string $fromAddress = null,
        public ?string $fromName = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $envelope = new Envelope(subject: 'ppKalkulatron — dijagnostika '.now()->format('d.m.Y.'));

        if ($this->fromAddress) {
            $envelope->from($this->fromAddress, $this->fromName ?? '');
        }

        return $envelope;
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(view: 'emails.diagnostics');
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [Attachment::fromPath($this->reportPath)
            ->as($this->reportName)
            ->withMime('text/plain')];
    }
}
