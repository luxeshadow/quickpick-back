<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Event',
    required: ['id', 'user_id', 'name', 'status', 'media_count'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 12),
        new OA\Property(property: 'name', type: 'string', example: 'Mariage de Marie et Paul'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cérémonie et réception'),
        new OA\Property(property: 'location', type: 'string', nullable: true, example: 'Paris'),
        new OA\Property(property: 'event_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
        new OA\Property(property: 'media_count', type: 'integer', example: 0),
        new OA\Property(property: 'media_status', type: 'string', enum: ['empty', 'uploading', 'processing', 'completed', 'partial_failure', 'failed']),
        new OA\Property(property: 'expected_media_count', type: 'integer', example: 100),
        new OA\Property(property: 'uploaded_media_count', type: 'integer', example: 100),
        new OA\Property(property: 'processed_media_count', type: 'integer', example: 98),
        new OA\Property(property: 'failed_media_count', type: 'integer', example: 2),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'updated_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'deleted_at', type: 'string', format: 'date-time', nullable: true),
    ],
    type: 'object'
)]

class EventSchema
{
}

