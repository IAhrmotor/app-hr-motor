<?php

namespace App\Notifications;

use App\Models\ItTicket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ItTicketAssignedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly ItTicket $ticket,
        private readonly User $actor,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'it-ticket.assigned',
            'priority' => true,
            'title' => 'Te han asignado el ticket ' . $this->ticket->number,
            'description' => $this->ticket->title,
            'link_url' => route('tickets.show', $this->ticket),
            'link_label' => 'Abrir ticket',
            'actor_name' => $this->actor->name,
            'actor_avatar_url' => $this->actor->avatar_url,
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->number,
            'ticket_title' => $this->ticket->title,
        ];
    }
}
