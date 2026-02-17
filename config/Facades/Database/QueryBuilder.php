<?php

/**
 * Nom du Fichier : QueryBuilder.php
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
use Config\Facades\Support\Collection;
use PDO;
use PDOException;

/**
 * QueryBuilder: Constructs SQL statements in a programmatic, 
 * chainable manner and handles safe execution via PDO prepared statements.
 */
class QueryBuilder
{
    // --- Query State Properties ---

    protected $debug = false;
    protected $table = "";
    protected $select = "*";
    protected $joins = "";
    protected $where = "";
    protected array $orderBy = [];
    protected array $groupBy = [];
    protected ?int $limit = null;
    protected ?int $offset = null;
    protected $bindings = [];

    /**
     * List of SQL operators authorized for use in the where clauses.
     * Prevents injection attempts using complex or malicious operators.
     * @var array<string>
     */
    private array $authorizedOperators = [
        "=",
        "!=",
        "<>",
        "<",
        "<=",
        ">",
        ">=",
        "LIKE",
        "NOT LIKE",
        "IN",
        "NOT IN",
        "IS NULL",
        "IS NOT NULL",
        "BETWEEN"
    ];

    /**
     * PDO database connection instance.
     * @var PDO
     */
    protected $pdo;

    /**
     * Constructor: Initializes the database connection (PDO instance).
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected ?string $modelClass = null;

    public function setModel(string $class): self
    {
        $this->modelClass = $class;
        return $this;
    }

    /**
     * Internal helper to validate if the operator is authorized.
     * * @param string $operator The operator string (e.g., '=', 'LIKE').
     * @throws \InvalidArgumentException If the operator is not authorized (logged as an exception).
     */
    protected function validateOperator(string $operator): void
    {
        $normalizedOperator = strtoupper(trim($operator));

        if (!in_array($normalizedOperator, $this->authorizedOperators)) {
            Log::exception("EXCEPTION FROM '" . __CLASS__ . "' IN METHOD '" . __FUNCTION__ . "' > Invalid operator '{$operator}' provided. Authorized operators are: " . implode(', ', $this->authorizedOperators));
        }
    }

    /**
     * Resets the builder's state properties after an execution to prepare for the next query.
     */
    protected function resetState(): void
    {
        $this->table = "";
        $this->select = "*";
        $this->joins = "";
        $this->where = "";
        $this->orderBy = [];
        $this->groupBy = [];
        $this->limit = null;
        $this->offset = null;
        $this->bindings = [];
        $this->debug = false;
    }

    public function debug($enable = false)
    {
        $this->debug = $enable;
        return $this;
    }

    /**
     * Sets the primary table for the query.
     * * @param string $tableName The name of the table.
     * @return self Returns the QueryBuilder instance for method chaining.
     */
    public function table(string $tableName): self
    {
        $this->table = $tableName;
        return $this;
    }

    /**
     * Specifies the columns to be selected. Defaults to '*' if not called.
     * * @param string ...$columns Variable number of column names or expressions.
     * @return self
     */
    public function select(string ...$columns): self
    {
        $this->select = implode(", ", $columns);
        return $this;
    }

    /**
     * Adds an INNER JOIN clause to the query.
     * * @param string $table The table to join.
     * @param string $onClause The condition for the join (e.g., 't1.id = t2.t1_id').
     * @return self
     */
    public function innerjoin($table, $onClause)
    {
        $this->joins .= "INNER JOIN $table ON $onClause ";
        return $this;
    }

    /**
     * Adds a LEFT JOIN clause to the query.
     * * @param string $table The table to join.
     * @param string $onClause The condition for the join.
     * @return self
     */
    public function leftjoin($table, $onClause)
    {
        $this->joins .= "LEFT JOIN $table ON $onClause ";
        return $this;
    }

    /**
     * Internal helper to create and bind a standard WHERE condition.
     * * @param string $col The column name.
     * @param string $operator The SQL comparison operator.
     * @param mixed $value The value to compare against (ignored for IS NULL/IS NOT NULL).
     * @param string $boolean 'AND' or 'OR'.
     * @param bool $negate If true, wraps the condition in NOT (...).
     * @return self
     */
    protected function addCondition(string $col, string $operator, $value, string $boolean, bool $negate = false): self
    {
        $this->validateOperator($operator);

        $normalizedOperator = strtoupper(trim($operator));
        $boolean = strtoupper($boolean);

        $clause = "";
        $conditionStart = $this->where === "" ? " WHERE " : " {$boolean} ";
        $negation = $negate ? "NOT " : "";

        if ($normalizedOperator === 'IS NULL' || $normalizedOperator === 'IS NOT NULL') {
            $clause = " ({$col} {$normalizedOperator}) ";
        } elseif (is_array($value)) {
            if (!in_array($normalizedOperator, ['IN', 'NOT IN'])) {
                Log::warning("QueryBuilder > addCondition() : Operator '{$operator}' used with array value. Did you mean 'IN' or 'NOT IN'?");
            }

            $placeholders = [];
            $newBindings = [];
            $bindingBaseCount = count($this->bindings);

            foreach ($value as $index => $val) {
                $placeholder = ":p_" . ($bindingBaseCount + $index);
                $placeholders[] = $placeholder;
                $newBindings[$placeholder] = $val;
            }

            $placeholderList = implode(', ', $placeholders);
            $clause = " ({$col} {$normalizedOperator} ( {$placeholderList} )) ";
            $this->bindings = array_merge($this->bindings, $newBindings);

        } else {
            $placeholder = ":p" . count($this->bindings);
            $clause = " ({$col} {$operator} ( {$placeholder} ) ) ";
            $this->bindings[$placeholder] = $value;
        }

        $this->where .= $conditionStart . $negation . $clause;

        return $this;
    }

