<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;

class Query
{
    private Database $db;
    private string $queryResult = "";
    private array $bindings = [];
    private int $numRows = 0;
    private int $numCols = 0;

    /**
     * Initialize the query builder with a database instance.
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->reset();
    }

    /**
     * Reset query state and bindings.
     */
    private function reset(): void
    {
        $this->queryResult = "";
        $this->bindings = [];
    }

    /**
     * Static factory method to start a query fluently for a specific table.
     */
    public static function table(string $table, Database $db): self
    {
        $instance = new self($db);
        return $instance->select("*")->from($table);
    }
    
    /**
     * Append a SELECT clause to the query.
     */
    public function select($fields = "*"): self
    {
        if (is_array($fields)) {
            $fields = implode(", ", $fields);
        }
        $this->queryResult .= " SELECT {$fields} ";
        return $this;
    }

    /**
     * Append a FROM clause to the query.
     */
    public function from(string $table): self
    {
        $this->queryResult .= " FROM {$table} ";
        return $this;
    }

    /**
     * Append a JOIN clause to the query.
     */
    public function join(string $table, ?string $on = null, string $type = "INNER"): self
    {
        $strOn = ($on === null) ? "" : " ON ({$on}) ";
        $this->queryResult .= " {$type} JOIN {$table} {$strOn} ";
        return $this;
    }

    /**
     * Append a LEFT JOIN clause to the query.
     */
    public function leftJoin(string $table, ?string $on = null): self
    {
        return $this->join($table, $on, "LEFT");
    }

    /**
     * Append a RIGHT JOIN clause to the query.
     */
    public function rightJoin(string $table, ?string $on = null): self
    {
        return $this->join($table, $on, "RIGHT");
    }

    /**
     * Append a WHERE clause with safe parameter binding.
     */
    public function where(string $field, string $operator, $value, string $boolean = "WHERE"): self
    {
        $paramName = "w_" . count($this->bindings);
        $this->queryResult .= " {$boolean} {$field} {$operator} :{$paramName} ";
        $this->bindings[$paramName] = $value;
        return $this;
    }

    /**
     * Append an AND WHERE clause condition.
     */
    public function and(string $field, string $operator, $value): self
    {
        return $this->where($field, $operator, $value, "AND");
    }

    /**
     * Append an OR WHERE clause condition.
     */
    public function or(string $field, string $operator, $value): self
    {
        return $this->where($field, $operator, $value, "OR");
    }

    /**
     * Append an ORDER BY clause.
     */
    public function orderBy(string $field, string $ordem = "ASC"): self
    {
        $this->queryResult .= " ORDER BY {$field} {$ordem} ";
        return $this;
    }

    /**
     * Append a GROUP BY clause.
     */
    public function groupBy(string $field): self
    {
        $this->queryResult .= " GROUP BY {$field} ";
        return $this;
    }

    /**
     * Append a LIMIT clause.
     */
    public function limit(int $start, int $end): self
    {
        $this->queryResult .= " LIMIT {$start}, {$end} ";
        return $this;
    }

    /**
     * Append an IN clause.
     */
    public function in(string $field, array $values, string $boolean = "WHERE"): self
    {
        if (empty($values)) {
            $this->queryResult .= " {$boolean} 1 = 0 ";
            return $this;
        }

        $paramKeys = [];
        foreach ($values as $value) {
            $paramName = "in_" . count($this->bindings);
            $paramKeys[] = ":{$paramName}";
            $this->bindings[$paramName] = $value;
        }

        $strBinds = implode(', ', $paramKeys);
        $this->queryResult .= " {$boolean} {$field} IN ({$strBinds}) ";
        return $this;
    }

    /**
     * Append a NOT IN clause.
     */
    public function notIn(string $field, array $values, string $boolean = "WHERE"): self
    {
        if (empty($values)) {
            $this->queryResult .= " {$boolean} 1 = 1 ";
            return $this;
        }

        $paramKeys = [];
        foreach ($values as $value) {
            $paramName = "nin_" . count($this->bindings);
            $paramKeys[] = ":{$paramName}";
            $this->bindings[$paramName] = $value;
        }

        $strBinds = implode(', ', $paramKeys);
        $this->queryResult .= " {$boolean} {$field} NOT IN ({$strBinds}) ";
        return $this;
    }

