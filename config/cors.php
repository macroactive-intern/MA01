<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS)
    |--------------------------------------------------------------------------
    |
    | Set CORS_ALLOWED_ORIGINS in your environment file to a comma-separated
    | list of allowed origins, e.g.:
    |
    |   CORS_ALLOWED_ORIGINS=https://app.example.com,https://admin.example.com
    |
    | In production this must never be the wildcard '*'.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => array_filter(
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
