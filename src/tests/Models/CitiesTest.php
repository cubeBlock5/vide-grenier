<?php

namespace Tests\Models;

use App\Models\Cities;
use PDO;
use PHPUnit\Framework\TestCase;

class CitiesTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->exec('
            CREATE TABLE villes_france (
                ville_id INTEGER PRIMARY KEY AUTOINCREMENT,
                ville_nom_reel TEXT
            )
        ');

        Cities::setTestDB($this->pdo);
    }

    protected function tearDown(): void
    {
        Cities::setTestDB(null);
    }

    private function seedCity(int $id, string $name): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO villes_france (ville_id, ville_nom_reel) VALUES (:id, :name)');
        $stmt->execute([':id' => $id, ':name' => $name]);
    }

    public function testSearchReturnsFlatArrayOfMatchingIds(): void
    {
        $this->seedCity(1, 'Paris');
        $this->seedCity(2, 'Parthenay');
        $this->seedCity(3, 'Lyon');

        $result = Cities::search('Par');

        $this->assertSame([1, 2], $result);
    }

    public function testSearchOnlyMatchesPrefixNotSubstring(): void
    {
        $this->seedCity(1, 'Paris');
        $this->seedCity(2, 'Le Paris'); // does not start with "Paris"

        $result = Cities::search('Paris');

        $this->assertSame([1], $result);
    }

    public function testSearchReturnsEmptyArrayWhenNoMatch(): void
    {
        $this->seedCity(1, 'Lyon');

        $result = Cities::search('Nowhere');

        $this->assertSame([], $result);
    }

    public function testSearchIsCaseTreatedAsSqliteLikeDefault(): void
    {
        // SQLite's default LIKE is case-insensitive for ASCII.
        $this->seedCity(1, 'Marseille');

        $result = Cities::search('mar');

        $this->assertSame([1], $result);
    }
}
