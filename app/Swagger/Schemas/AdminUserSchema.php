<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'UserProfile', type: 'object', properties: [
    new OA\Property(property: 'id', type: 'integer'),
    new OA\Property(property: 'full_name', type: 'string'),
    new OA\Property(property: 'email', type: 'string', format: 'email'),
    new OA\Property(property: 'phone', type: 'string', nullable: true),
    new OA\Property(property: 'status', type: 'string'),
    new OA\Property(property: 'registered_at', type: 'string', format: 'date-time'),
    new OA\Property(property: 'email_verified_at', type: 'string', format: 'date-time', nullable: true),
    new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), description: 'Présent dans les réponses admin'),
])]
#[OA\Schema(schema: 'AdminUser', allOf: [new OA\Schema(ref: '#/components/schemas/UserProfile'),
    new OA\Schema(type: 'object', properties: [
        new OA\Property(property: 'current_subscription', type: 'object', nullable: true, description: 'Abonnement actif avec subscription_plan, starts_at et ends_at ; null si aucun'),
        new OA\Property(property: 'statistics', type: 'object', properties: [
            new OA\Property(property: 'events', type: 'integer'), new OA\Property(property: 'photos', type: 'integer'),
            new OA\Property(property: 'videos', type: 'integer'), new OA\Property(property: 'total_media', type: 'integer'),
        ]),
        new OA\Property(property: 'subscriptions', type: 'array', description: 'Détail uniquement : historique avec plans et dates', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'payments', type: 'array', description: 'Détail uniquement : montants, devises, états et dates des paiements', items: new OA\Items(type: 'object')),
        new OA\Property(property: 'events', type: 'array', description: 'Détail uniquement ; événements non archivés', items: new OA\Items(type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'), new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'event_date', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'photos_count', type: 'integer'), new OA\Property(property: 'videos_count', type: 'integer'),
            new OA\Property(property: 'total_media', type: 'integer'),
        ])),
    ]),
])]
class AdminUserSchema {}
