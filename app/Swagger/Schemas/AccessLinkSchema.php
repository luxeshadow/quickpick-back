<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AccessLink', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'event_id', type: 'integer'),
    new OA\Property(property: 'token', type: 'string'),
    new OA\Property(property: 'public_url', type: 'string', format: 'uri'),
    new OA\Property(property: 'source', type: 'string', enum: ['automatic', 'manual']),
    new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'is_active', type: 'boolean'),
    new OA\Property(property: 'max_scans', type: 'integer', nullable: true),
    new OA\Property(property: 'scans_count', type: 'integer'),
    new OA\Property(property: 'last_used_at', type: 'string', format: 'date-time', nullable: true),
], type: 'object')]
class AccessLinkSchema {}
