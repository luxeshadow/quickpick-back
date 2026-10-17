<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Events', description: 'Gestion des événements de l’utilisateur connecté')]
#[OA\Get(
    path: '/api/events',
    summary: 'Lister mes événements',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Liste des événements', content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', example: 'success'),
                new OA\Property(property: 'events', type: 'array', items: new OA\Items(ref: '#/components/schemas/Event')),
            ]
        )),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]
#[OA\Get(
    path: '/api/events/options',
    summary: 'Lister mes événements pour un select',
    description: 'Retourne uniquement id, name et status, sans pagination.',
    security: [['sanctum' => []]],
    tags: ['Events'],
    responses: [
        new OA\Response(response: 200, description: 'Options des événements'),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]
#[OA\Get(
    path: '/api/events/status/{status}',
    summary: 'Lister mes événements par statut',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'status', in: 'path', required: true, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Événements paginés correspondant au statut'),
        new OA\Response(response: 401, description: 'Non authentifié'),
        new OA\Response(response: 422, description: 'Statut invalide'),
    ]
)]
#[OA\Get(
    path: '/api/events/search',
    summary: 'Rechercher dans mes événements par nom',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [
        new OA\Parameter(name: 'search', in: 'query', required: true, schema: new OA\Schema(type: 'string', example: 'mariage')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 15)),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Résultats paginés de la recherche'),
        new OA\Response(response: 401, description: 'Non authentifié'),
        new OA\Response(response: 422, description: 'Recherche invalide'),
    ]
)]
#[OA\Post(
    path: '/api/events',
    summary: 'Créer un événement',
    security: [['sanctum' => []]],
    tags: ['Events'],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        required: ['name'],
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Mariage de Marie et Paul'),
            new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Cérémonie et réception'),
            new OA\Property(property: 'location', type: 'string', maxLength: 255, nullable: true, example: 'Paris'),
            new OA\Property(property: 'event_date', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
        ]
    )),
    responses: [
        new OA\Response(response: 201, description: 'Événement créé'),
        new OA\Response(response: 401, description: 'Non authentifié'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
    ]
)]
#[OA\Get(
    path: '/api/events/{event}',
    summary: 'Afficher un de mes événements',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'Détail de l’événement'),
        new OA\Response(response: 403, description: 'Événement appartenant à un autre utilisateur'),
        new OA\Response(response: 404, description: 'Événement introuvable'),
    ]
)]
#[OA\Patch(
    path: '/api/events/{event}',
    summary: 'Modifier partiellement un de mes événements',
    description: 'Permet de modifier uniquement le statut, un seul autre champ, ou plusieurs champs.',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Nouveau nom'),
            new OA\Property(property: 'description', type: 'string', nullable: true),
            new OA\Property(property: 'location', type: 'string', maxLength: 255, nullable: true),
            new OA\Property(property: 'event_date', type: 'string', format: 'date-time', nullable: true),
            new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
        ]
    )),
    responses: [
        new OA\Response(response: 200, description: 'Événement mis à jour'),
        new OA\Response(response: 403, description: 'Accès refusé'),
        new OA\Response(response: 422, description: 'Erreur de validation'),
    ]
)]
#[OA\Delete(
    path: '/api/events/{event}',
    summary: 'Archiver un de mes événements (suppression logique)',
    security: [['sanctum' => []]],
    tags: ['Events'],
    parameters: [new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [
        new OA\Response(response: 200, description: 'Événement archivé sans suppression définitive'),
        new OA\Response(response: 403, description: 'Accès refusé'),
        new OA\Response(response: 404, description: 'Événement introuvable'),
    ]
)]
class EventEndpoints
{
}
