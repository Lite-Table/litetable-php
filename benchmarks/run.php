<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use LiteTable\Database;
use LiteTable\Table;
use LiteTable\Query;
use LiteTable\Benchmark\Benchmark;

// Setup in-memory SQLite database for fast execution
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        price REAL
    )
");

$db = new Database($pdo);

// 1. Benchmark for insert operations using the Table CRUD abstraction
Benchmark::measure('Table Insert Performance', function ($i) use ($db) {
    $table = new Table($db, 'products');
    $table->insert([
        'name' => "Product {$i}",
        'price' => rand(10, 500) + 0.99
    ]);
}, 5000);

// 2. Benchmark for fluent queries and conditional selection using the Query builder
Benchmark::measure('Query Builder Select Performance', function ($i) use ($db) {
    Query::table('products', $db)
        ->select('*')
        ->where('price', '>', 100.0)
        ->one();
}, 2000);
