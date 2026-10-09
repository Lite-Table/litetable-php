<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;

trait TableFinderTrait
{
    public function find(int|string $id): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} WHERE {$this->primaryKey} = :id LIMIT 1;";
        $stmt = $this->executeQuery($sql, ['id' => $id]);
        
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

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

    public function findAll(): array
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName};";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

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
    
    public function findByField(string $field, mixed $value): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} WHERE {$field} = :value LIMIT 1;";
        $stmt = $this->executeQuery($sql, [':value' => $value]);
        
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

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

    public function findByRange(string $minColumn, string $maxColumn, mixed $value): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} 
                WHERE :val BETWEEN {$minColumn} AND {$maxColumn} 
                LIMIT 1;";
        
        $stmt = $this->executeQuery($sql, [':val' => $value]);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function findAllByRange(string $minColumn, string $maxColumn, mixed $value): array
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} 
                WHERE :val BETWEEN {$minColumn} AND {$maxColumn};";
        
        $stmt = $this->executeQuery($sql, [':val' => $value]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
}
