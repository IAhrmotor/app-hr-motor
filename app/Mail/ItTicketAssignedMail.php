<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ItTicketAssignedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $assigneeName,
        public readonly string $actorName,
        public readonly string $ticketNumber,
        public readonly string $ticketTitle,
        public readonly string $priorityLabel,
        public readonly string $ticketTool,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no.reply@hrmotor.com', 'HR Motor'),
            subject: sprintf(
                'Te han asignado el ticket %s (%s - %s)',
                $this->ticketNumber,
                $this->priorityLabel,
                $this->ticketTool
            ),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.it-ticket-assigned',
            with: [
                'assigneeName' => $this->assigneeName,
                'actorName' => $this->actorName,
                'ticketNumber' => $this->ticketNumber,
                'ticketTitle' => $this->ticketTitle,
                'priorityLabel' => $this->priorityLabel,
                'ticketTool' => $this->ticketTool,
            ],
        );
    }
}
