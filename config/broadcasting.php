<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'pusher'),

    'connections' => [
        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                'useTLS' => env('PUSHER_SCHEME', 'http') === 'https',
                'host' => env('PUSHER_HOST', '127.0.0.1'),
                'port' => env('PUSHER_PORT', 6001),
                'scheme' => env('PUSHER_SCHEME', 'http'),
                'encrypted' => env('PUSHER_SCHEME', 'http') === 'https',
            ],
        ],

        'reverb' => [
            'driver' => 'pusher',
            'key' => env('REVERB_APP_KEY', 'local'),
            'secret' => env('REVERB_APP_SECRET', 'local'),
            'app_id' => env('REVERB_APP_ID', '1'),
            'options' => [
                'host' => env('REVERB_HOST', '127.0.0.1'),
                'port' => env('REVERB_PORT', 6001),
                'scheme' => env('REVERB_SCHEME', 'http'),
                'encrypted' => env('REVERB_SCHEME', 'http') === 'https',
                'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],
    ],
];
