<?php

return [
    'main' => [
        [
            'label' => 'Foro',
            'route' => 'forum.index',
        ],
        [
            'label' => 'Agenda',
            'route' => 'agenda.index',
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
            'label' => 'Reseñas',
            'route' => 'reviews.index',
        ],
        [
            'label' => 'Vídeos',
            'route' => 'videos',
        ],
    ],

    'footer' => [
        'platform' => [
            [
                'label' => 'Inicio',
                'route' => 'home',
            ],
            [
                'label' => 'Agenda',
                'route' => 'agenda.index',
            ],
            [
                'label' => 'Vídeos',
                'route' => 'videos',
            ],
            [
                'label' => 'Foro',
                'route' => 'forum.index',
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
