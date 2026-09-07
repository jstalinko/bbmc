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
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'service' => env('WHATSAPP_SERVICE', 'piwapi'),
        'piwapi' => [
            'api_secret_key' => env('PIWAPI_API_SECRET_KEY'),
            'account_id' => env('PIWAPI_ACCOUNT_ID'),
        ],
        'bion' => [
            'api_url' => env('BION_API_URL', 'https://crmapis2.bion.id/api/meta'),
            'api_version' => env('BION_API_VERSION', 'v19.0'),
            'waba_id' => env('BION_WABA_ID', '112227011977777'),
            'phone_number_id' => env('BION_PHONE_NUMBER_ID', '115952861601111'),
            'access_token' => env('BION_ACCESS_TOKEN'),
            'auth_template_name' => env('BION_AUTH_TEMPLATE_NAME', 'otp_bikers_mc'),
            'template_language' => env('BION_TEMPLATE_LANGUAGE', 'en_US'),
            'auth_button_type' => env('BION_AUTH_BUTTON_TYPE', 'url'),
        ],
    ],

];
