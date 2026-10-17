<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users', description: 'Profil propriétaire et gestion administrateur')]
#[OA\Patch(path: '/api/users/me', tags: ['Users'], summary: 'Modifier mon propre profil', security: [['bearerAuth' => []]],
    requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(properties: [
        new OA\Property(property: 'full_name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'phone', type: 'string', nullable: true),
    ])), responses: [new OA\Response(response: 200, description: 'Profil mis à jour', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'status', type: 'string'), new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserProfile'),
    ])), new OA\Response(response: 401, description: 'Authentification requise'), new OA\Response(response: 422, description: 'Validation échouée')])]
#[OA\Get(path: '/api/users', tags: ['Users'], summary: 'Admin : lister et rechercher les utilisateurs', security: [['bearerAuth' => []]],
    parameters: [new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'), description: 'Nom ou email, insensible à la casse'),
        new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
        new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', minimum: 1))],
    responses: [new OA\Response(response: 200, description: 'Liste paginée, abonnement actif et compteurs', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'status', type: 'string'),
        new OA\Property(property: 'users', type: 'array', items: new OA\Items(ref: '#/components/schemas/AdminUser')),
        new OA\Property(property: 'pagination', type: 'object', properties: [
            new OA\Property(property: 'current_page', type: 'integer'), new OA\Property(property: 'last_page', type: 'integer'),
            new OA\Property(property: 'per_page', type: 'integer'), new OA\Property(property: 'total', type: 'integer'),
        ]),
    ])), new OA\Response(response: 403, description: 'Admin requis')])]
#[OA\Get(path: '/api/users/search', tags: ['Users'], summary: 'Admin : alias de GET /api/users?search=...', security: [['bearerAuth' => []]],
    parameters: [new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string'))],
    responses: [new OA\Response(response: 200, description: 'Même réponse paginée que GET /api/users'), new OA\Response(response: 403, description: 'Admin requis')])]
#[OA\Get(path: '/api/users/{user}', tags: ['Users'], summary: 'Admin : détail utilisateur, abonnements, paiements et événements', security: [['bearerAuth' => []]],
    parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Détail utilisateur', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'status', type: 'string'), new OA\Property(property: 'user', ref: '#/components/schemas/AdminUser'),
    ])), new OA\Response(response: 403, description: 'Admin requis'), new OA\Response(response: 404, description: 'Utilisateur introuvable')])]
#[OA\Delete(path: '/api/users/{user}', tags: ['Users'], summary: 'Admin : supprimer définitivement un utilisateur', security: [['bearerAuth' => []]],
    description: 'Suppression Laravel avec cascades et révocation des tokens. Les objets Supabase ne sont pas supprimés.',
    parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
    responses: [new OA\Response(response: 200, description: 'Utilisateur supprimé', content: new OA\JsonContent(properties: [
        new OA\Property(property: 'status', type: 'string', example: 'success'),
        new OA\Property(property: 'message', type: 'string', example: 'Utilisateur supprimé avec succès.'),
    ])), new OA\Response(response: 403, description: 'Admin requis'), new OA\Response(response: 404, description: 'Utilisateur introuvable')])]
class UserEndpoints {}
