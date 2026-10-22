<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserStats', type: 'object', properties: [
    new OA\Property(property: 'status', type: 'string', example: 'success'),
    new OA\Property(property: 'user_id', type: 'integer'),
    new OA\Property(property: 'statistics', type: 'object', properties: [
        new OA\Property(property: 'events', type: 'integer'), new OA\Property(property: 'photos', type: 'integer'),
        new OA\Property(property: 'videos', type: 'integer'), new OA\Property(property: 'total_media', type: 'integer'),
    ]),
    new OA\Property(property: 'events', type: 'array', maxItems: 3, items: new OA\Items(type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'event_date', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'photos_count', type: 'integer'), new OA\Property(property: 'videos_count', type: 'integer'),
        new OA\Property(property: 'total_media', type: 'integer'),
    ])),
    new OA\Property(property: 'events_by_month', type: 'object', properties: [
        new OA\Property(property: 'year', type: 'integer'), new OA\Property(property: 'total', type: 'integer'),
        new OA\Property(property: 'date_basis', type: 'string', example: 'event_date_or_created_at'),
        new OA\Property(property: 'labels', type: 'array', minItems: 12, maxItems: 12, items: new OA\Items(type: 'string')),
        new OA\Property(property: 'datasets', type: 'array', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'label', type: 'string', example: 'Événements'),
            new OA\Property(property: 'data', type: 'array', minItems: 12, maxItems: 12, items: new OA\Items(type: 'integer')),
        ])),
    ]),
])]
class UserStatsSchema {}
