<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Media', description: 'Envoi et consultation des images et vidéos d’un événement')]
#[OA\Get(path: '/api/events/{event}/media', summary: 'Lister les médias d’un événement', security: [['sanctum' => []]], tags: ['Media'], parameters: [
    new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    new OA\Parameter(name: 'type', description: 'Filtre facultatif', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['image', 'video'])),
], responses: [
    new OA\Response(response: 200, description: 'Liste des médias'),
    new OA\Response(response: 403, description: 'Accès refusé'),
    new OA\Response(response: 422, description: 'Type invalide'),
])]
#[OA\Post(path: '/api/events/{event}/media', summary: 'Envoyer un lot d’images ou de vidéos', description: 'Chaque appel crée un lot et retourne les noms des fichiers envoyés et échoués. Le lien d’accès reste créé manuellement.', security: [['sanctum' => []]], tags: ['Media'], parameters: [
    new OA\Parameter(name: 'event', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
], requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['files'], properties: [
    new OA\Property(property: 'files', type: 'array', maxItems: 1000, items: new OA\Items(type: 'string', format: 'binary')),
]))), responses: [
    new OA\Response(response: 201, description: 'Tous les médias du lot ont été envoyés'),
    new OA\Response(response: 207, description: 'Envoi partiel avec les listes uploaded_files et failed_files'),
    new OA\Response(response: 401, description: 'Non authentifié'),
    new OA\Response(response: 403, description: 'Événement appartenant à un autre utilisateur'),
    new OA\Response(response: 422, description: 'Fichier invalide, abonnement inactif ou quota dépassé'),
])]
class MediaEndpoints {}

