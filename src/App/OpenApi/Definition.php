<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Point d'ancrage pour les métadonnées globales et les schémas réutilisables
 * de la documentation OpenAPI. Ce fichier ne contient aucune logique : il est
 * uniquement scanné par swagger-php pour générer public/openapi.json.
 *
 * @see bin/generate-openapi.php
 */
#[OA\Info(
    version: '1.0.0',
    title: 'Vide Grenier En Ligne - API',
    description: "Documentation des endpoints JSON exposés par le contrôleur `Api` (App\\Controllers\\Api).",
)]
#[OA\Server(
    url: '/',
    description: 'Serveur courant',
)]
#[OA\Schema(
    schema: 'Article',
    type: 'object',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Mappemonde à gratter'),
        new OA\Property(property: 'description', type: 'string', example: 'Carte du monde à gratter, neuve.'),
        new OA\Property(property: 'published_date', type: 'string', format: 'date', example: '2018-05-28'),
        new OA\Property(property: 'user_id', type: 'integer', example: 1),
        new OA\Property(property: 'views', type: 'integer', example: 4),
        new OA\Property(property: 'picture', type: 'string', nullable: true, example: '1.jpeg'),
    ],
)]
class Definition
{
}
