<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;

/**
 * @author Ortiz David
 * @copyright 2026
 * @version 2.7.0
 * @name Table
 * @desc Low-level, lightweight base table operations using native PDO without hidden magic columns.
 */
class Table
{
    protected Database $db;
    protected ?string $tableName = null;
    protected string $primaryKey = 'id';
    protected ?int $lastInsertId = null;
    protected int $numRows = 0;
    protected int $numCols = 0;

    /**
     * Initialize the table helper with a database instance.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Set the target table name fluently.
     */
    public function table(string $tableName): self
    {
        $this->tableName = $tableName;
        return $this;
    }

    /**
     * Get the active table name or throw an exception if not set.
     */
    protected function getTable(): string
    {
        if ($this->tableName === null) {
            throw new RuntimeException("LiteTable Error: Table name not defined. Set \$tableName in your class or call table('name').");
        }
        return $this->tableName;
    }

    /**
     * Centralized method for query execution.
     */
    protected function executeQuery(string $sql, array $params = [])
    {
        $pdo = $this->db->getPdo();
        try {
            $stmt = $pdo->prepare($sql);
            
            foreach ($params as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                
                $bindKey = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($bindKey, $value, $paramType);
            }

            $stmt->execute();

            $this->numCols = $stmt->columnCount();
            $this->numRows = $stmt->rowCount();

            return $stmt;
        } catch (PDOException $e) {
            throw new RuntimeException("Database Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Insert a new record into the table.
     */
    public function insert(array $obj): bool
    {
        $tableName = $this->getTable();
        $keys = array_keys($obj);
        $strKeys = implode(', ', $keys);
        $strBinds = implode(', ', array_map(fn($k) => ":{$k}", $keys));

        $sql = "INSERT INTO {$tableName} ({$strKeys}) VALUES ({$strBinds});";
        
        $pdo = $this->db->getPdo();
        
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($obj);
            
            $this->lastInsertId = (int)$pdo->lastInsertId();
            $this->numCols = $stmt->columnCount();
            $this->numRows = $stmt->rowCount();

            return true;
        } catch (PDOException $e) {
            throw new RuntimeException("Insert Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Update an existing record by ID.
     */
    public function update(array $obj, int|string $id): bool
    {
        $tableName = $this->getTable();
        $setClauses = [];
        $values = [];

        foreach ($obj as $key => $value) {
            $setClauses[] = "{$key} = :{$key}";
            $values[$key] = $value;
        }
        
        $setStr = implode(', ', $setClauses);
        $values['id'] = $id;

        $sql = "UPDATE {$tableName} 
                SET {$setStr}
                WHERE {$this->primaryKey} = :id;";

        $stmt = $this->executeQuery($sql, $values);
        return $stmt->rowCount() > 0 || $stmt->execute();
    }

    /**
     * Update records matching custom conditions.
     */
    public function updateWhere(array $obj, array $conditions): bool
    {
        $tableName = $this->getTable();
        $setClauses = [];
        $params = [];

        foreach ($obj as $key => $value) {
            $setClauses[] = "{$key} = :set_{$key}";
            $params["set_{$key}"] = $value;
        }
        
        $whereClauses = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "{$key} = :cond_{$key}";
            $params["cond_{$key}"] = $value;
        }

        $setStr = implode(', ', $setClauses);
        $whereStr = implode(' AND ', $whereClauses);

        $sql = "UPDATE {$tableName} 
                SET {$setStr}
                WHERE {$whereStr};";

        $stmt = $this->executeQuery($sql, $params);
        return (bool)$stmt;
    }

    /**
     * Find a single record by its primary key.
     */
    public function find(int|string $id): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} WHERE {$this->primaryKey} = :id LIMIT 1;";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Find all records except those matching specific conditions.
     */
    public function findAllExcept(array $conditions): array
    {
        $tableName = $this->getTable();
        $clauses = ["1 = 1"];
        $params = [];

        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} != :{$column}";
            $params[":{$column}"] = $value;
        }

        $whereSql = implode(' AND ', $clauses);
        $sql = "SELECT * FROM {$tableName} WHERE {$whereSql};";

        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Find all records in the table.
     */
    public function findAll(): array
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName};";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Find records matching given conditions.
     */
    public function findWhere(array $conditions): array
    {
        $tableName = $this->getTable();
        $clauses = ["1 = 1"];
        $params = [];

        foreach ($conditions as $column => $value) {
            $clauses[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $whereSql = implode(' AND ', $clauses);
        $sql = "SELECT * FROM {$tableName} WHERE {$whereSql};";

        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Find a single record by a specific field and value.
     */
    public function findByField(string $field, mixed $value): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} WHERE {$field} = :value LIMIT 1;";
        $stmt = $this->executeQuery($sql, [':value' => $value]);
        
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Find records where a column falls between a minimum and maximum value.
     */
    public function findAllBetween(string $column, mixed $minValue, mixed $maxValue, array $extraConditions = []): array
    {
        $tableName = $this->getTable();
        $clauses = ["{$column} BETWEEN :min_val AND :max_val"];
        $params = [
            ':min_val' => $minValue,
            ':max_val' => $maxValue
        ];

        foreach ($extraConditions as $col => $val) {
            $clauses[] = "{$col} = :ext_{$col}";
            $params[":ext_{$col}"] = $val;
        }

        $whereSql = implode(' AND ', $clauses);
        $sql = "SELECT * FROM {$tableName} WHERE {$whereSql};";

        $stmt = $this->executeQuery($sql, $params);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Find a record where a value falls between two boundary columns.
     */
    public function findByRange(string $minColumn, string $maxColumn, mixed $value): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} 
                WHERE :val BETWEEN {$minColumn} AND {$maxColumn} 
                LIMIT 1;";
        
        $stmt = $this->executeQuery($sql, [':val' => $value]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Find multiple records where a value falls between two boundary columns.
     */
    public function findAllByRange(string $minColumn, string $maxColumn, mixed $value): array
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} 
                WHERE :val BETWEEN {$minColumn} AND {$maxColumn};";
        
        $stmt = $this->executeQuery($sql, [':val' => $value]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     * Delete a record permanently by ID.
     */
    public function delete(int|string $id): bool
    {
        $tableName = $this->getTable();
        $sql = "DELETE FROM {$tableName} WHERE {$this->primaryKey} = :id;";
        $stmt = $this->executeQuery($sql, [':id' => $id]);
        return (bool)$stmt;
    }

    /**
     * Delete records matching conditions.
     */
    public function deleteWhere(array $conditions): bool
    {
        $tableName = $this->getTable();
        
        $whereClauses = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $whereStr = implode(' AND ', $whereClauses);
        $sql = "DELETE FROM {$tableName} WHERE {$whereStr};";

        $stmt = $this->executeQuery($sql, $params);
        return (bool)$stmt;
    }

    /**
     * Delete records by a specific field and value.
     */
    public function deleteByField(string $field, mixed $value): bool
    {
        return $this->deleteWhere([$field => $value]);
    }

    /**
     * Check if records exist matching conditions.
     */
    public function exists(array $conditions): bool
    {
        $tableName = $this->getTable();
        $whereClauses = [];
        $params = [];
        foreach ($conditions as $key => $value) {
            $whereClauses[] = "{$key} = :{$key}";
            $params[$key] = $value;
        }
        
        $whereStr = implode(' AND ', $whereClauses);
        $sql = "SELECT 1 FROM {$tableName} WHERE {$whereStr} LIMIT 1;";
        
        $stmt = $this->executeQuery($sql, $params);
        return $stmt->rowCount() > 0;
    }

    /**
     * Count total records in the table.
     */
    public function count(): int
    {
        $tableName = $this->getTable();
        $sql = "SELECT COUNT(*) FROM {$tableName};";
        $stmt = $this->executeQuery($sql);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Count records matching specific conditions.
     */
    public function countWhere(array $conditions = []): int
    {
        $tableName = $this->getTable();
        $clauses = ["1 = 1"];
        $params = [];

        foreach ($conditions as $column => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            $clauses[] = "{$column} = :{$column}";
            $params[":{$column}"] = $value;
        }

        $whereSql = implode(' AND ', $clauses);
        $sql = "SELECT COUNT(*) FROM {$tableName} WHERE {$whereSql};";
        
        $stmt = $this->executeQuery($sql, $params);
        return (int)$stmt->fetchColumn();
    }

    public function getLastInsertId(): ?int { return $this->lastInsertId; }
    public function getNumRows(): int { return $this->numRows; }
    public function getNumCols(): int { return $this->numCols; }
}
