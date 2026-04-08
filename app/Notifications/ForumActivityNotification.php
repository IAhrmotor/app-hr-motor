<?php

namespace App\Notifications;

use App\Models\ForumThread;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ForumActivityNotification extends Notification
{
    use Queueable;

    public const TYPE_THREAD_CREATED = 'forum.thread.created';

    public const TYPE_REPLY_CREATED = 'forum.reply.created';

    public function __construct(
        private readonly string $type,
        private readonly ForumThread $thread,
        private readonly User $actor,
    ) {
    }

    public static function threadCreated(ForumThread $thread, User $actor): self
    {
        return new self(self::TYPE_THREAD_CREATED, $thread, $actor);
    }

    public static function replyCreated(ForumThread $thread, User $actor): self
    {
        return new self(self::TYPE_REPLY_CREATED, $thread, $actor);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message(),
            'actor_name' => $this->actor->name,
            'actor_avatar_url' => $this->actor->avatar_url,
            'thread_id' => $this->thread->id,
            'thread_title' => $this->thread->title,
            'thread_url' => route('forum.show', $this->thread),
        ];
    }

    private function message(): string
    {
        return match ($this->type) {
            self::TYPE_THREAD_CREATED => $this->actor->name . ' tiene una nueva duda',
            self::TYPE_REPLY_CREATED => $this->actor->name . ' ha respondido a tu consulta',
            default => $this->actor->name . ' ha actualizado el foro',
        };
    }
}
