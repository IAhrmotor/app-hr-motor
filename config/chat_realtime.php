<?php

$appKey = env('CHAT_REALTIME_APP_KEY', env('PUSHER_APP_KEY', env('REVERB_APP_KEY', '')));
$host = env('CHAT_REALTIME_HOST', env('PUSHER_HOST', env('REVERB_HOST', '')));
$broadcastConnection = strtolower((string) env('BROADCAST_CONNECTION', 'null'));
$enabled = env('CHAT_REALTIME_ENABLED');

if ($enabled === null) {
    $enabled = in_array($broadcastConnection, ['pusher', 'reverb'], true)
        && trim((string) $appKey) !== ''
        && trim((string) $host) !== '';
}

return [
    'enabled' => filter_var($enabled, FILTER_VALIDATE_BOOL),
    'auth_endpoint' => env('CHAT_REALTIME_AUTH_ENDPOINT', '/broadcasting/auth'),
    'app_key' => $appKey,
    'host' => $host,
    'port' => env('CHAT_REALTIME_PORT', env('PUSHER_PORT', env('REVERB_PORT', '443'))),
    'scheme' => env('CHAT_REALTIME_SCHEME', env('PUSHER_SCHEME', env('REVERB_SCHEME', 'https'))),
    'path' => env('CHAT_REALTIME_PATH', env('PUSHER_PATH', '')),
    'channel_prefix' => env('CHAT_REALTIME_CHANNEL_PREFIX', 'private-'),
];
