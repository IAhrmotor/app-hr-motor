<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompanyChatConversationRead implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * @param  array<int, int>  $messageIds
     */
    public function __construct(
        public readonly int $conversationId,
        public readonly int $readerId,
        public readonly array $messageIds,
        public readonly string $readAt,
        public readonly array $targetUserIds = [],
    ) {
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('chat.conversations.' . $this->conversationId),
        ];

        foreach ($this->targetUserIds as $userId) {
            $channels[] = new PrivateChannel('chat.users.' . (int) $userId);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'chat.conversation.read';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'reader_id' => $this->readerId,
            'message_ids' => $this->messageIds,
            'read_at' => $this->readAt,
        ];
    }
}
