<?php

namespace App\Notifications;

use App\Models\CompanyChatConversation;
use App\Models\CompanyChatMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CompanyChatMessageNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly CompanyChatConversation $conversation,
        private readonly CompanyChatMessage $message,
        private readonly User $sender,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'chat.message.received',
            'priority' => false,
            'title' => 'Nuevo mensaje de ' . $this->sender->name,
            'description' => str($this->message->body)->squish()->limit(140)->toString(),
            'link_url' => route('chat.beta', ['conversation' => $this->conversation->id]),
            'link_label' => 'Abrir chat',
            'actor_name' => $this->sender->name,
            'actor_avatar_url' => $this->sender->avatar_url,
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
        ];
    }
}
