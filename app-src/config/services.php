<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'eve' => [
        'client_id' => env('EVE_CLIENT_ID'),
        'client_secret' => env('EVE_CLIENT_SECRET'),
        'redirect' => env('EVE_CALLBACK_URL', 'http://localhost:8080/auth/eve/callback'),
        'esi_base_url' => env('ESI_BASE_URL', 'https://esi.evetech.net/latest'),
        'esi_datasource' => env('ESI_DATASOURCE', 'tranquility'),
        'esi_user_agent' => env('ESI_USER_AGENT', 'EVE Pilot Dashboard/1.0'),
    ],

];
