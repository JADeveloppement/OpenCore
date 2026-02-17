<?php

/**
 * Nom du Fichier : Models.php
 * Projet : JADCoreEngine
 * @category   Framework
 * @package    JADeveloppement
 * @author     Jalal AISSAOUI <jalal.aissaoui@outlook.com>
 * @copyright  2024-2026 JADeveloppement
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       https://jadeveloppement.fr
 */

namespace Config\Facades\Models;

use Config\Facades\Database\DB;
use Config\Facades\Database\QueryBuilder;
use Config\Facades\Log;

abstract class Models implements \JsonSerializable, \ArrayAccess
{

    /**
     * @var \PDO The PDO database connection instance.
     */

    protected ?\PDO $pdo;
    /**
     * The name of the database table for the model.
     * Must be defined in the child class.
     * @var string
     */
    protected static string $tableName;

    /**
     * An array of valid columns in the database table schema.
     * Used for security checks.
     * Must be defined in the child class.
     * @var array
     */
    protected static array $schemaColumns;

    protected static array $rules = [];

    protected array $attributes = [];

    /**
     * Hydrate le modèle avec des données
     */
    public function fill(array $data): self
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
        return $this;
    }

    /**
     * Accesseur magique pour lire les colonnes comme des propriétés
     */
    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Mutateur magique pour écrire les colonnes comme des propriétés
     */
    public function __set($key, $value)
    {
        if (in_array($key, static::$schemaColumns) || $key === 'id') {
            $this->attributes[$key] = $value;
        } else {
            Log::info("Error while setting $key. Column $key does not exists in Model " . static::$tableName);
        }
    }

    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? DB::getConnection();
    }

    /**
     * Allows static calls to QueryBuilder methods to automatically start a query chain.
     * E.g., User::where('id', 1) is equivalent to User::query()->where('id', 1)
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $query = static::query();

        if (method_exists($query, $method)) {
            return $query->{$method}(...$parameters);
        }

        Log::exception("EXCEPTION : " . __CLASS__ . " in method : " . __FUNCTION__ . "Static method '{$method}' not found on Model or QueryBuilder.");
    }

    /**
     * Crée une nouvelle instance de QueryBuilder configurée pour ce modèle.
     */
    protected function newQuery(): QueryBuilder
    {
        return DB::table(static::$tableName)->setModel(static::class);
    }

    /**
     * Point d'entrée statique pour démarrer une requête.
     * Optimisé pour ne pas instancier le modèle inutilement.
     */
    public static function query(): QueryBuilder
    {
        return DB::table(static::$tableName)->setModel(static::class);
    }

    public static function getTableName(): string
    {
        return static::$tableName;
    }

    public static function getSchemaColumns(): array
    {
        return static::$schemaColumns;
    }

    public static function validate(array $data): array|bool
    {
        $validator = new \Config\Facades\Validation\Validator();

        $filteredData = array_intersect_key($data, array_flip(static::$schemaColumns));

        if (!$validator->validate($filteredData, static::$rules)) {
            return [
                'success' => false,
                'errors' => $validator->errors()
            ];
        }

        return [
            'success' => true,
            'data' => $filteredData
        ];
    }

    /**
     * Sauvegarde le modèle en base de données (Insert ou Update)
     */
    public function save(): bool
    {
        $query = $this->newQuery();

        if (isset($this->attributes['id'])) {
            return (bool) $query->where('id', '=', $this->attributes['id'])
                ->update($this->attributes);
        }

        $id = $query->insert($this->attributes);
        if ($id) {
            $this->attributes['id'] = $id;
            return true;
        }

        return false;
    }

    /**
     * Supprime l'instance actuelle de la base de données
     */
    public function delete(): bool
    {
        if (!isset($this->attributes['id'])) {
            return false;
        }

        return (bool) $this->newQuery()
            ->where('id', '=', $this->attributes['id'])
            ->delete();
    }

    public function jsonSerialize(): mixed
    {
        return $this->attributes;
    }

    /**
     * --- MÉTHODES DE L'INTERFACE ArrayAccess ---
     * Permet la rétrocompatibilité $object['key']
     */

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->attributes[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->attributes[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->attributes[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }
}
