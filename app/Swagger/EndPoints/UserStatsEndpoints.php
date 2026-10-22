<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Get(path: '/api/users/me/stats', tags: ['Users'],
    summary: 'Mes événements et statistiques avec graphique annuel',
    description: 'Les totaux sont globaux. La liste contient au maximum les 3 derniers événements par event_date, sinon created_at, sans pagination. year filtre uniquement le graphique. Événements archivés et médias non uploadés exclus.',
    security: [['bearerAuth' => []]], parameters: [
        new OA\Parameter(name: 'year', in: 'query', description: 'Année du graphique, année courante par défaut', schema: new OA\Schema(type: 'integer', minimum: 1900, maximum: 2100)),
    ], responses: [
        new OA\Response(response: 200, description: 'Statistiques et données Chart.js', content: new OA\JsonContent(ref: '#/components/schemas/UserStats')),
        new OA\Response(response: 401, description: 'Authentification requise'),
        new OA\Response(response: 422, description: 'Année invalide'),
    ])]
class UserStatsEndpoints {}
