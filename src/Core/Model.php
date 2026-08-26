<?php

namespace Core;

use PDO;
use App\Config;

/**
 * Base model
 *
 * PHP version 7.0
 */
abstract class Model
{

    /**
     * Connexion PDO injectée pour les tests, à la place de la vraie base MySQL
     *
     * @var PDO|null
     */
    private static $testDB = null;

    /**
     * Get the PDO database connection
     *
     * @return mixed
     */
    protected static function getDB()
    {
        static $db = null;

        if (static::$testDB !== null) {
            return static::$testDB;
        }

        if ($db === null) {
            $dsn = 'mysql:host=' . Config::dbHost() . ';dbname=' . Config::dbName() . ';charset=utf8';
            $db = new PDO($dsn, Config::dbUser(), Config::dbPassword());

            // Throw an Exception when an error occurs
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        return $db;
    }

    /**
     * Remplace la connexion à la base par celle fournie (utilisé par les tests).
     * Appeler avec null pour revenir à la vraie connexion.
     *
     * @param PDO|null $db
     * @return void
     */
    public static function setTestDB(?PDO $db): void
    {
        static::$testDB = $db;
    }
}
