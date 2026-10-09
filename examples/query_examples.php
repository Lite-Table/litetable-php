<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LiteTable\Database;
use LiteTable\Query;

// Setup in-memory SQLite database
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create related tables
$pdo->exec("
    CREATE TABLE users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        status TEXT
    )
");

$pdo->exec("
    CREATE TABLE profiles (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER,
        bio TEXT,
        score REAL
    )
");

$db = new Database($pdo);

// Seed mock data
$pdo->exec("INSERT INTO users (name, status) VALUES ('Alice', 'active')");
$pdo->exec("INSERT INTO users (name, status) VALUES ('Bob', 'inactive')");
$pdo->exec("INSERT INTO users (name, status) VALUES ('Charlie', 'active')");

$pdo->exec("INSERT INTO profiles (user_id, bio, score) VALUES (1, 'Backend Developer', 95.5)");
$pdo->exec("INSERT INTO profiles (user_id, bio, score) VALUES (3, 'Frontend Engineer', 88.0)");

echo "----------------------------------------\n";
echo " LITE-TABLE: QUERY BUILDER EXAMPLE\n";
echo "----------------------------------------\n\n";

// 1. Basic Select with Where condition (Usando new Query para colunas específicas)
$activeUsers = (new Query($db))
    ->select('id, name')
    ->from('users')
    ->where('status', '=', 'active')
    ->all();

echo "✔ Active Users Found: " . count($activeUsers) . "\n";
foreach ($activeUsers as $u) {
    echo "  - {$u->name}\n";
}

// 2. Advanced JOIN Query (Users + Profiles)
$joinQuery = (new Query($db))
    ->select('users.name, profiles.bio, profiles.score')
    ->from('users')
    ->join('profiles', 'users.id = profiles.user_id', 'INNER')
    ->where('profiles.score', '>', 90.0)
    ->orderBy('profiles.score', 'DESC');

echo "\n[SQL Generated]: " . $joinQuery->getQuery() . "\n";
$developer = $joinQuery->one();
echo "✔ Top Developer via JOIN: {$developer->name} ({$developer->bio}) with score {$developer->score}\n";

// 3. IN Clause filtering (Usando o atalho Query::table)
$inResults = Query::table('users', $db)
    ->in('name', ['Alice', 'Charlie'])
    ->all();
echo "\n✔ IN clause matched " . count($inResults) . " records.\n";

// 4. Exists Check
$exists = Query::table('users', $db)
    ->where('status', '=', 'inactive')
    ->exists();
echo "✔ Inactive users exist? " . ($exists ? 'Yes' : 'No') . "\n";

// 5. Safe Raw SQL Fragment
$rawResults = Query::table('users', $db)
    ->raw('WHERE name LIKE ?', ['%li%'])
    ->all();
echo "✔ Raw SQL query results count: " . count($rawResults) . "\n";

echo "\n----------------------------------------\n";
echo " Query Builder example executed successfully!\n";
echo "----------------------------------------\n";