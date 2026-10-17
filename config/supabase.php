<?php

return [
    'url' => env('SUPABASE_URL'),
    'secret_key' => env('SUPABASE_SECRET_KEY', env('SUPABASE_SERVICE_ROLE_KEY')),
    'media_bucket' => env(
        'SUPABASE_MEDIA_BUCKET',
        env('SUPABASE_PHOTO_BUCKET', 'event-media')
    ),
];
