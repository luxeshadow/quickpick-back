<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserSchema',
    title: 'User',
    description: 'Quick Pick user',
    type: 'object',
    properties: [
        new OA\Property(
            property: 'full_name',
            type: 'string',
            example: 'John Doe'
        ),
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'john@example.com'
        ),
        new OA\Property(
            property: 'phone',
            type: 'string',
            nullable: true,
            example: '+15145551234'
        ),
        new OA\Property(
            property: 'password',
            type: 'string',
            format: 'password',
            example: 'P@ssw0rd123'
        ),
        new OA\Property(
            property: 'password_confirmation',
            type: 'string',
            format: 'password',
            example: 'P@ssw0rd123'
        ),
    ]
)]
class UserSchema
{
}