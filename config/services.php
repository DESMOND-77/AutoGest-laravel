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

    // No Gabonese SMS provider is wired up yet — see SmsGateway's docblock.
    // 'log' (the only driver today) writes to the log instead of sending.
    'sms' => [
        'driver' => env('SMS_DRIVER', 'log'),
    ],

    'student_registration' => [
        // How long a freshly generated public registration link stays
        // usable before it needs to be regenerated. See
        // docs/features/student-public-registration.md.
        'link_ttl_days' => env('STUDENT_REGISTRATION_LINK_TTL_DAYS', 90),
    ],

];
