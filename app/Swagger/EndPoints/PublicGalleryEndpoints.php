<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Public gallery', description: 'Galerie manuelle publique, indépendante du scan facial')]
#[OA\Get(path: '/api/public/access-links/{token}/media', summary: 'Parcourir les images avec scroll infini', tags: ['Public gallery'], parameters: [
    new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    new OA\Parameter(name: 'cursor', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 10, maximum: 50, default: 30)),
], responses: [
    new OA\Response(response: 200, description: 'Médias et prochain curseur'),
    new OA\Response(response: 410, description: 'Lien invalide ou abonnement inactif'),
])]
#[OA\Post(path: '/api/public/access-links/{token}/downloads', summary: 'Générer les téléchargements de la sélection', tags: ['Public gallery'], parameters: [
    new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['media_ids'], properties: [
    new OA\Property(property: 'media_ids', type: 'array', maxItems: 100, items: new OA\Items(type: 'integer')),
])), responses: [
    new OA\Response(response: 200, description: 'URLs privées valables une heure'),
    new OA\Response(response: 422, description: 'Sélection invalide'),
])]
#[OA\Post(path: '/api/public/access-links/{token}/deliveries', summary: 'Envoyer la sélection par e-mail sans scan facial', tags: ['Public gallery'], parameters: [
    new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['media_ids', 'channel', 'recipient'], properties: [
    new OA\Property(property: 'media_ids', type: 'array', maxItems: 100, items: new OA\Items(type: 'integer')),
    new OA\Property(property: 'channel', type: 'string', enum: ['email']),
    new OA\Property(property: 'recipient', type: 'string', format: 'email'),
])), responses: [
    new OA\Response(response: 202, description: 'Livraison programmée'),
    new OA\Response(response: 422, description: 'Sélection ou destinataire invalide'),
])]
class PublicGalleryEndpoints {}
