<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'GuestScanResult', type: 'object', required: ['status', 'scan', 'media'], properties: [
    new OA\Property(property: 'status', type: 'string', example: 'success'),
    new OA\Property(property: 'scan', type: 'object', required: ['id', 'status', 'matches_count'], properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'status', type: 'string', enum: ['matched', 'no_match']),
        new OA\Property(property: 'matches_count', type: 'integer'),
    ]),
    new OA\Property(property: 'media', type: 'array', items: new OA\Items(type: 'object', required: ['id', 'filename', 'width', 'height', 'similarity', 'url'], properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'filename', type: 'string'),
        new OA\Property(property: 'width', type: 'integer', nullable: true),
        new OA\Property(property: 'height', type: 'integer', nullable: true),
        new OA\Property(property: 'similarity', type: 'number', description: 'Score cosinus, pas une probabilité'),
        new OA\Property(property: 'url', type: 'string', format: 'uri', description: 'URL privée signée, valable une heure'),
    ])),
])]
class GuestScanSchema {}
