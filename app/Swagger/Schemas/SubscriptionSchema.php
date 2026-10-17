<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Subscription',
    required: ['id', 'user_id', 'subscription_plan_id', 'status', 'starts_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'user_id', type: 'integer', example: 12),
        new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 3),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'cancelled', 'expired'], example: 'active'),
        new OA\Property(property: 'starts_at', type: 'string', format: 'date-time', example: '2026-08-27T10:00:00Z'),
        new OA\Property(property: 'ends_at', type: 'string', format: 'date-time', nullable: true, example: '2026-09-26T10:00:00Z'),
        new OA\Property(property: 'cancelled_at', type: 'string', format: 'date-time', nullable: true),
        new OA\Property(property: 'subscription_plan', ref: '#/components/schemas/SubscriptionPlan', type: 'object'),
    ],
    type: 'object'
)]
class SubscriptionSchema
{
}
