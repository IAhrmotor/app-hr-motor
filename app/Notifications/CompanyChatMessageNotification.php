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
        $isGroupConversation = $this->conversation->isGroupConversation();
        $isMentionedRecipient = $notifiable instanceof User
            ? $this->message->mentionsUser($notifiable)
            : false;

        return [
            'type' => 'chat.message.received',
            'priority' => $isMentionedRecipient,
            'title' => $isMentionedRecipient
                ? 'Te han mencionado en ' . ($this->conversation->chatGroup?->name ?? 'grupo de chat')
                : ($isGroupConversation
                    ? 'Nuevo mensaje en ' . ($this->conversation->chatGroup?->name ?? 'grupo de chat')
                    : 'Nuevo mensaje de ' . $this->sender->name),
            'description' => $this->message->preview_text,
            'link_url' => route('chat.beta', ['conversation' => $this->conversation->id]),
            'link_label' => 'Abrir chat',
            'actor_name' => $this->sender->name,
            'actor_avatar_url' => $this->sender->avatar_url,
            'conversation_id' => $this->conversation->id,
            'message_id' => $this->message->id,
            'sender_id' => $this->sender->id,
            'conversation_is_group' => $isGroupConversation,
            'conversation_name' => $isGroupConversation
                ? ($this->conversation->chatGroup?->name ?? 'grupo de chat')
                : $this->sender->name,
            'chat_group_key' => $isGroupConversation ? (string) $this->conversation->id : $this->conversation->id . ':' . $this->sender->id,
        ];
    }
}
