<?php

namespace Tests\Models;

use App\Models\Articles;
use DateTime;
use PDO;
use PHPUnit\Framework\TestCase;

class ArticlesTest extends TestCase
{
    private PDO $pdo;

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
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL,
                email TEXT NOT NULL,
                password TEXT NOT NULL,
                salt TEXT NOT NULL,
                is_admin INTEGER NOT NULL DEFAULT 0
            )
        ');

        Articles::setTestDB($this->pdo);
    }

    protected function tearDown(): void
    {
        Articles::setTestDB(null);
    }

    private function seedUser(int $id, string $username = 'John Doe', string $email = 'john@example.com'): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO users (id, username, email, password, salt) VALUES (:id, :username, :email, :password, :salt)');
        $stmt->execute([
            ':id' => $id,
            ':username' => $username,
            ':email' => $email,
            ':password' => 'hashed',
            ':salt' => 'salt',
        ]);
    }

    private function seedArticle(array $overrides = []): int
    {
        $data = array_merge([
            'name' => 'Article',
            'description' => 'Description',
            'published_date' => '2020-01-01',
            'user_id' => 1,
            'views' => 0,
            'picture' => null,
        ], $overrides);

        $stmt = $this->pdo->prepare('
            INSERT INTO articles (name, description, published_date, user_id, views, picture)
            VALUES (:name, :description, :published_date, :user_id, :views, :picture)
        ');
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':published_date' => $data['published_date'],
            ':user_id' => $data['user_id'],
            ':views' => $data['views'],
            ':picture' => $data['picture'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function testGetAllWithNoFilterReturnsAllArticles(): void
    {
        $this->seedUser(1);
        $this->seedArticle(['name' => 'A']);
        $this->seedArticle(['name' => 'B']);

        $result = Articles::getAll('');

        $this->assertCount(2, $result);
    }

    public function testGetAllWithViewsFilterOrdersByViewsDescending(): void
    {
        $this->seedUser(1);
        $this->seedArticle(['name' => 'Low', 'views' => 2]);
        $this->seedArticle(['name' => 'High', 'views' => 50]);
        $this->seedArticle(['name' => 'Mid', 'views' => 10]);

        $result = Articles::getAll('views');

        $this->assertSame(['High', 'Mid', 'Low'], array_column($result, 'name'));
    }

    public function testGetAllWithDataFilterOrdersByPublishedDateDescending(): void
    {
        $this->seedUser(1);
        $this->seedArticle(['name' => 'Old', 'published_date' => '2019-01-01']);
        $this->seedArticle(['name' => 'New', 'published_date' => '2022-01-01']);
        $this->seedArticle(['name' => 'Mid', 'published_date' => '2020-06-15']);

        $result = Articles::getAll('data');

        $this->assertSame(['New', 'Mid', 'Old'], array_column($result, 'name'));
    }

    public function testGetOneReturnsArrayContainingTheJoinedRow(): void
    {
        $this->seedUser(1, 'Alice');
        $id = $this->seedArticle(['name' => 'Chair', 'user_id' => 1]);

        $result = Articles::getOne($id);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertSame('Chair', $result[0]['name']);
        $this->assertSame('Alice', $result[0]['username']);
    }

    public function testGetOneReturnsEmptyArrayWhenArticleDoesNotExist(): void
    {
        $this->seedUser(1);

        $result = Articles::getOne(999);

        $this->assertSame([], $result);
    }

    public function testAddOneViewIncrementsViewsCounter(): void
    {
        $this->markTestSkipped(
            'Articles::addOneView() runs "UPDATE articles SET articles.views = ..." '
            . '(a table-qualified column on the left of SET), which is valid MySQL but '
            . 'SQLite rejects it with a syntax error. Cannot be exercised against a '
            . 'SQLite fixture without modifying the source, which is out of scope here.'
        );
    }

    public function testAddOneViewDoesNotAffectOtherArticles(): void
    {
        $this->markTestSkipped(
            'Same SQLite/MySQL SET-clause syntax incompatibility as above; see '
            . 'testAddOneViewIncrementsViewsCounter.'
        );
    }

    public function testGetByUserReturnsOnlyThatUsersArticles(): void
    {
        $this->seedUser(1, 'Alice');
        $this->seedUser(2, 'Bob');
        $this->seedArticle(['name' => 'A1', 'user_id' => 1]);
        $this->seedArticle(['name' => 'A2', 'user_id' => 1]);
        $this->seedArticle(['name' => 'B1', 'user_id' => 2]);

        $result = Articles::getByUser(1);

        $this->assertCount(2, $result);
        $this->assertSame(['A1', 'A2'], array_column($result, 'name'));
    }

    public function testGetByUserReturnsEmptyArrayForUserWithNoArticles(): void
    {
        $this->seedUser(1);

        $result = Articles::getByUser(1);

        $this->assertSame([], $result);
    }

    public function testGetSuggestOrdersByPublishedDateDescendingAndLimitsToTen(): void
    {
        $this->seedUser(1);

        for ($i = 1; $i <= 12; $i++) {
            $this->seedArticle([
                'name' => 'Article' . $i,
                'published_date' => sprintf('2020-01-%02d', $i),
            ]);
        }

        $result = Articles::getSuggest();

        $this->assertCount(10, $result);
        $this->assertSame('Article12', $result[0]['name']);
        $this->assertSame('Article3', $result[9]['name']);
    }

    public function testSaveInsertsArticleAndReturnsLastInsertId(): void
    {
        $this->seedUser(1);

        $id = Articles::save([
            'name' => 'New Article',
            'description' => 'Brand new',
            'user_id' => 1,
        ]);

        $this->assertGreaterThan(0, (int) $id);

        $row = $this->pdo->query('SELECT * FROM articles WHERE id = ' . (int) $id)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('New Article', $row['name']);
        $this->assertSame('Brand new', $row['description']);
        $this->assertSame('1', (string) $row['user_id']);
    }

    public function testSaveSetsPublishedDateToToday(): void
    {
        $this->seedUser(1);

        $id = Articles::save([
            'name' => 'Dated Article',
            'description' => 'desc',
            'user_id' => 1,
        ]);

        $row = $this->pdo->query('SELECT published_date FROM articles WHERE id = ' . (int) $id)->fetch(PDO::FETCH_ASSOC);

        $expected = (new DateTime())->format('Y-m-d');
        $this->assertSame($expected, $row['published_date']);
    }

    public function testAttachPictureUpdatesPictureColumn(): void
    {
        $this->seedUser(1);
        $id = $this->seedArticle(['picture' => null]);

        Articles::attachPicture($id, 'photo.jpg');

        $picture = $this->pdo->query('SELECT picture FROM articles WHERE id = ' . $id)->fetchColumn();
        $this->assertSame('photo.jpg', $picture);
    }

    public function testAttachPictureDoesNotAffectOtherArticles(): void
    {
        $this->seedUser(1);
        $idA = $this->seedArticle(['picture' => 'a.jpg']);
        $idB = $this->seedArticle(['picture' => 'b.jpg']);

        Articles::attachPicture($idA, 'new.jpg');

        $pictureB = $this->pdo->query('SELECT picture FROM articles WHERE id = ' . $idB)->fetchColumn();
        $this->assertSame('b.jpg', $pictureB);
    }
}
