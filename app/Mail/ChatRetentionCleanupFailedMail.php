<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChatRetentionCleanupFailedMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param  array<int, string>  $affectedUsers
     * @param  array<int, array<string, mixed>>  $errors
     */
    public function __construct(
        public readonly string $cutoff,
        public readonly int $deletedCount,
        public readonly array $affectedUsers = [],
        public readonly array $errors = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no.reply@hrmotor.com', 'HR Motor'),
            subject: 'Error en la limpieza diaria de mensajes de chat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.chat-retention-cleanup-failed',
            with: [
                'cutoff' => $this->cutoff,
                'deletedCount' => $this->deletedCount,
                'affectedUsers' => $this->affectedUsers,
                'errors' => $this->errors,
            ],
        );
    }
}
