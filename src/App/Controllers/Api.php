<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Models\Cities;
use \Core\View;
use Exception;
use OpenApi\Attributes as OA;

/**
 * API controller
 */
class Api extends \Core\Controller
{

    /**
     * Affiche la liste des articles / produits pour la page d'accueil
     *
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/products',
        description: 'Retourne la liste des articles publiés, triée selon le paramètre `sort`.',
        summary: 'Liste des articles',
        tags: ['Api'],
        parameters: [
            new OA\Parameter(
                name: 'sort',
                description: "Critère de tri : `views` (nombre de vues décroissant), `data` (date de publication décroissante), ou vide pour l'ordre par défaut.",
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', enum: ['', 'views', 'data']),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des articles',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Article'),
                ),
            ),
        ],
    )]
    public function ProductsAction()
    {
        $query = $_GET['sort'];

        $articles = $this->getProducts($query);

        header('Content-Type: application/json');
        echo json_encode($articles);
    }

    /**
     * Recherche dans la liste des villes
     *
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/cities',
        description: "Recherche les villes dont le nom commence par `query` (utilisé par l'autocomplétion du formulaire de dépôt d'annonce).",
        summary: 'Recherche de villes',
        tags: ['Api'],
        parameters: [
            new OA\Parameter(
                name: 'query',
                description: 'Préfixe du nom de ville recherché.',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Identifiants des villes correspondantes',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(type: 'integer'),
                ),
            ),
        ],
    )]
    public function CitiesAction(){

        $cities = $this->searchCities($_GET['query']);

        header('Content-Type: application/json');
        echo json_encode($cities);
    }

    /**
     * Récupère la liste des articles triée selon $sort.
     *
     * @param string $sort
     * @return array
     */
    protected function getProducts(string $sort): array
    {
        return Articles::getAll($sort);
    }

    /**
     * Recherche les villes correspondant à $query.
     *
     * @param string $query
     * @return array
     */
    protected function searchCities(string $query): array
    {
        return Cities::search($query);
    }
}
