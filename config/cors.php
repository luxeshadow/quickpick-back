<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => array_values(array_unique(array_merge(array_filter(array_map(
        'trim',
        explode(',', env('FRONTEND_ORIGINS', 'http://localhost:3000,http://192.168.0.173:3000'))
    )), ['https://cool-sundae-186349.netlify.app']))),
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
