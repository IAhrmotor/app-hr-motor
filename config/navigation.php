<?php

return [
    'main' => [
        [
            'label' => 'Foro',
            'route' => 'forum.index',
        ],
        [
            'label' => 'Ranking',
            'children' => [
                [
                    'label' => 'Ranking de ventas',
                    'route' => 'leaderboard.sales',
                ],
                [
                    'label' => 'Ranking de compras',
                    'route' => 'leaderboard.purchases',
                ],
            ],
        ],
        [
            'label' => 'Hot & Cold',
            'route' => 'leaderboard.vehicles',
        ],
        [
            'label' => 'Videos',
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
                'label' => 'Videos',
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
