<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Invoices', description: 'Factures prêtes à afficher ou imprimer')]
#[OA\Get(path: '/api/invoices', summary: 'Lister mes factures', security: [['sanctum' => []]], tags: ['Invoices'], responses: [new OA\Response(response: 200, description: 'Factures enrichies')])]
#[OA\Get(path: '/api/invoices/{invoice}', summary: 'Afficher une facture prête à imprimer', security: [['sanctum' => []]], tags: ['Invoices'], parameters: [new OA\Parameter(name: 'invoice', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))], responses: [new OA\Response(response: 200, description: 'Facture avec client, paiement, abonnement et plan'), new OA\Response(response: 403, description: 'Accès refusé')])]
class InvoiceEndpoints {}
