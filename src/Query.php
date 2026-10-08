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
        return $instance->from($table);
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
     * Append a custom raw statement string.
     */
    public function statement(string $stmt): self
    {
        $this->queryResult .= " {$stmt} ";
        return $this;
    }

    /**
     * Append a raw SQL fragment with optional secure bindings.
     */
    public function raw(string $sql, array $bindings = []): self
    {
        $this->queryResult .= " {$sql} ";
        
        foreach ($bindings as $key => $value) {
            if (is_string($key)) {
                $this->bindings[$key] = $value;
            } else {
                $paramName = "r_" . count($this->bindings);
                $this->bindings[$paramName] = $value;
            }
        }
        
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
                    return ($this->numRows > 0);
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
