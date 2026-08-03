<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class UserOnboardingNotification extends Notification
{
    use Queueable;

    public const TYPE_AGENDA = 'onboarding.agenda';
    public const TYPE_SALES_RANKING = 'onboarding.sales_ranking';
    public const TYPE_VEHICLE_RANKING = 'onboarding.vehicle_ranking';
    public const TYPE_WEB = 'onboarding.web';
    public const TYPE_CHAT = 'onboarding.chat';
    public const TYPE_VIDEOS = 'onboarding.videos';

    private function __construct(
        private readonly string $type,
        private readonly string $title,
        private readonly string $description,
        private readonly string $linkUrl,
        private readonly string $linkLabel,
    ) {
    }

    public static function agenda(): self
    {
        return new self(
            self::TYPE_AGENDA,
            'Agenda interna',
            'Usa la agenda para consultar el correo, el webphone, la extensión y el resto de datos de contacto del equipo.',
            route('agenda.index'),
            'Abrir agenda',
        );
    }

    public static function salesRanking(): self
    {
        return new self(
            self::TYPE_SALES_RANKING,
            'Ranking de ventas y compras',
            'En este ranking puedes ver las compras y ventas de todos los compañeros de la empresa.',
            route('leaderboard.sales'),
            'Ver ranking',
        );
    }

    public static function vehicleRanking(): self
    {
        return new self(
            self::TYPE_VEHICLE_RANKING,
            'Ranking Hot & Cold',
            'Aquí verás los vehículos con más y menos leads asociados para detectar qué modelos se mueven mejor.',
            route('leaderboard.vehicles'),
            'Abrir ranking',
        );
    }

    public static function web(): self
    {
        return new self(
            self::TYPE_WEB,
            'Web HR Motor',
            'Tienes la web de la empresa centralizada aquí para no salir de la app cuando la necesites.',
            route('tools.web'),
            'Abrir web',
        );
    }

    public static function chat(): self
    {
        return new self(
            self::TYPE_CHAT,
            'Chat interno',
            'Este es el chat interno de la empresa para hablar con el equipo de forma rápida y centralizada.',
            route('chat.beta'),
            'Abrir chat',
        );
    }

    public static function videos(): self
    {
        return new self(
            self::TYPE_VIDEOS,
            'Vídeos de formación',
            'Vídeos de formación para refrescar procesos y resolver dudas cuando lo necesites.',
            route('videos'),
            'Ver vídeos',
        );
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => $this->type,
            'priority' => false,
            'title' => $this->title,
            'description' => $this->description,
            'link_url' => $this->linkUrl,
            'link_label' => $this->linkLabel,
            'message' => $this->title,
        ];
    }
}
