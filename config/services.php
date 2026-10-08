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

    // Server-to-server delivery of contact requests to the StartEntreprise commercial CRM.
    'startentreprise' => [
        'leads_enabled' => (bool) env('STARTENTREPRISE_LEADS_ENABLED', false),
        'api_url' => env('STARTENTREPRISE_API_URL') ?: 'https://api.startentreprise.ma',
        'token_url' => env('STARTENTREPRISE_TOKEN_URL')
            ?: 'https://auth.startentreprise.ma/realms/startentreprise/protocol/openid-connect/token',
        'client_id' => env('STARTENTREPRISE_CLIENT_ID'),
        'client_secret' => env('STARTENTREPRISE_CLIENT_SECRET'),
        // Only requests created at or after this instant are sent automatically; older ones
        // need contact-requests:crm-backfill. Nothing is sent while it is empty.
        'leads_sync_from' => env('STARTENTREPRISE_LEADS_SYNC_FROM'),
        'connect_timeout' => (int) env('STARTENTREPRISE_CONNECT_TIMEOUT', 5),
        'timeout' => (int) env('STARTENTREPRISE_TIMEOUT', 15),
    ],

    'contact' => [
        'recipient' => env('CONTACT_RECIPIENT_EMAIL', 'majd.chraibi@gmail.com'),
    ],

];
