<?php

declare(strict_types=1);

use LiteTable\Database;
use LiteTable\Testing\Test;

// Mock implementation using LiteTable traits for testing CRUD operations
class TestUserRepository {
    use \LiteTable\TableCrudTrait;
    use \LiteTable\TableFinderTrait;
    use \LiteTable\TableCountTrait;

    private $db;
    public string $primaryKey = 'id';
    public int $lastInsertId = 0;
    public int $numCols = 0;
    public int $numRows = 0;

    public function __construct(Database $db) {
        $this->db = $db;
    }

    public function getTable(): string {
        return 'users';
    }

    public function executeQuery(string $sql, array $params = []) {
        $pdo = $this->db->getPdo();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

Test::describe('LiteTable Table Traits & CRUD Operations', function () {
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
    $repo = new TestUserRepository($db);

    // 1. Test Insert and Count
    $repo->insert(['name' => 'Alice', 'email' => 'alice@test.com', 'status' => 'active']);
    Test::assertCount(1, $repo->count(), 'Count returns 1 after insert');
    Test::ok('Insert and count operations passed');

    // 2. Test Find
    $user = $repo->find(1);
    Test::assertObjectHasAttribute('name', $user);
    Test::assertEquals('Alice', $user->name, 'Find retrieves correct object property');
    Test::ok('Find operation passed');

    // 3. Test Exists and CountWhere
    $exists = $repo->exists(['status' => 'active']);
    Test::assert($exists, 'Exists returns true for active status');
    Test::assertEquals(1, $repo->countWhere(['status' => 'active']), 'CountWhere filters correctly');
    Test::ok('Exist and countWhere operations passed');

    // 4. Test InsertBatch and DeleteIn
    $repo->insertBatch([
        ['name' => 'Bob', 'email' => 'bob@test.com', 'status' => 'pending'],
        ['name' => 'Charlie', 'email' => 'charlie@test.com', 'status' => 'pending']
    ]);
    Test::assertCount(3, $repo->count(), 'Count matches total after batch insert');

    $repo->deleteIn([2, 3]);
    Test::assertCount(1, $repo->count(), 'DeleteIn removes specified records');
    Test::ok('Batch insert and deleteIn operations passed');
});
