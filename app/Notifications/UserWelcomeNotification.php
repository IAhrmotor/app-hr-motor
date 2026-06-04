<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserWelcomeNotification extends Notification
{
    use Queueable;

    public const TYPE = 'user.welcome';

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => self::TYPE,
            'priority' => true,
            'title' => 'Bienvenido a HR Motor',
            'description' => 'Ya tienes acceso a la aplicación interna de HR Motor. Aquí encontrarás las herramientas y recursos del día a día.',
            'link_url' => route('home'),
            'link_label' => 'Ir al inicio',
            'message' => 'Bienvenido a HR Motor',
        ];
    }
}
