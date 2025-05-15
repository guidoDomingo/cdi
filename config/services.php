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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'icd11' => [
        'client_id' => env('ICD11_CLIENT_ID'),
        'client_secret' => env('ICD11_CLIENT_SECRET'),
        'cache_duration' => env('ICD11_CACHE_DURATION', 24), // Duración de caché en horas
        'enable_enhanced_browser' => env('ICD11_ENABLE_ENHANCED_BROWSER', true), // Habilitar servicio de navegador mejorado
        'timeout' => env('ICD11_TIMEOUT', 10), // Timeout para peticiones en segundos
    ],

];
