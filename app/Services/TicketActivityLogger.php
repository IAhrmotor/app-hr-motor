<?php

namespace App\Services;

use App\Models\ItTicket;
use App\Models\TicketActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

class TicketActivityLogger
{
    public function record(
        User $actor,
        ItTicket $ticket,
        string $event,
        string $title,
        array $details = [],
    ): void {
        if (! Schema::hasTable('ticket_activity_logs')) {
            return;
        }

        TicketActivityLog::query()->create([
            'it_ticket_id' => $ticket->id,
            'actor_user_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'event' => $event,
            'title' => $title,
            'details' => $details,
        ]);
    }
}
