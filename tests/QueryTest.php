<?php

declare(strict_types=1);

use LiteTable\Database;
use LiteTable\Query;
use LiteTable\Testing\Test;

Test::describe('LiteTable Query Builder Tests', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $pdo->exec("
        CREATE TABLE users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            status TEXT
        )
    ");

    $db = new Database($pdo);

    $pdo->exec("INSERT INTO users (name, email, status) VALUES ('Alice', 'alice@test.com', 'active')");
    $pdo->exec("INSERT INTO users (name, email, status) VALUES ('Bob', 'bob@test.com', 'inactive')");
    $pdo->exec("INSERT INTO users (name, email, status) VALUES ('Charlie', 'charlie@test.com', 'active')");

    // 1. Test fluent query generation and execution (all())
    $query = Query::table('users', $db)->select('*')->where('status', '=', 'active');
    Test::assertSqlContains('SELECT *', $query->getQuery());
    
    $results = $query->all();
    Test::assertCount(2, $results, 'Query all() returns correct number of active users');
    Test::assertEquals('Alice', $results[0]->name);
    Test::ok('Fluent select and where clauses executed successfully');

    // 2. Test single result execution (one())
    $user = Query::table('users', $db)->select('*')->where('name', '=', 'Bob')->one();
    Test::assertObjectHasAttribute('email', $user);
    Test::assertEquals('bob@test.com', $user->email);
    Test::ok('Query one() retrieves a single object correctly');

    // 3. Test scalar execution (value())
    $count = Query::table('users', $db)->select('COUNT(*)')->value();
    Test::assertEquals(3, (int)$count, 'Query value() returns scalar aggregate correctly');
    Test::ok('Query scalar value execution passed');

    // 4. Test exists check
    $exists = Query::table('users', $db)->from('users')->where('status', '=', 'inactive')->exists();
    Test::assert($exists, 'Query exists() returns true when records match');
    Test::ok('Query exists check passed');

    // 5. Test IN clause
    $inResults = Query::table('users', $db)->select('*')->in('name', ['Alice', 'Charlie'])->all();
    Test::assertCount(2, $inResults, 'Query IN clause filters correctly');
    Test::ok('Query IN clause execution passed');

    // 6. Test security protection on raw()
    $failedSafeguard = false;
    try {
        Query::table('users', $db)->raw('DELETE FROM users');
    } catch (\RuntimeException $e) {
        $failedSafeguard = true;
    }
    Test::assert($failedSafeguard, 'Query raw() successfully blocks write operations');
    Test::ok('Query security safeguard validated');
});
