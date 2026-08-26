<?php

namespace App\Controllers;

use App\Models\Articles;
use App\Models\Cities;
use \Core\View;
use Exception;

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
