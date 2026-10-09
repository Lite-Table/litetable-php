<?php

declare(strict_types=1);

namespace LiteTable\Testing;

class Test
{
    private static int $passed = 0;
    private static int $failed = 0;

    /**
     * Group a set of tests under a descriptive title.
     */
    public static function describe(string $title, callable $callback): void
    {
        echo "\033[36m[TEST] " . $title . "\033[0m\n";
        try {
            $callback();
        } catch (\Throwable $e) {
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m " . $e->getMessage() . "\n\n";
            throw $e;
        }
    }

    /**
     * Assert that a boolean condition is true.
     */
    public static function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            self::$failed++;
            echo "  \033[31m✖ Assertion Failed:\033[0m {$message}\n";
            throw new \Exception("Assertion Failed: {$message}");
        }
        self::$passed++;
    }

    /**
     * Assert that expected and actual values are strictly equal.
     */
    public static function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        if ($expected !== $actual) {
            $msg = $message ?: "Expected [" . var_export($expected, true) . "], got [" . var_export($actual, true) . "]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    /**
     * Assert that a generated SQL query or count matches expectations.
     */
    public static function assertCount(int $expected, int|array $actualValue, string $message = ''): void
    {
        $actual = is_array($actualValue) ? count($actualValue) : $actualValue;
        if ($expected !== $actual) {
            $msg = $message ?: "Expected count [{$expected}], got [{$actual}]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    /**
     * Assert that an object is not null and contains a specific property.
     */
    public static function assertObjectHasAttribute(string $property, object|null $object, string $message = ''): void
    {
        if ($object === null || !property_exists($object, $property)) {
            $msg = $message ?: "Object is null or does not have property [{$property}]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    /**
     * Assert that a value is strictly null.
     */
    public static function assertNull(mixed $actual, string $message = ''): void
    {
        if ($actual !== null) {
            $msg = $message ?: "Expected null, got [" . var_export($actual, true) . "]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    /**
     * Assert that a generated SQL string contains a specific clause or keyword.
     */
    public static function assertSqlContains(string $expectedSubstring, string $actualSql, string $message = ''): void
    {
        if (!str_contains(strtoupper($actualSql), strtoupper($expectedSubstring))) {
            $msg = $message ?: "Expected SQL to contain [{$expectedSubstring}], got [{$actualSql}]";
            self::$failed++;
            echo "  \033[31m✖ Failed:\033[0m {$msg}\n";
            throw new \Exception($msg);
        }
        self::$passed++;
    }

    /**
     * Output a success checkmark for a specific sub-test.
     */
    public static function ok(string $message): void
    {
        echo "  \033[32m✔\033[0m {$message}\n";
    }

    /**
     * Print the final test execution summary and exit with status if failures occurred.
     */
    public static function summary(): void
    {
        echo "\n----------------------------------------\n";
        echo " Results: \033[32m" . self::$passed . " passed\033[0m, \033[31m" . self::$failed . " failed\033[0m\n";
        echo "----------------------------------------\n";
        
        if (self::$failed > 0) {
            exit(1);
        }
    }
}
