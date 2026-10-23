<?php
return [
    'whatsapp' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v23.0'),
        'app_id' => env('WHATSAPP_APP_ID'),
        'app_secret' => env('WHATSAPP_APP_SECRET'),
        'config_id' => env('WHATSAPP_CONFIG_ID'),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN'),
    ],
];
