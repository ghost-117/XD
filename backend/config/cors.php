<?php

$frontendUrl = env('FRONTEND_URL');

return [
    'paths' => ['api/*', 'health', 'up'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_filter([
        $frontendUrl,
        'http://localhost:5174',
        'http://127.0.0.1:5174',
    ])),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => false,
];
