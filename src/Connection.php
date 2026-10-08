<?php

namespace LiteTable;

use PDO;
use PDOException;
use RuntimeException;
use PDOStatement;

class Connection
{
    private PDO $pdo;

    /**
     * Constructor accepts either an existing PDO instance or connection parameters.
     * 
     * @param PDO|string $dsn PDO instance or DSN connection string
     * @param string|null $username Database username
     * @param string|null $password Database password
     * @param array $options Additional PDO options
     * @param int $defaultFetchMode Default fetch mode (e.g., PDO::FETCH_ASSOC, PDO::FETCH_OBJ)
     */
    public function __construct(
        PDO|string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
        int $defaultFetchMode = PDO::FETCH_ASSOC
    ) {
        if ($dsn instanceof PDO) {
            $this->pdo = $dsn;
            return;
        }

        // Default secure PDO options for performance and safety
        $defaultOptions = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => $defaultFetchMode,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $options = array_replace($defaultOptions, $options);

        try {
            $this->pdo = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            throw new RuntimeException("LiteTable Connection Error: " . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Static factory method for fluent instantiation (e.g., Connection::make(...))
     */
    public static function make(
        PDO|string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
        int $defaultFetchMode = PDO::FETCH_ASSOC
    ): self {
        return new self($dsn, $username, $password, $options, $defaultFetchMode);
    }

    /**
     * Alias for make() if preferred.
     */
    public static function connect(
        PDO|string $dsn,
        ?string $username = null,
        ?string $password = null,
        array $options = [],
        int $defaultFetchMode = PDO::FETCH_ASSOC
    ): self {
        return new self($dsn, $username, $password, $options, $defaultFetchMode);
    }

    /**
     * Returns the raw native PDO instance.
     */
    public function getPdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * Helper to quickly prepare statements directly from the connection wrapper.
     */
    public function prepare(string $sql, array $options = []): PDOStatement
    {
        return $this->pdo->prepare($sql, $options);
    }
}
