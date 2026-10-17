<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'SubscriptionPlan',
    required: ['id', 'name', 'price', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Pro'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', example: 29.99),
        new OA\Property(property: 'duration_days', type: 'integer', nullable: true, example: 30),
        new OA\Property(property: 'max_events', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'max_photos', type: 'integer', nullable: true, example: 5000),
        new OA\Property(property: 'accept_video', type: 'boolean', example: false),
        new OA\Property(property: 'max_videos', type: 'integer', nullable: true, example: 10),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SubscriptionPlanInput',
    required: ['name', 'price'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Pro'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'duration_days', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'max_events', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'max_photos', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'accept_video', type: 'boolean', default: false),
        new OA\Property(property: 'max_videos', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'is_active', type: 'boolean', default: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'SubscriptionPlanUpdateInput',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'description', type: 'string', nullable: true),
        new OA\Property(property: 'price', type: 'number', format: 'float', minimum: 0),
        new OA\Property(property: 'duration_days', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'max_events', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'max_photos', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'accept_video', type: 'boolean'),
        new OA\Property(property: 'max_videos', type: 'integer', nullable: true, minimum: 1),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
    type: 'object'
)]
class SubscriptionPlanSchema
{
}
