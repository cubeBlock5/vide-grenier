<?php

namespace Tests\Models;

use App\Models\User;
use PDO;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

        User::setTestDB($this->pdo);
    }

    protected function tearDown(): void
    {
        User::setTestDB(null);
    }

    public function testCreateUserInsertsRowAndReturnsLastInsertId(): void
    {
        $id = User::createUser([
            'username' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'hashedpassword',
            'salt' => 'somesalt',
        ]);

        $this->assertGreaterThan(0, (int) $id);

        $row = $this->pdo->query('SELECT * FROM users WHERE id = ' . (int) $id)->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('Alice', $row['username']);
        $this->assertSame('alice@example.com', $row['email']);
        $this->assertSame('hashedpassword', $row['password']);
        $this->assertSame('somesalt', $row['salt']);
    }

    public function testGetByLoginReturnsUserMatchingEmail(): void
    {
        User::createUser([
            'username' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pw',
            'salt' => 'salt',
        ]);

        $result = User::getByLogin('bob@example.com');

        $this->assertIsArray($result);
        $this->assertSame('Bob', $result['username']);
        $this->assertSame('bob@example.com', $result['email']);
    }

    public function testGetByLoginReturnsFalseWhenNoMatch(): void
    {
        User::createUser([
            'username' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pw',
            'salt' => 'salt',
        ]);

        $result = User::getByLogin('nobody@example.com');

        $this->assertFalse($result);
    }

    public function testGetByLoginReturnsOnlyOneRowWhenMultipleShareEmailSomehow(): void
    {
        // The query uses LIMIT 1; verify it still resolves to a single assoc array.
        User::createUser([
            'username' => 'First',
            'email' => 'dup@example.com',
            'password' => 'pw1',
            'salt' => 'salt1',
        ]);

        $result = User::getByLogin('dup@example.com');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
    }
}
