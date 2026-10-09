<?php

declare(strict_types=1);

namespace LiteTable;

trait TableCountTrait
{
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
        $result = $stmt->fetch();
        
        return $result !== false && $result !== null;
    }

    public function count(): int
    {
        $tableName = $this->getTable();
        $sql = "SELECT COUNT(*) FROM {$tableName};";
        $stmt = $this->executeQuery($sql);
        return (int)$stmt->fetchColumn();
    }

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
}
