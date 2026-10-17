<?php

namespace App\Swagger\Endpoints;

use OpenApi\Attributes as OA;

#[OA\Tag(
    name: 'Auth',
    description: 'Authentication management'
)]

#[OA\Post(
    path: '/api/auth/register',
    tags: ['Auth'],
    summary: 'Register a new user',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            ref: '#/components/schemas/UserSchema'
        )
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'User created and OTP sent',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'success'
                    ),
                    new OA\Property(
                        property: 'message',
                        type: 'string',
                        example: 'Compte créé. Un code de vérification a été envoyé.'
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'john@example.com'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]

#[OA\Post(
    path: '/api/auth/login',
    tags: ['Auth'],
    summary: 'Login with email and password',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'john@example.com'
                ),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    example: 'P@ssw0rd123'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Credentials verified and OTP sent',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'success'
                    ),
                    new OA\Property(
                        property: 'message',
                        type: 'string',
                        example: 'Un code de vérification a été envoyé.'
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'john@example.com'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Invalid credentials'
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]

#[OA\Post(
    path: '/api/auth/verify-otp',
    tags: ['Auth'],
    summary: 'Verify OTP for register or login',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'otp'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'john@example.com'
                ),
                new OA\Property(
                    property: 'otp',
                    type: 'string',
                    example: '583921'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'OTP verified successfully',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'success'
                    ),
                    new OA\Property(
                        property: 'message',
                        type: 'string',
                        example: 'Code vérifié avec succès.'
                    ),
                    new OA\Property(
                        property: 'user',
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'token',
                        type: 'string',
                        example: '1|xxxxxxxxxxxxxxxxxxxxxxxx'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]

#[OA\Post(
    path: '/api/auth/resend-otp',
    tags: ['Auth'],
    summary: 'Renvoyer un nouveau code OTP',
    description: 'L’ancien code est supprimé et devient immédiatement inutilisable.',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(
                    property: 'email',
                    type: 'string',
                    format: 'email',
                    example: 'john@example.com'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Nouveau code envoyé'),
        new OA\Response(response: 422, description: 'Adresse e-mail invalide ou inconnue'),
        new OA\Response(response: 429, description: 'Trop de demandes'),
    ]
)]

#[OA\Post(
    path: '/api/auth/forgot-password',
    tags: ['Auth'],
    summary: 'Envoyer le lien magique de réinitialisation',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [new OA\Property(property: 'email', type: 'string', format: 'email')]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Lien envoyé par e-mail'),
        new OA\Response(response: 422, description: 'Adresse e-mail invalide'),
        new OA\Response(response: 429, description: 'Trop de demandes'),
    ]
)]

#[OA\Post(
    path: '/api/auth/reset-password',
    tags: ['Auth'],
    summary: 'Enregistrer le nouveau mot de passe',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'token', 'password', 'password_confirmation'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'token', type: 'string'),
                new OA\Property(property: 'password', type: 'string', format: 'password'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Mot de passe réinitialisé'),
        new OA\Response(response: 422, description: 'Lien invalide, expiré ou mot de passe identique à l’ancien'),
        new OA\Response(response: 429, description: 'Trop de demandes'),
    ]
)]

#[OA\Post(
    path: '/api/auth/logout',
    tags: ['Auth'],
    summary: 'Déconnecter l’utilisateur',
    description: 'Supprime uniquement le token utilisé pour cette requête.',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Déconnexion réussie',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'status', type: 'string', example: 'success'),
                    new OA\Property(property: 'message', type: 'string', example: 'Déconnexion réussie.'),
                ],
                type: 'object'
            )
        ),
        new OA\Response(response: 401, description: 'Non authentifié'),
    ]
)]

#[OA\Post(
    path: '/api/auth/login/google',
    tags: ['Auth'],
    summary: 'Login with Google',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['token'],
            properties: [
                new OA\Property(
                    property: 'token',
                    type: 'string',
                    description: 'Google ID token',
                    example: 'google-id-token-fourni-par-le-client'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'Google login successful',
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'status',
                        type: 'string',
                        example: 'success'
                    ),
                    new OA\Property(
                        property: 'user',
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'token',
                        type: 'string',
                        example: '1|xxxxxxxxxxxxxxxxxxxxxxxx'
                    ),
                ],
                type: 'object'
            )
        ),
        new OA\Response(
            response: 401,
            description: 'Invalid Google token'
        ),
        new OA\Response(
            response: 422,
            description: 'Validation error'
        ),
    ]
)]

class AuthEndpoints
{
}