    /**
     * Append an IS NULL clause.
     */
    public function isNull(string $field, string $boolean = "WHERE"): self
    {
        $this->queryResult .= " {$boolean} {$field} IS NULL ";
        return $this;
    }

    /**
     * Append an IS NOT NULL clause.
     */
    public function isNotNull(string $field, string $boolean = "WHERE"): self
    {
        $this->queryResult .= " {$boolean} {$field} IS NOT NULL ";
        return $this;
    }

    /**
     * Append an AND IN clause.
     */
    public function andIn(string $field, array $values): self
    {
        return $this->in($field, $values, "AND");
    }

    /**
     * Append an OR IN clause.
     */
    public function orIn(string $field, array $values): self
    {
        return $this->in($field, $values, "OR");
    }

    /**
     * Append a raw SQL fragment with optional secure bindings.
     */
    public function raw(string $sql, array $bindings = []): self
    {
        $trimmedSql = strtoupper(trim($sql));
        
        // Block write or structural modification commands in the read query class
        $forbiddenKeywords = ['INSERT ', 'UPDATE ', 'DELETE ', 'DROP ', 'ALTER ', 'TRUNCATE ', 'CREATE '];
        foreach ($forbiddenKeywords as $keyword) {
            if (str_starts_with($trimmedSql, $keyword) || str_contains($trimmedSql, "; " . trim($keyword))) {
                throw new RuntimeException("LiteTable Query Error: Write operations are forbidden in Query::raw(). Use Table for modifications.");
            }
        }

        // Handle positional (?) versus named parameters efficiently
        foreach ($bindings as $key => $value) {
            if (is_string($key)) {
                $this->bindings[ltrim($key, ':')] = $value;
            } else {
                $paramName = "r_" . count($this->bindings);
                $pos = strpos($sql, '?');
                if ($pos !== false) {
                    $sql = substr_replace($sql, ":{$paramName}", $pos, 1);
                }
                $this->bindings[$paramName] = $value;
            }
        }

        $this->queryResult .= " {$sql} ";
        
        return $this;
    }
 
    /**
     * Get the final generated SQL query string.
     */
    public function getQuery(): string
    {
        return trim($this->queryResult);
    }

    /**
     * Get the number of affected or returned rows from the last execution.
     */
    public function getNumRows(): int
    {
        return $this->numRows;
    }

    /**
     * Get the number of columns from the last statement execution.
     */
    public function getNumCols(): int
    {
        return $this->numCols;
    }

    /**
     * Prepare, bind, and execute the query against the database.
     */
    public function execute(string $operation = 'all')
    {
        $operation = strtolower($operation);
        $pdo = $this->db->getPdo();

        try {
            $stmt = $pdo->prepare($this->getQuery());
            
            foreach ($this->bindings as $key => $value) {
                $paramType = match (true) {
                    is_int($value) => PDO::PARAM_INT,
                    is_bool($value) => PDO::PARAM_BOOL,
                    is_null($value) => PDO::PARAM_NULL,
                    default => PDO::PARAM_STR,
                };
                
                $stmt->bindValue($key, $value, $paramType);
            }

            $stmt->execute();

            $this->numCols = $stmt->columnCount();
            $this->numRows = $stmt->rowCount();

            switch ($operation) {
                case 'one':
                case 'find':
                    $result = $stmt->fetch();
                    return $result ? (object)$result : null;
                case 'all':
                case 'findall':
                    $results = $stmt->fetchAll();
                    return $results ? array_map(fn($row) => (object)$row, $results) : [];
                case 'value':
                case 'scalar':
                    $result = $stmt->fetchColumn();
                    return $result !== false ? $result : null;
                case 'exists':
                    $result = $stmt->fetch();
                    return $result !== false && $result !== null;
                default:
                    $results = $stmt->fetchAll();
                    return $results ? array_map(fn($row) => (object)$row, $results) : [];
            }
        } catch (PDOException $e) {
            throw new RuntimeException("LiteTable Query Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Fetch all results as an array of objects.
     */
    public function all(): array { return $this->execute('all'); }
    
    /**
     * Fetch a single result as an object.
     */
    public function one(): ?object 
    { 
        $result = $this->execute('one');
        return is_object($result) ? $result : null; 
    }

    /**
     * Fetch a single scalar value (e.g., COUNT, SUM, AVG).
     */
    public function value()
    {
        return $this->execute('value');
    }

    /**
     * Check if any records match the query criteria.
     */
    public function exists(): bool { return $this->execute('exists'); }
}
