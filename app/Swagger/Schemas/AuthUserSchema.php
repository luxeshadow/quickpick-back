<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'AuthUser', type: 'object', description: 'Utilisateur après validation OTP ou connexion Google ; mots de passe et remember_token exclus.', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'full_name', type: 'string'),
    new OA\Property(property: 'email', type: 'string', format: 'email'),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'status', type: 'string'),
    new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'google_id', type: 'string', nullable: true),
    new OA\Property(property: 'apple_id', type: 'string', nullable: true),
    new OA\Property(property: 'created_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'updated_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['photographer']),
])]
class AuthUserSchema {}
