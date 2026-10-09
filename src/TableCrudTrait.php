<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;

trait TableCrudTrait
{
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

    public function insertBatch(array $rows): bool
    {
        if (empty($rows)) {
            return false;
        }

        $tableName = $this->getTable();
        $keys = array_keys(reset($rows));
        $strKeys = implode(', ', $keys);
        
        $sqlValues = [];
        $params = [];
        $paramIndex = 0;

        foreach ($rows as $row) {
            $rowBinds = [];
            foreach ($keys as $key) {
                $paramName = "p_{$paramIndex}";
                $rowBinds[] = ":{$paramName}";
                $params[$paramName] = $row[$key] ?? null;
                $paramIndex++;
            }
            $sqlValues[] = '(' . implode(', ', $rowBinds) . ')';
        }

        $strValues = implode(', ', $sqlValues);
        $sql = "INSERT INTO {$tableName} ({$strKeys}) VALUES {$strValues};";

        $stmt = $this->executeQuery($sql, $params);
        return (bool)$stmt;
    }

    public function replace(array $data): bool
    {
        $tableName = $this->getTable();
        $keys = array_keys($data);
        $strKeys = implode(', ', $keys);
        $strBinds = implode(', ', array_map(fn($k) => ":{$k}", $keys));

        $sql = "REPLACE INTO {$tableName} ({$strKeys}) VALUES ({$strBinds});";
        
        $pdo = $this->db->getPdo();
        $stmt = $this->executeQuery($sql, $data);
        
        $this->lastInsertId = (int)$pdo->lastInsertId();
        return (bool)$stmt;
    }

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

    public function delete(int|string $id): bool
    {
        $tableName = $this->getTable();
        $sql = "DELETE FROM {$tableName} WHERE {$this->primaryKey} = :id;";
        $stmt = $this->executeQuery($sql, [':id' => $id]);
        return (bool)$stmt;
    }

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

    public function deleteIn(array $ids): bool
    {
        if (empty($ids)) {
            return false;
        }

        $tableName = $this->getTable();
        $params = [];
        $bindKeys = [];

        foreach ($ids as $index => $id) {
            $paramName = "id_{$index}";
            $bindKeys[] = ":{$paramName}";
            $params[$paramName] = $id;
        }

        $strBinds = implode(', ', $bindKeys);
        $sql = "DELETE FROM {$tableName} WHERE {$this->primaryKey} IN ({$strBinds});";

        $stmt = $this->executeQuery($sql, $params);
        return (bool)$stmt;
    }

    public function deleteByField(string $field, mixed $value): bool
    {
        return $this->deleteWhere([$field => $value]);
    }
}
