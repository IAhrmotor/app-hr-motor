<?php

return [
    'main' => [
        [
            'label' => 'Vídeos',
            'route' => 'videos',
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
    ],

    'footer' => [
        'platform' => [
            [
                'label' => 'Inicio',
                'route' => 'home',
            ],
            [
                'label' => 'Vídeos',
                'route' => 'videos',
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
