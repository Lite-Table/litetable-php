<?php

declare(strict_types=1);

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;

/**
 * @name Table
 * @desc Low-level, lightweight base table operations using native PDO without hidden magic columns.
 */
class Table
{
    use TableCrudTrait;
    use TableFinderTrait;
    use TableCountTrait;

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
     * Get the first record ordered by primary key.
     */
    public function first(): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} ORDER BY {$this->primaryKey} ASC LIMIT 1;";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    /**
     * Get the last record ordered by primary key.
     */
    public function last(): ?object
    {
        $tableName = $this->getTable();
        $sql = "SELECT * FROM {$tableName} ORDER BY {$this->primaryKey} DESC LIMIT 1;";
        $stmt = $this->executeQuery($sql);
        return $stmt->fetch(PDO::FETCH_OBJ) ?: null;
    }

    public function getLastInsertId(): ?int { return $this->lastInsertId; }
    public function getNumRows(): int { return $this->numRows; }
    public function getNumCols(): int { return $this->numCols; }
}