    /**
     * Adds a condition to the WHERE clause using the AND operator.
     * * @param string $col The column name.
     * @param string $operator The SQL comparison operator.
     * @param mixed $value The value to compare against.
     * @return self
     */
    public function where(string $col, string $operator, $value): self
    {
        return $this->addCondition($col, $operator, $value, 'AND');
    }

    /**
     * Adds a raw string WHERE clause using the AND operator
     * @param string $rawCondition
     * @return QueryBuilder
     */
    public function whereRaw(string $rawCondition): self
    {
        $this->where .= " AND $rawCondition ";
        return $this;
    }

    /**
     * Adds a condition to the WHERE clause using the OR operator.
     * * @param string $col The column name.
     * @param string $operator The SQL comparison operator.
     * @param mixed $value The value to compare against.
     * @return self
     */
    public function orWhere(string $col, string $operator, $value): self
    {
        return $this->addCondition($col, $operator, $value, 'OR');
    }

    /**
     * Adds a raw string WHERE clause using the OR operator
     * @param string $rawCondition
     * @return QueryBuilder
     */
    public function orWhereRaw(string $rawCondition): self
    {
        $this->where .= " OR $rawCondition ";
        return $this;
    }

    public function whereBetween(string $col, array $values): self
    {
        $this->where .= ($this->where === "" ? " WHERE " : " AND ") . "($col BETWEEN :p" . count($this->bindings) . " AND :p" . (count($this->bindings) + 1) . ")";
        $this->bindings[] = $values[0];
        $this->bindings[] = $values[1];
        return $this;
    }

    /**
     * Adds a negation of a condition to the WHERE clause using the AND operator.
     * Example: whereNot('value', '<', 500) generates: AND NOT (value < :pX)
     * * @param string $col The column name.
     * @param string $operator The SQL comparison operator.
     * @param mixed $value The value to compare against.
     * @return self
     */
    public function whereNot(string $col, string $operator, $value): self
    {
        return $this->addCondition($col, $operator, $value, 'AND', true);
    }

    /**
     * Adds a negation of a condition to the WHERE clause using the OR operator.
     * Example: orWhereNot('value', '=', 500) generates: OR NOT (value = :pX)
     * * @param string $col The column name.
     * @param string $operator The SQL comparison operator.
     * @param mixed $value The value to compare against.
     * @return self
     */
    public function orWhereNot(string $col, string $operator, $value): self
    {
        return $this->addCondition($col, $operator, $value, 'OR', true);
    }

    /**
     * Adds an X IN (Y) condition to the WHERE clause using the AND operator.
     * * @param string $col The column name.
     * @param array $values Array of values to search for.
     * @return self
     */
    public function whereIn(string $col, $values): self
    {
        return $this->addCondition($col, 'IN', $values, 'AND');
    }

    /**
     * Adds an X IN (Y) condition to the WHERE clause using the OR operator.
     * * @param string $col The column name.
     * @param array $values Array of values to search for.
     * @return self
     */
    public function orWhereIn(string $col, array $values): self
    {
        return $this->addCondition($col, 'IN', $values, 'OR');
    }

    /**
     * Adds an X NOT IN (Y) condition to the WHERE clause using the AND operator.
     * * @param string $col The column name.
     * @param array $values Array of values to exclude.
     * @return self
     */
    public function whereNotIn(string $col, array $values): self
    {
        return $this->addCondition($col, 'NOT IN', $values, 'AND');
    }

    /**
     * Adds an X NOT IN (Y) condition to the WHERE clause using the OR operator.
     * * @param string $col The column name.
     * @param array $values Array of values to exclude.
     * @return self
     */
    public function orWhereNotIn(string $col, array $values): self
    {
        return $this->addCondition($col, 'NOT IN', $values, 'OR');
    }

