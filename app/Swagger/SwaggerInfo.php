<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'Quick Pick API',
    version: '1.0.0',
    description: 'Quick Pick backend API'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Local server'
)]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Bearer token'
)]
class SwaggerInfo
{
}
