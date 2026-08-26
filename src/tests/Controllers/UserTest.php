<?php

namespace Tests\Controllers;

use App\Controllers\User;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Small fixture subclass exposing public wrappers around the protected
 * register()/login() methods on the User controller.
 */
class UserTestFixture extends User
{
    public function callRegister($data)
    {
        return $this->register($data);
    }

    public function callLogin($data)
    {
        return $this->login($data);
    }
}

/**
 * These tests exercise register()/login() end-to-end against a SQLite
 * in-memory DB (via the Core\Model::setTestDB() seam), bypassing the real
 * MySQL connection. Note: the password-confirmation check ("password" vs
 * "password-check") lives in registerAction(), not in register() itself,
 * so it isn't exercised here.
 */
class UserTest extends TestCase
{
    private PDO $pdo;
    private UserTestFixture $user;

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

        \App\Models\User::setTestDB($this->pdo);

        $_SESSION = [];

        // login() calls session_regenerate_id(true) on success; that emits
        // a PHP warning (which PHPUnit turns into a test warning) unless a
        // session is actually active, which the CLI SAPI doesn't start on
        // its own.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $this->user = new UserTestFixture(['id' => 1]);
    }

    protected function tearDown(): void
    {
        \App\Models\User::setTestDB(null);

        $_SESSION = [];

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    public function testRegisterCreatesUserAndReturnsInsertId(): void
    {
        $result = $this->user->callRegister([
            'email' => 'jane.doe@example.com',
            'username' => 'JaneDoe',
            'password' => 'S3cret!',
        ]);

        $this->assertNotFalse($result);
        $this->assertTrue(is_numeric($result));
        $this->assertGreaterThan(0, (int) $result);

        $stmt = $this->pdo->query('SELECT * FROM users WHERE email = ' . $this->pdo->quote('jane.doe@example.com'));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($row);
        $this->assertSame('JaneDoe', $row['username']);
    }

    public function testLoginWithCorrectCredentialsReturnsTrueAndFillsSession(): void
    {
        $this->user->callRegister([
            'email' => 'jane.doe@example.com',
            'username' => 'JaneDoe',
            'password' => 'S3cret!',
        ]);

        $result = $this->user->callLogin([
            'email' => 'jane.doe@example.com',
            'password' => 'S3cret!',
        ]);

        $this->assertTrue($result);
        $this->assertArrayHasKey('user', $_SESSION);
        $this->assertSame('JaneDoe', $_SESSION['user']['username']);
        $this->assertNotEmpty($_SESSION['user']['id']);
    }

    public function testLoginWithWrongPasswordReturnsFalse(): void
    {
        $this->user->callRegister([
            'email' => 'jane.doe@example.com',
            'username' => 'JaneDoe',
            'password' => 'S3cret!',
        ]);

        $result = $this->user->callLogin([
            'email' => 'jane.doe@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertFalse($result);
        $this->assertArrayNotHasKey('user', $_SESSION);
    }

    public function testLoginWithUnknownEmailReturnsFalse(): void
    {
        $result = $this->user->callLogin([
            'email' => 'nobody@example.com',
            'password' => 'whatever',
        ]);

        $this->assertFalse($result);
        $this->assertArrayNotHasKey('user', $_SESSION);
    }

    public function testLoginWithEmptyCredentialsReturnsFalse(): void
    {
        $result = $this->user->callLogin([
            'email' => '',
            'password' => '',
        ]);

        $this->assertFalse($result);
    }
}
