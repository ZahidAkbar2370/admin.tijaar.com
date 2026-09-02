<?php

$frontendUrl = rtrim((string) env('FRONTEND_URL', ''), '/');
$extraOrigins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))));

$allowedOrigins = array_values(array_unique(array_filter(array_merge(
    [
        'https://tijaar.com',
        'https://www.tijaar.com',
        'http://tijaar.com',
        'http://www.tijaar.com',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        'http://localhost:3001',
        'http://127.0.0.1:3001',
    ],
    $frontendUrl !== '' ? [$frontendUrl] : [],
    $extraOrigins,
))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
