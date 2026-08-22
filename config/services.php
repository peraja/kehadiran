<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    'simpeg' => [
        'url' => rtrim(env('SIMPEG_API_URL', 'http://apps.sinjaikab.go.id/api/pegawai'), '/'),
        'timeout' => (int) env('SIMPEG_API_TIMEOUT', 10),
    ],

    'bsre' => [
        'url' => rtrim(env('BSRE_ESIGN_URL', 'http://localhost:8080/api/v2'), '/'),
        'username' => env('BSRE_AUTH_USERNAME', ''),
        'password' => env('BSRE_AUTH_PASSWORD', ''),
        'location' => env('BSRE_SIGN_LOCATION', 'Kabupaten Sinjai'),
    ],

];
