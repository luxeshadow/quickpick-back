<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Payment', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'user_id', type: 'integer'),
    new OA\Property(property: 'subscription_id', type: 'integer'),
    new OA\Property(property: 'provider', type: 'string', example: 'stripe'),
    new OA\Property(property: 'payment_method', type: 'string', example: 'card'),
    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 29.99),
    new OA\Property(property: 'currency', type: 'string', example: 'EUR'),
    new OA\Property(property: 'status', type: 'string', enum: ['pending', 'paid', 'failed', 'cancelled', 'refunded']),
    new OA\Property(property: 'external_reference', type: 'string', nullable: true),
    new OA\Property(property: 'paid_at', type: 'string', format: 'date-time', nullable: true),
], type: 'object')]
class PaymentSchema {}
