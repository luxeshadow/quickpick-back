<?php

return [
    'url' => rtrim((string) env('FACE_SERVICE_URL', 'http://127.0.0.1:8001'), '/'),
    'hmac_secret' => env('FACE_SERVICE_HMAC_SECRET'),
    'model_version' => env('FACE_MODEL_VERSION', 'sface-2021dec-v1'),
    'timeout' => (int) env('FACE_SERVICE_TIMEOUT', 15),
    'search_timeout' => (int) env('FACE_SERVICE_SEARCH_TIMEOUT', 60),
];
