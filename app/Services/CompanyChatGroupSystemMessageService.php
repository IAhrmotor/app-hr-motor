<?php

namespace App\Services;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatGroup;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanyChatGroupSystemMessageService
{
    public function recordParticipantAdded(CompanyChatGroup $group, User $participant, ?User $actor = null): void
    {
        $this->recordMembershipChange($group, $participant, true, $actor);
    }

    public function recordParticipantRemoved(CompanyChatGroup $group, User $participant, ?User $actor = null): void
    {
        $this->recordMembershipChange($group, $participant, false, $actor);
    }

    private function recordMembershipChange(CompanyChatGroup $group, User $participant, bool $isAdded, ?User $actor = null): void
    {
        $body = $this->buildMessageBody($group, $participant, $isAdded, $actor);

        DB::transaction(function () use ($group, $body): void {
            $conversation = CompanyChatConversation::query()->firstOrCreate([
                'company_chat_group_id' => $group->id,
            ]);

            $message = $conversation->messages()->create([
                'sender_id' => null,
                'body' => $body,
                'is_system' => true,
                'read_at' => null,
            ]);

            $conversation->forceFill([
                'last_message_at' => $message->created_at ?? now(),
                'last_message_excerpt' => $message->preview_text,
            ])->save();
        });
    }

    private function buildMessageBody(CompanyChatGroup $group, User $participant, bool $isAdded, ?User $actor = null): string
    {
        $participantName = $participant->name ?: 'Usuario';

        return $isAdded
            ? "{$participantName} se ha unido al grupo."
            : "{$participantName} salió del grupo.";
    }
}
