<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Payments', description: 'Paiements et génération automatique des factures')]
#[OA\Get(path: '/api/payments', summary: 'Lister mes paiements et les filtrer', security: [['sanctum' => []]], tags: ['Payments'], parameters: [
    new OA\Parameter(name: 'date_from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
    new OA\Parameter(name: 'date_to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date')),
    new OA\Parameter(name: 'status', in: 'query', schema: new OA\Schema(type: 'string')),
], responses: [new OA\Response(response: 200, description: 'Liste filtrée'), new OA\Response(response: 422, description: 'Filtres invalides')])]
#[OA\Post(path: '/api/payments', summary: 'Créer un paiement et sa facture', security: [['sanctum' => []]], tags: ['Payments'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['subscription_id', 'provider', 'payment_method'], properties: [
    new OA\Property(property: 'subscription_id', type: 'integer'), new OA\Property(property: 'provider', type: 'string'),
    new OA\Property(property: 'payment_method', type: 'string'), new OA\Property(property: 'currency', type: 'string', default: 'EUR'),
    new OA\Property(property: 'external_reference', type: 'string', nullable: true),
])), responses: [new OA\Response(response: 201, description: 'Paiement et facture créés'), new OA\Response(response: 403, description: 'Accès refusé'), new OA\Response(response: 422, description: 'Erreur métier ou de validation')])]
#[OA\Get(path: '/api/payments/{payment}', summary: 'Afficher un paiement', security: [['sanctum' => []]], tags: ['Payments'], parameters: [new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Paiement'), new OA\Response(response: 403, description: 'Accès refusé')])]
#[OA\Patch(path: '/api/payments/{payment}', summary: 'Modifier un paiement (admin)', security: [['sanctum' => []]], tags: ['Payments'], parameters: [new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Paiement modifié'), new OA\Response(response: 403, description: 'Réservé aux administrateurs')])]
#[OA\Delete(path: '/api/payments/{payment}', summary: 'Annuler un paiement non encaissé', security: [['sanctum' => []]], tags: ['Payments'], parameters: [new OA\Parameter(name: 'payment', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Paiement annulé'), new OA\Response(response: 422, description: 'Paiement déjà encaissé')])]
class PaymentEndpoints {}
