<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ItTicketCreatedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $reporterName,
        public readonly string $priorityLabel,
        public readonly string $ticketNumber,
        public readonly string $ticketTitle,
        public readonly string $ticketTool,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no.reply@hrmotor.com', 'HR Motor'),
            subject: sprintf('%s ha habierto un ticket %s con número %s.', $this->reporterName, $this->priorityLabel, $this->ticketNumber),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.it-ticket-created',
            with: [
                'reporterName' => $this->reporterName,
                'priorityLabel' => $this->priorityLabel,
                'ticketNumber' => $this->ticketNumber,
                'ticketTitle' => $this->ticketTitle,
                'ticketTool' => $this->ticketTool,
            ],
        );
    }
}
