<?php

namespace Tests\Core;

use Core\Model;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Tiny concrete fixture so we can reach the protected getDB() method
 * through a public wrapper, without touching the abstract Model class.
 */
class ModelTestFixture extends Model
{
    public static function callGetDB()
    {
        return static::getDB();
    }
}

class ModelTest extends TestCase
{
    protected function tearDown(): void
    {
        // Reset the shared static override so this doesn't leak into other
        // test classes/files (the property lives on the base Model class,
        // so it is shared across every subclass).
        ModelTestFixture::setTestDB(null);
    }

    public function testSetTestDBMakesGetDBReturnTheInjectedInstance(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        ModelTestFixture::setTestDB($pdo);

        $result = ModelTestFixture::callGetDB();

        $this->assertSame($pdo, $result);
    }

    public function testSetTestDBNullRevertsToRealDBPath(): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        ModelTestFixture::setTestDB($pdo);
        $this->assertSame($pdo, ModelTestFixture::callGetDB());

        ModelTestFixture::setTestDB(null);

        // With the override cleared, getDB() falls back to building a real
        // MySQL PDO connection from App\Config. That host doesn't exist in
        // the test environment, so it should throw rather than silently
        // return our injected fixture instance.
        try {
            $result = ModelTestFixture::callGetDB();
            // If it somehow succeeds (e.g. a real MySQL happens to be
            // reachable), it must at least no longer be our injected PDO.
            $this->assertNotSame($pdo, $result);
        } catch (Throwable $e) {
            $this->assertTrue(true, 'getDB() threw while attempting the real DB connection, as expected.');
        }
    }
}
