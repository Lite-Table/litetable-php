<?php

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;
use PDOStatement;
use Throwable;

class Database
{
    private PDO $pdo;

    /**
     * Constructor accepts either an existing PDO instance or connection parameters.
     */
    public function __construct(PDO|string $dsn, ?string $username = null, ?string $password = null, array $options = [])
    {
        if ($dsn instanceof PDO) {
            $this->pdo = $dsn;
            return;
        }

        // Default secure PDO options for performance and safety
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $options = array_replace($defaultOptions, $options);

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("LiteTable Database Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Static factory method for fluent instantiation (e.g., Database::make(...))
     */
    public static function make(PDO|string $dsn, ?string $username = null, ?string $password = null, array $options = []): self
    {
        return new self($dsn, $username, $password, $options);
    }

    /**
     * Alias for make() if preferred.
     */
    public static function connect(PDO|string $dsn, ?string $username = null, ?string $password = null, array $options = []): self
    {
        return new self($dsn, $username, $password, $options);
    }

    /**
     * Returns the raw native PDO instance.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Helper to quickly prepare statements directly from the database wrapper.
     */
    public function prepare(string $sql, array $options = []): PDOStatement
    {
        return $this->pdo->prepare($sql, $options);
    }

    /**
     * Initiates a transaction.
     */
    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Commits a transaction.
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Rolls back a transaction.
     */
    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Checks if inside a transaction.
     */
    public function inTransaction(): bool
    {
        return $this->pdo->inTransaction();
    }

    /**
     * Executes a callback within a database transaction.
     * Automatically commits on success or rolls back on exception.
     * 
     * @template T
     * @param callable(self): T $callback
     * @return T
     * @throws Throwable
     */
    public function transaction(callable $callback)
    {
        $this->beginTransaction();

        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->inTransaction()) {
                $this->rollBack();
            }
            throw $e;
        }
    }
}
