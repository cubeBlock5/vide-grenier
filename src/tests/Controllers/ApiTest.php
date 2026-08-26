<?php

namespace Tests\Controllers;

use App\Controllers\Api;
use App\Models\Articles;
use App\Models\Cities;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Small fixture subclass exposing public wrappers around the protected
 * getProducts()/searchCities() methods extracted from Api::ProductsAction()
 * / Api::CitiesAction(), so they can be tested directly without reading
 * $_GET or echoing JSON.
 */
class ApiTestFixture extends Api
{
    public function callGetProducts(string $sort): array
    {
        return $this->getProducts($sort);
    }

    public function callSearchCities(string $query): array
    {
        return $this->searchCities($query);
    }
}

class ApiTest extends TestCase
{
    private PDO $pdo;
    private ApiTestFixture $api;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE articles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT NOT NULL,
                published_date TEXT,
                user_id INTEGER NOT NULL,
                views INTEGER NOT NULL DEFAULT 0,
                picture TEXT
            )
        ');

        $this->pdo->exec('
            CREATE TABLE villes_france (
                ville_id INTEGER PRIMARY KEY AUTOINCREMENT,
                ville_nom_reel TEXT
            )
        ');

        $this->pdo->exec("INSERT INTO articles (name, description, published_date, user_id, views) VALUES
            ('Guide Berlin', 'desc', '2018-01-01', 1, 5),
            ('Vélo', 'desc', '2020-06-15', 1, 50),
            ('Cluedo', 'desc', '2019-03-10', 1, 20)
        ");

        $this->pdo->exec("INSERT INTO villes_france (ville_nom_reel) VALUES
            ('Paris'), ('Paray-le-Monial'), ('Lyon')
        ");

        Articles::setTestDB($this->pdo);
        Cities::setTestDB($this->pdo);

        $this->api = new ApiTestFixture(['id' => 1]);
    }

    protected function tearDown(): void
    {
        Articles::setTestDB(null);
        Cities::setTestDB(null);
    }

    public function testGetProductsReturnsAllArticlesUnsorted(): void
    {
        $articles = $this->api->callGetProducts('');

        $this->assertCount(3, $articles);
    }

    public function testGetProductsSortsByViewsDescending(): void
    {
        $articles = $this->api->callGetProducts('views');

        $this->assertSame('Vélo', $articles[0]['name']);
        $this->assertSame('Cluedo', $articles[1]['name']);
        $this->assertSame('Guide Berlin', $articles[2]['name']);
    }

    public function testGetProductsSortsByDateDescending(): void
    {
        $articles = $this->api->callGetProducts('data');

        $this->assertSame('Vélo', $articles[0]['name']);
        $this->assertSame('Cluedo', $articles[1]['name']);
        $this->assertSame('Guide Berlin', $articles[2]['name']);
    }

    public function testSearchCitiesFindsMatchingPrefix(): void
    {
        $cities = $this->api->callSearchCities('Para');

        $this->assertCount(1, $cities);
    }

    public function testSearchCitiesReturnsEmptyArrayWhenNoMatch(): void
    {
        $cities = $this->api->callSearchCities('Zzz');

        $this->assertSame([], $cities);
    }
}
