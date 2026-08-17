<?php

namespace App;

/**
 * Application configuration
 *
 * PHP version 7.0
 */
class Config
{

    /**
     * Database host
     */
    public static function dbHost(): string
    {
        return getenv('DB_HOST') ?: 'localhost';
    }

    /**
     * Database name
     */
    public static function dbName(): string
    {
        return getenv('DB_NAME') ?: 'videgrenierenligne';
    }

    /**
     * Database user
     */
    public static function dbUser(): string
    {
        return getenv('DB_USER') ?: 'webapplication';
    }

    /**
     * Database password
     */
    public static function dbPassword(): string
    {
        return getenv('DB_PASSWORD') ?: '';
    }

    /**
     * Show or hide error messages on screen
     * @var boolean
     */
    const SHOW_ERRORS = true;
}