    /**
     * Internal helper to handle the common logic for WHERE IN/NOT IN clauses.
     * * @param string $col The column name.
     * @param array $values Array of values.
     * @param string $operator 'IN' or 'NOT IN'.
     * @param string $boolean 'AND' or 'OR'.
     * @return self
     */
    protected function addWhereInCondition(string $col, array $values, string $operator, string $boolean): self
    {
        if (empty($values)) {
            // For an empty array, create a condition that logically returns no results (0=1 for IN, 1=1 for NOT IN)
            $impossibleCondition = ($operator === 'IN') ? " (0 = 1) " : " (1 = 1) ";
            $this->where .= ($this->where === "" ? " WHERE " : " {$boolean} ") . $impossibleCondition;
            return $this;
        }

        $placeholders = [];
        $newBindings = [];
        $bindingBaseCount = count($this->bindings);

        foreach ($values as $index => $value) {
            $placeholder = ":p_in_" . ($bindingBaseCount + $index);
            $placeholders[] = $placeholder;
            $newBindings[$placeholder] = $value;
        }

        $placeholderList = implode(', ', $placeholders);

        $booleanOp = $this->where === "" ? " WHERE " : " {$boolean} ";
        $this->where .= "{$booleanOp} ({$col} {$operator} ({$placeholderList})) ";

        $this->bindings = array_merge($this->bindings, $newBindings);

        return $this;
    }

