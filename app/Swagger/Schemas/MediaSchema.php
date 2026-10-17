<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Media', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'event_id', type: 'integer'),
    new OA\Property(property: 'media_type', type: 'string', enum: ['image', 'video']),
    new OA\Property(property: 'storage_path', type: 'string'),
    new OA\Property(property: 'original_filename', type: 'string'),
    new OA\Property(property: 'mime_type', type: 'string'),
    new OA\Property(property: 'file_size', type: 'integer'),
    new OA\Property(property: 'processing_status', type: 'string', enum: ['pending', 'processing', 'processed', 'failed', 'skipped']),
    new OA\Property(property: 'faces_count', type: 'integer'),
], type: 'object')]
class MediaSchema {}

