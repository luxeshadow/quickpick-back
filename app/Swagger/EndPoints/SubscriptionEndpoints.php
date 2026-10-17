<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Subscriptions', description: 'Gestion des abonnements')]
#[OA\Get(path: '/api/subscriptions/current', summary: 'Vérifier mon abonnement actif', security: [['sanctum' => []]], tags: ['Subscriptions'], responses: [
    new OA\Response(response: 200, description: 'État et abonnement actif éventuel'),
    new OA\Response(response: 401, description: 'Non authentifié'),
])]
#[OA\Get(path: '/api/subscriptions', summary: 'Lister mes abonnements (tous pour un admin)', security: [['sanctum' => []]], tags: ['Subscriptions'], responses: [
    new OA\Response(response: 200, description: 'Liste des abonnements'),
    new OA\Response(response: 401, description: 'Non authentifié'),
])]
#[OA\Post(path: '/api/subscriptions', summary: 'Souscrire à un plan actif', security: [['sanctum' => []]], tags: ['Subscriptions'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(required: ['subscription_plan_id'], properties: [new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 3)])), responses: [
    new OA\Response(response: 201, description: 'Abonnement créé'),
    new OA\Response(response: 422, description: 'Plan inexistant ou inactif'),
])]
#[OA\Get(path: '/api/subscriptions/{subscription}', summary: 'Afficher un abonnement autorisé', security: [['sanctum' => []]], tags: ['Subscriptions'], parameters: [new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
    new OA\Response(response: 200, description: 'Détail de l’abonnement'),
    new OA\Response(response: 403, description: 'Accès refusé'),
    new OA\Response(response: 404, description: 'Abonnement introuvable'),
])]
#[OA\Patch(path: '/api/subscriptions/{subscription}', summary: 'Modifier le plan ou le statut (admin)', security: [['sanctum' => []]], tags: ['Subscriptions'], parameters: [new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
    new OA\Property(property: 'subscription_plan_id', type: 'integer', example: 3),
    new OA\Property(property: 'status', type: 'string', enum: ['active', 'cancelled', 'expired']),
])), responses: [
    new OA\Response(response: 200, description: 'Abonnement mis à jour'),
    new OA\Response(response: 403, description: 'Réservé aux administrateurs'),
    new OA\Response(response: 422, description: 'Erreur de validation'),
])]
#[OA\Delete(path: '/api/subscriptions/{subscription}', summary: 'Annuler un abonnement sans supprimer son historique', security: [['sanctum' => []]], tags: ['Subscriptions'], parameters: [new OA\Parameter(name: 'subscription', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
    new OA\Response(response: 200, description: 'Abonnement annulé'),
    new OA\Response(response: 403, description: 'Accès refusé'),
    new OA\Response(response: 404, description: 'Abonnement introuvable'),
])]
class SubscriptionEndpoints
{
}
