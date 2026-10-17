<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Subscription plans', description: 'Gestion des plans d’abonnement')]
#[OA\Get(path: '/api/subscription-plans', summary: 'Lister les plans', security: [['sanctum' => []]], tags: ['Subscription plans'], responses: [
    new OA\Response(response: 200, description: 'Liste des plans'),
    new OA\Response(response: 401, description: 'Non authentifié'),
])]
#[OA\Post(path: '/api/subscription-plans', summary: 'Créer un plan (admin)', security: [['sanctum' => []]], tags: ['Subscription plans'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionPlanInput')), responses: [
    new OA\Response(response: 201, description: 'Plan créé'),
    new OA\Response(response: 403, description: 'Réservé aux administrateurs'),
    new OA\Response(response: 422, description: 'Erreur de validation'),
])]
#[OA\Get(path: '/api/subscription-plans/{subscription_plan}', summary: 'Afficher un plan', security: [['sanctum' => []]], tags: ['Subscription plans'], parameters: [new OA\Parameter(name: 'subscription_plan', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
    new OA\Response(response: 200, description: 'Détail du plan'),
    new OA\Response(response: 404, description: 'Plan introuvable'),
])]
#[OA\Patch(path: '/api/subscription-plans/{subscription_plan}', summary: 'Modifier partiellement un plan (admin)', security: [['sanctum' => []]], tags: ['Subscription plans'], parameters: [new OA\Parameter(name: 'subscription_plan', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/SubscriptionPlanUpdateInput')), responses: [
    new OA\Response(response: 200, description: 'Plan modifié'),
    new OA\Response(response: 403, description: 'Réservé aux administrateurs'),
    new OA\Response(response: 422, description: 'Erreur de validation'),
])]
#[OA\Delete(path: '/api/subscription-plans/{subscription_plan}', summary: 'Supprimer un plan (admin)', security: [['sanctum' => []]], tags: ['Subscription plans'], parameters: [new OA\Parameter(name: 'subscription_plan', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [
    new OA\Response(response: 200, description: 'Plan supprimé'),
    new OA\Response(response: 403, description: 'Réservé aux administrateurs'),
    new OA\Response(response: 404, description: 'Plan introuvable'),
])]
class SubscriptionPlanEndpoints
{
}
