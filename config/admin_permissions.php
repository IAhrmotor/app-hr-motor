<?php

use App\Models\User;

return [
    'permissions' => [
        'users.manage' => [
            'label' => 'Gestión de usuarios',
            'description' => 'Altas, edición de perfiles y seguimiento del equipo.',
            'route' => 'users.index',
            'icon' => 'users',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'dealerships.manage' => [
            'label' => 'Gestión de delegaciones',
            'description' => 'Consulta, crea y organiza las delegaciones disponibles.',
            'route' => 'dealerships.index',
            'icon' => 'dealership',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'zones.manage' => [
            'label' => 'Gestión de zonas',
            'description' => 'Agrupa delegaciones en zonas y controla su reparto.',
            'route' => 'admin.zones.index',
            'icon' => 'zones',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'contacts.manage' => [
            'label' => 'Gestión de contactos',
            'description' => 'Mantén los contactos externos que aparecen en la agenda.',
            'route' => 'admin.contacts.index',
            'icon' => 'contacts',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'forum-tags.manage' => [
            'label' => 'Gestión de tags del foro comercial',
            'description' => 'Crea, edita y elimina etiquetas para organizar las dudas del foro.',
            'route' => 'admin.forum-tags.index',
            'icon' => 'tags',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'magazine.manage' => [
            'label' => 'Gestión de la revista mensual',
            'description' => 'Publica la edición mensual y actualiza la portada visible.',
            'route' => 'admin.magazine.edit',
            'icon' => 'magazine',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'bulletin.manage' => [
            'label' => 'Gestión del tablón',
            'description' => 'Publica avisos internos visibles para toda la plantilla.',
            'route' => 'admin.tablon.index',
            'icon' => 'bulletin',
            'default_roles' => [
                User::ROLE_ADMIN,
            ],
        ],
        'notifications.manage' => [
            'label' => 'Gestión de notificaciones',
            'description' => 'Envía avisos destacados a los roles que elijas.',
            'route' => 'admin.notifications.create',
            'icon' => 'notifications',
            'default_roles' => [
                User::ROLE_ADMIN,
                User::ROLE_MANAGER,
            ],
        ],
        'chat-retention-holds.manage' => [
            'label' => 'Conservación excepcional de conversación del chat',
            'description' => 'Bloquea conversaciones o usuarios para que no entren en la purga automática.',
            'route' => 'admin.chat-retention-holds.index',
            'icon' => 'chat-retention-hold',
            'default_roles' => [
                User::ROLE_ADMIN,
            ],
        ],
        'conversation-access.manage' => [
            'label' => 'Acceso justificado a conversaciones',
            'description' => 'Solicita acceso temporal y auditado a conversaciones ajenas.',
            'route' => 'admin.conversation-access.index',
            'icon' => 'conversation-access',
            'default_roles' => [
                User::ROLE_ADMIN,
            ],
        ],
        'chat-groups.manage' => [
            'label' => 'Gestión de grupos del chat',
            'description' => 'Crea, edita y elimina los grupos internos del chat.',
            'route' => 'admin.chat-groups.index',
            'icon' => 'chat-groups',
            'default_roles' => [
                User::ROLE_ADMIN,
            ],
        ],
    ],
];
