<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LiteTable\Database;
use LiteTable\Table;

// Setup a file-based or in-memory SQLite connection for demonstration
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create sample table
$pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        email TEXT,
        status TEXT
    )
");

$db = new Database($pdo);
$table = new Table($db);
$table->table('users');

echo "----------------------------------------\n";
echo " LITE-TABLE: TABLE CRUD EXAMPLE\n";
echo "----------------------------------------\n\n";

// 1. Single Insert
$table->insert([
    'name' => 'Alice Smith',
    'email' => 'alice@example.com',
    'status' => 'active'
]);
echo "✔ Inserted single user. Total records: " . $table->count() . "\n";

// 2. Batch Insert
$table->insertBatch([
    ['name' => 'Bob Jones', 'email' => 'bob@example.com', 'status' => 'pending'],
    ['name' => 'Charlie Brown', 'email' => 'charlie@example.com', 'status' => 'active']
]);
echo "✔ Inserted batch. Total records: " . $table->count() . "\n";

// 3. Find Operations
$user = $table->find(1);
echo "✔ Found User ID 1: {$user->name} ({$user->email})\n";

$byField = $table->findByField('email', 'bob@example.com');
echo "✔ Found by Field (Bob): ID {$byField->id} - {$byField->name}\n";

// 4. Update Operations
$table->update(['name' => 'Alice S. Updated'], 1);
$updatedUser = $table->find(1);
echo "✔ Updated User ID 1 name to: {$updatedUser->name}\n";

// 5. First and Last records
$first = $table->first();
$last = $table->last();
echo "✔ First record: {$first->name} | Last record: {$last->name}\n";

// 6. Delete Operations
$table->delete(2); // Deletes Bob
echo "✔ Deleted User ID 2. Remaining records: " . $table->count() . "\n";

echo "\n----------------------------------------\n";
echo " Table CRUD example executed successfully!\n";
echo "----------------------------------------\n";