<?php

namespace App\Notifications;

use App\Models\ItTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ItTicketMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ItTicket $ticket,
        private readonly User $actor,
        private readonly string $messageBody,
        private readonly bool $isClosed = false,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->isClosed ? 'it-ticket.closed' : 'it-ticket.reply.received',
            'priority' => false,
            'title' => $this->isClosed
                ? 'El ticket ' . $this->ticket->number . ' se ha cerrado'
                : 'Nuevo mensaje en el ticket ' . $this->ticket->number,
            'description' => $this->notificationDescription($notifiable),
            'link_url' => route('tickets.show', $this->ticket),
            'link_label' => 'Abrir ticket',
            'actor_name' => $this->actor->name,
            'actor_avatar_url' => $this->actor->avatar_url,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'ticket_title' => $this->ticket->title,
        ];
    }

    private function notificationDescription(object $notifiable): string
    {
        if ($this->isClosed) {
            return 'El ticket se ha cerrado con un nuevo comentario.';
        }

        $body = trim($this->messageBody);

        if ($body !== '') {
            return Str::squish(Str::limit($body, 140));
        }

        return 'Se han adjuntado imágenes en el hilo.';
    }
}