    /**
     * Adds an ORDER BY clause. Can be chained multiple times.
     * * @param string $column The column to sort by.
     * @param string $direction The sort direction ('ASC' or 'DESC'). Defaults to 'ASC'.
     * @return self
     */
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column " . strtoupper($direction);
        return $this;
    }

    /**
     * Adds a GROUP BY clause.
     * * @param string ...$columns Variable number of column names to group by.
     * @return self
     */
    public function groupBy(string ...$columns): self
    {
        $this->groupBy = array_merge($this->groupBy, $columns);
        return $this;
    }

    /**
     * Sets the maximum number of rows to return.
     * * @param int $limit The limit value.
     * @return self
     */
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sets the offset for the query (number of rows to skip).
     * * @param int $offset The offset value.
     * @return self
     */
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function first()
    {
        $result = $this->get();
        return $result[0] ?? [];
    }

    public function count()
    {
        $oldSelect = $this->select;
        $this->select = "COUNT(*) AS value";
        $count = $this->get()[0]['value'] ?? 0;
        $this->select = $oldSelect;
        return $count;
    }

    public function find($id)
    {
        return $this->where('id', '=', $id)->first();
    }

    /**
     * Compiles the stored query parts into a complete SQL SELECT string.
     * Follows the correct SQL clause order: SELECT...FROM...JOIN...WHERE...GROUP BY...ORDER BY...LIMIT.
     * * @return string The compiled SQL query.
     */
    public function toSql(): string
    {
        $groupByClause = '';
        if (!empty($this->groupBy)) {
            $groupByClause = ' GROUP BY ' . implode(', ', $this->groupBy);
        }

        $orderByClause = '';
        if (!empty($this->orderBy)) {
            $orderByClause = ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        $limitClause = '';
        if ($this->limit !== null) {
            $limitClause = " LIMIT {$this->limit}";
            if ($this->offset !== null) {
                $limitClause .= " OFFSET {$this->offset}";
            }
        }

        return "SELECT {$this->select} FROM {$this->table} {$this->joins} {$this->where}{$groupByClause}{$orderByClause}{$limitClause}";
    }

    /**
     * Retrieves the array of values bound to placeholders for execution.
     * * @return array<string, mixed> The key-value array of bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Executes the built query against the database connection.
     * * Prepares the statement, binds values using PDO::PARAM_INT or PDO::PARAM_STR, 
     * executes, and fetches all results as an associative array.
     * Handles exceptions and logs errors.
     * * @return array The fetched results or an empty array on failure.
     */
    public function get()
    {
        if (empty($this->orderBy))
            $this->orderBy($this->table . ".created_at", "DESC");

        $sql = $this->toSql();
        try {
            $stmt = $this->pdo->prepare($sql);

            if ($this->debug)
                Log::info("QueryBuilder > get() : <b>SQL</b> : $sql");

            foreach ($this->bindings as $placeholder => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($placeholder, $value, $type);
                if ($this->debug)
                    Log::info("QueryBuilder > get() : <b>PLACEHOLDERS</b> : $placeholder <b>VALUE</b> : $value <b>TYPE</b> : $type");
            }

            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($this->modelClass) {
                $objects = [];
                foreach ($result as $row) {
                    $model = new $this->modelClass();
                    $model->fill($row);
                    $objects[] = $model;
                }
                return new Collection($objects);
            }

            return new Collection($result);

        } catch (PDOException $e) {
            Log::warning("WARNING IN '" . __CLASS__ . "' IN METHOD '" . __FUNCTION__ . "' > DB Query Error: " . $e->getMessage() . "\nSQL: " . $sql);
            return [];
        }
    }

    /**
     * Executes an INSERT query into the specified table with the given values.
     * * The values array must be an associative array: ['columnName' => $value].
     * Uses named placeholders for safe binding.
     * * @param string $tableName The table to insert into.
     * @param array $values Associative array of column => value pairs.
     * @return int|false The ID of the last inserted row, or false on failure.
     */
    public function insert(array $values): int|false
    {
        if (empty($values)) {
            Log::warning("QueryBuilder > insert() : No values provided for insertion.");
            return false;
        }

        $columns = array_keys($values);
        $placeholders = array_map(fn($col) => ":$col", $columns);

        $columnList = implode(', ', $columns);
        $placeholderList = implode(', ', $placeholders);

        $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholderList})";

        $bindings = [];
        foreach ($values as $column => $value) {
            $bindings[":{$column}"] = $value;
        }

        try {
            $stmt = $this->pdo->prepare($sql);

            if ($this->debug) {
                Log::info("QueryBuilder > insert() : <b>SQL</b> : $sql");
                Log::info("QueryBuilder > insert() : <b>BINDINGS</b> : " . json_encode($bindings));
            }

            foreach ($bindings as $placeholder => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($placeholder, $value, $type);
            }

            $success = $stmt->execute();

            if ($success) {
                return (int) $this->pdo->lastInsertId();
            }

            return false;

        } catch (PDOException $e) {
            Log::warning("WARNING IN '" . __CLASS__ . "' IN METHOD '" . __FUNCTION__ . "' > DB Insert Error: " . $e->getMessage() . "\nSQL: " . $sql);
            return false;
        }
    }

    /**
     * Executes an UPDATE query on the previously specified table and WHERE clause.
     * * @param array $values Associative array of column => new_value pairs to set.
     * @return int|false The number of affected rows, or false on failure.
     */
    public function update(array $values): int|false
    {
        if (empty($this->table)) {
            Log::warning("QueryBuilder > update() : Table name not specified. Use ->table('tableName') first.");
            return false;
        }

        if (empty($values)) {
            Log::warning("QueryBuilder > update() : No values provided for update.");
            return false;
        }

        $setClauses = [];
        $updateBindings = [];
        foreach ($values as $column => $value) {
            $placeholder = ":u_" . $column;
            $setClauses[] = "{$column} = {$placeholder}";
            $updateBindings[$placeholder] = $value;
        }
        $setClause = implode(', ', $setClauses);

        $allBindings = array_merge($updateBindings, $this->bindings);

        $sql = "UPDATE {$this->table} SET {$setClause} {$this->where}";

        try {
            $stmt = $this->pdo->prepare($sql);

            if ($this->debug) {
                Log::info("QueryBuilder > update() : <b>SQL</b> : $sql");
                Log::info("QueryBuilder > update() : <b>ALL BINDINGS</b> : " . json_encode($allBindings));
            }

            foreach ($allBindings as $placeholder => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($placeholder, $value, $type);
            }

            $stmt->execute();

            return $stmt->rowCount();

        } catch (PDOException $e) {
            Log::warning("WARNING IN '" . __CLASS__ . "' IN METHOD '" . __FUNCTION__ . "' > DB Update Error: " . $e->getMessage() . "\nSQL: " . $sql);
            return false;
        }
    }

    /**
     * Executes the DELETE query on the previously specified table and WHERE clause.
     * * @return int|false The number of affected rows, or false on failure.
     */
    public function delete(): int|false
    {
        if (empty($this->table)) {
            Log::exception("QueryBuilder > delete() : Table name not specified. Use ->table('tableName') first.");
            return false;
        }

        if (empty($this->where)) {
            Log::exception("QueryBuilder > delete() : WHERE clause is missing. Full table deletion is prevented.");
            return false;
        }

        $sql = "DELETE FROM {$this->table} {$this->where}";
        $allBindings = $this->bindings;

        try {
            $stmt = $this->pdo->prepare($sql);

            if ($this->debug) {
                Log::info("QueryBuilder > delete() : <b>SQL</b> : $sql");
                Log::info("QueryBuilder > delete() : <b>ALL BINDINGS</b> : " . json_encode($allBindings));
            }

            foreach ($allBindings as $placeholder => $value) {
                $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $stmt->bindValue($placeholder, $value, $type);
            }

            $stmt->execute();

            $rowCount = $stmt->rowCount();

            $this->resetState();

            return $rowCount;

        } catch (PDOException $e) {
            Log::warning("WARNING IN '" . __CLASS__ . "' IN METHOD '" . __FUNCTION__ . "' > DB Delete Error: " . $e->getMessage() . "\nSQL: " . $sql);

            $this->resetState();

            return false;
        }
    }
}