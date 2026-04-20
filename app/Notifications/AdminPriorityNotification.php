<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AdminPriorityNotification extends Notification
{
    use Queueable;

    public const TYPE_ADMIN_ANNOUNCEMENT = 'admin.announcement';

    public function __construct(
        private readonly string $title,
        private readonly string $description,
        private readonly ?string $linkUrl,
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
            'type' => self::TYPE_ADMIN_ANNOUNCEMENT,
            'priority' => true,
            'title' => $this->title,
            'description' => $this->description,
            'link_url' => $this->linkUrl,
            'link_label' => $this->linkUrl ? 'Abrir' : null,
            'actor_name' => $this->actor->name,
            'actor_avatar_url' => $this->actor->avatar_url,
        ];
    }
}
