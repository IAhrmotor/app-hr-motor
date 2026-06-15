<?php

return [
    'main' => [
        [
            'label' => 'Foro',
            'route' => 'forum.index',
        ],
        [
            'label' => 'Rankings',
            'children' => [
                [
                    'label' => 'Ranking de ventas',
                    'route' => 'leaderboard.sales',
                ],
                [
                    'label' => 'Ranking de compras',
                    'route' => 'leaderboard.purchases',
                ],
                [
                    'label' => 'Hot & Cold',
                    'route' => 'leaderboard.vehicles',
                ],
            ],
        ],
        [
            'label' => 'Web',
            'route' => 'tools.web',
        ],
        [
            'label' => 'Currículums',
            'route' => 'curriculums.index',
        ],
        [
            'label' => 'Empresa',
            'children' => [
                [
                    'label' => 'Quiénes somos',
                    'route' => 'empresa.index',
                ],
                [
                    'label' => 'Vídeos',
                    'route' => 'videos',
                ],
                [
                    'label' => 'Reseñas',
                    'route' => 'reviews.index',
                ],
                [
                    'label' => 'Informes',
                    'route' => 'tools.informes',
                ],
            ],
        ],
        [
            'label' => 'Comunicación',
            'children' => [
                [
                    'label' => 'Agenda',
                    'route' => 'agenda.index',
                ],
                [
                    'label' => 'Chat',
                    'route' => 'chat.beta',
                ],
            ],
        ],
    ],

    'footer' => [
        'platform' => [
            [
                'label' => 'Inicio',
                'route' => 'home',
            ],
            [
                'label' => 'Informes',
                'route' => 'tools.informes',
            ],
            [
                'label' => 'Agenda',
                'route' => 'agenda.index',
            ],
            [
                'label' => 'Chat',
                'route' => 'chat.beta',
            ],
            [
                'label' => 'Currículums',
                'route' => 'curriculums.index',
            ],
            [
                'label' => 'Vídeos',
                'route' => 'videos',
            ],
            [
                'label' => 'Quiénes somos',
                'route' => 'empresa.index',
            ],
            [
                'label' => 'Foro',
                'route' => 'forum.index',
            ],
            [
                'label' => 'Reseñas',
                'route' => 'reviews.index',
            ],
            [
                'label' => 'Ranking ventas',
                'route' => 'leaderboard.sales',
            ],
            [
                'label' => 'Ranking compras',
                'route' => 'leaderboard.purchases',
            ],
            [
                'label' => 'Ranking coches',
                'route' => 'leaderboard.vehicles',
            ],
        ],
    ],
];
