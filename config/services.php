<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

    'fcm' => [
        'enabled' => filter_var(env('FCM_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'project_id' => env('FCM_PROJECT_ID'),
        'api_key' => env('FCM_API_KEY'),
        'auth_domain' => env('FCM_AUTH_DOMAIN'),
        'storage_bucket' => env('FCM_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FCM_MESSAGING_SENDER_ID'),
        'app_id' => env('FCM_APP_ID'),
        'vapid_key' => env('FCM_VAPID_KEY'),
        'credentials_path' => env('FCM_CREDENTIALS_PATH', storage_path('app/private/firebase-service-account.json')),
    ],

];
