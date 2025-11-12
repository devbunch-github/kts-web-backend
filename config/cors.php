<?php

return [

    'paths' => [
        'api/*',              // all API routes
        'api/accountant/*',   // explicitly allow accountant endpoints under /api
        'sanctum/csrf-cookie',
        'subscription/*',
        'accountant/*',
        'login',
        'logout',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];