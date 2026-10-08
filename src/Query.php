<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;
use PDOStatement;

class Query
{
    private Database $db;
    private string $queryResult = "";
    private array $bindings = [];
    private int $numRows = 0;
    private int $numCols = 0;

    public function __construct(Database $db)
    {
        $this->db = $db;
        $this->reset();
    }

    private function reset(): void
    {
        $this->queryResult = "";
        $this->bindings = [];
    }

    /**
     * Static factory method to start a query fluently (e.g., Query::table('users', $db))
     */
    public static function table(string $table, Database $db): self
    {
        $instance = new self($db);
        return $instance->from($table);
    }

    public function select($fields = "*"): self
    {
        if (is_array($fields)) {
            $fields = implode(", ", $fields);
        }
        $this->queryResult .= " SELECT {$fields} ";
        return $this;
    }

    public function from(string $table): self
    {
        $this->queryResult .= " FROM {$table} ";
        return $this;
    }

    public function join(string $table, ?string $on = null, string $type = "INNER"): self
    {
        $strOn = ($on === null) ? "" : " ON ({$on}) ";
        $this->queryResult .= " {$type} JOIN {$table} {$strOn} ";
        return $this;
    }

    public function leftJoin(string $table, ?string $on = null): self
    {
        return $this->join($table, $on, "LEFT");
    }

    public function rightJoin(string $table, ?string $on = null): self
    {
        return $this->join($table, $on, "RIGHT");
    }

    public function where(string $field, string $operator, $value, string $boolean = "WHERE"): self
    {
        $paramName = "w_" . count($this->bindings);
        $this->queryResult .= " {$boolean} {$field} {$operator} :{$paramName} ";
        $this->bindings[$paramName] = $value;
        return $this;
    }

    public function and(string $field, string $operator, $value): self
    {
        return $this->where($field, $operator, $value, "AND");
    }

    public function or(string $field, string $operator, $value): self
    {
        return $this->where($field, $operator, $value, "OR");
    }

    public function orderBy(string $field, string $ordem = "ASC"): self
    {
        $this->queryResult .= " ORDER BY {$field} {$ordem} ";
        return $this;
    }

    public function groupBy(string $field): self
    {
        $this->queryResult .= " GROUP BY {$field} ";
        return $this;
    }

    public function limit(int $start, int $end): self
    {
        $this->queryResult .= " LIMIT {$start}, {$end} ";
        return $this;
    }

    public function statement(string $stmt): self
    {
        $this->queryResult .= " {$stmt} ";
        return $this;
    }

    public function getQuery(): string
    {
        return trim($this->queryResult);
    }

    public function getNumRows(): int
    {
        return $this->numRows;
    }

    public function getNumCols(): int
    {
        return $this->numCols;
    }

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
                case 'insert':
                case 'create':
                case 'add':
                    return (int)$pdo->lastInsertId();
                case 'update':
                case 'edit':
                case 'delete':
                case 'remove':
                    return true;
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

    public function add(): bool { return (bool)$this->execute('add'); }
    public function edit(): bool { return (bool)$this->execute('update'); }
    public function remove(): bool { return (bool)$this->execute('delete'); }
    public function all(): array { return $this->execute('all'); }
    
    public function one(): ?object 
    { 
        $result = $this->execute('one');
        return is_object($result) ? $result : null; 
    }

    /**
     * Retorna um único valor escalar (ex: COUNT, SUM, AVG)
     */
    public function value()
    {
        return $this->execute('value');
    }

    public function exists(): bool { return $this->execute('exists'); }
}
