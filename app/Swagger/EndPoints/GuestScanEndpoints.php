<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Guest scans', description: 'Recherche des photos à partir d’un selfie')]
#[OA\Post(path: '/api/public/access-links/{token}/guest-scans', tags: ['Guest scans'],
    summary: 'Envoyer un selfie et recevoir les photos correspondantes',
    description: 'Recherche synchrone. Un seul visage. Selfie non conservé. Chaque tentative admise consomme un scan du lien. Maximum 100 correspondances par défaut.',
    parameters: [new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string', pattern: '^[A-Fa-f0-9]{64}$'))],
    requestBody: new OA\RequestBody(required: true, content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(type: 'object', required: ['selfie', 'consent'], properties: [
        new OA\Property(property: 'selfie', type: 'string', format: 'binary', description: 'JPEG, PNG ou WebP ; 5 Mo maximum ; 4096 pixels maximum par côté'),
        new OA\Property(property: 'consent', type: 'string', enum: ['1'], description: 'Consentement explicite à la recherche faciale'),
    ]))),
    responses: [
        new OA\Response(response: 200, description: 'Résultats ; media vide si aucune correspondance', content: new OA\JsonContent(ref: '#/components/schemas/GuestScanResult')),
        new OA\Response(response: 410, description: 'Lien invalide, expiré, quota atteint ou abonnement inactif'),
        new OA\Response(response: 422, description: 'Image, consentement ou nombre de visages invalide'),
        new OA\Response(response: 429, description: 'Maximum 5 requêtes par minute'),
        new OA\Response(response: 503, description: 'Microservice, modèles ou recherche Supabase indisponibles'),
    ])]
class GuestScanEndpoints {}
