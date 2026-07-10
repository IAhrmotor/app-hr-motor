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

        if (in_array($event, [
            TicketActivityLog::EVENT_ASSIGNED,
            TicketActivityLog::EVENT_CLOSED,
            TicketActivityLog::EVENT_PERMANENTLY_CLOSED,
        ], true) && ! array_key_exists('assignee_schedule', $details)) {
            $assigneeId = (int) ($details['assigned_to_user_id'] ?? $ticket->assigned_to_user_id ?? 0);

            if ($assigneeId > 0) {
                $assignee = User::query()->select([
                    'id',
                    'it_monday_start',
                    'it_monday_end',
                    'it_tuesday_start',
                    'it_tuesday_end',
                    'it_wednesday_start',
                    'it_wednesday_end',
                    'it_thursday_start',
                    'it_thursday_end',
                    'it_friday_start',
                    'it_friday_end',
                ])->find($assigneeId);

                if ($assignee) {
                    $schedule = $this->buildItScheduleSnapshot($assignee);

                    if ($schedule !== null) {
                        $details['assignee_schedule'] = $schedule;
                    }
                }
            }
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

    /**
     * @return array<string, array{start:string,end:string}>|null
     */
    private function buildItScheduleSnapshot(User $user): ?array
    {
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        $snapshot = [];

        foreach ($days as $day) {
            $start = $user->{'it_' . $day . '_start'};
            $end = $user->{'it_' . $day . '_end'};

            if (blank($start) || blank($end)) {
                return null;
            }

            $snapshot[$day] = [
                'start' => (string) $start,
                'end' => (string) $end,
            ];
        }

        return $snapshot;
    }
}
