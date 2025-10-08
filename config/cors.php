<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'subscription/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:5173',   // Vite dev server
        'http://127.0.0.1:5173',   // sometimes different host
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
