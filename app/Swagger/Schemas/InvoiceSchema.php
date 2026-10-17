<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Invoice', description: 'Facture enrichie prête à imprimer', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'invoice_number', type: 'string', example: 'INV-20260827-00000001'),
    new OA\Property(property: 'status', type: 'string', enum: ['issued', 'paid', 'cancelled']),
    new OA\Property(property: 'subtotal', type: 'number', format: 'float'),
    new OA\Property(property: 'tax', type: 'number', format: 'float'),
    new OA\Property(property: 'total', type: 'number', format: 'float'),
    new OA\Property(property: 'currency', type: 'string'),
    new OA\Property(property: 'client', type: 'object', properties: [
        new OA\Property(property: 'full_name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
    ]),
    new OA\Property(property: 'payment', ref: '#/components/schemas/Payment', type: 'object'),
    new OA\Property(property: 'subscription', type: 'object'),
], type: 'object')]
class InvoiceSchema {}
