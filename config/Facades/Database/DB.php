<?php

/**
 * Nom du Fichier : DB.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Database;

use Config\Facades\Log;
use PDO;

/**
 * DB Facade: Provides a static interface to the QueryBuilder class,
 * mimicking the structure of Laravel's DB facade.
 * * This allows calls like DB::table('users')->get() to work by forwarding 
 * the static method calls to a dynamic instance of QueryBuilder.
 */
class DB
{
    /**
     * The instance of the actual QueryBuilder (or Connection) object 
     * used for the current static call chain.
     * @var QueryBuilder|null
     */
    protected static $instance;

    /**
     * The statically stored PDO connection instance.
     * This is set once during application bootstrapping by the Container.
     * @var PDO|null
     */
    protected static ?PDO $connection = null;

    /**
     * Initializes the static DB facade with the PDO connection instance.
     * This method is called once by the Dependency Injection Container.
     * @param PDO $pdo The initialized PDO connection.
     */
    public static function boot(PDO $pdo): void
    {
        static::$connection = $pdo;
    }

    /**
     * Retrieves the statically stored PDO connection.
     * Used by Models to instantiate themselves when static methods are called.
     * @return PDO
     * @throws RuntimeException If the connection has not been booted.
     */
    public static function getConnection(): PDO
    {
        if (static::$connection === null) {
            Log::exception("EXCEPTION : " . __CLASS__ . " IN METHOD " . __FUNCTION__ . " : Database connection not initialized. Call DB::boot(PDO \$pdo) first.");
        }
        return static::$connection;
    }


    /**
     * Initializes and retrieves a new, clean instance of the QueryBuilder.
     * This ensures that each static call chain (e.g., DB::table(...)->get()) 
     * starts with a fresh query state.
     * * @return QueryBuilder
     */
    protected static function getQueryBuilderInstance(): QueryBuilder
    {
        if (static::$connection === null) {
            Log::exception("RuntimeException : " . __CLASS__ . " IN METHOD '" . __FUNCTION__ . "' > Database connection not initialized. Call DB::boot(PDO \$pdo) first.");
        }

        // Instantiate the QueryBuilder, injecting the PDO dependency
        static::$instance = new QueryBuilder(static::$connection);
        return static::$instance;
    }

    /**
     * Handle static calls to the DB class.
     * This is PHP's magic method that intercepts any call to a non-existent 
     * static method (like table(), select(), etc.) and redirects it to the
     * corresponding method on the QueryBuilder instance.
     * * @param string $method The name of the static method called (e.g., 'table').
     * @param array $parameters The arguments passed to the static method.
     * @return mixed Returns the QueryBuilder instance for chaining, or the final result (e.g., from get()).
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = static::getQueryBuilderInstance();

        return $instance->$method(...$parameters);
    }
}