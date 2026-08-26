<?php

namespace Tests\Utility;

use App\Utility\Hash;
use PHPUnit\Framework\TestCase;

class HashTest extends TestCase
{
    public function testGenerateReturnsSha256HashWithoutSalt(): void
    {
        $result = Hash::generate('password');

        $this->assertSame(hash('sha256', 'password'), $result);
        $this->assertSame(64, strlen($result));
    }

    public function testGenerateReturnsSha256HashWithSalt(): void
    {
        $result = Hash::generate('password', 'somesalt');

        $this->assertSame(hash('sha256', 'password' . 'somesalt'), $result);
    }

    public function testGenerateWithSaltDiffersFromGenerateWithoutSalt(): void
    {
        $withoutSalt = Hash::generate('password');
        $withSalt = Hash::generate('password', 'somesalt');

        $this->assertNotSame($withoutSalt, $withSalt);
    }

    public function testGenerateIsDeterministic(): void
    {
        $this->assertSame(
            Hash::generate('abc', 'xyz'),
            Hash::generate('abc', 'xyz')
        );
    }

    public function testGenerateSaltReturnsStringOfRequestedLength(): void
    {
        $this->assertSame(16, strlen(Hash::generateSalt(16)));
        $this->assertSame(0, strlen(Hash::generateSalt(0)));
        $this->assertSame(32, strlen(Hash::generateSalt(32)));
    }

    public function testGenerateSaltUsesOnlyAllowedCharset(): void
    {
        $charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/\\][{}'\";:?.>,<!@#$%^&*()-_=+|";
        $salt = Hash::generateSalt(200);

        for ($i = 0; $i < strlen($salt); $i++) {
            $this->assertStringContainsString($salt[$i], $charset);
        }
    }

    public function testGenerateSaltProducesDifferentValuesAcrossCalls(): void
    {
        // mt_rand-based; astronomically unlikely to collide at this length.
        $first = Hash::generateSalt(32);
        $second = Hash::generateSalt(32);

        $this->assertNotSame($first, $second);
    }

    public function testGenerateUniqueReturnsA64CharacterHash(): void
    {
        $unique = Hash::generateUnique();

        $this->assertSame(64, strlen($unique));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $unique);
    }

    public function testGenerateUniqueProducesDifferentValuesAcrossCalls(): void
    {
        $first = Hash::generateUnique();
        $second = Hash::generateUnique();

        $this->assertNotSame($first, $second);
    }
}
