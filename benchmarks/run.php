<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/Benchmark.php';

use LiteTable\Database;
use LiteTable\Table;
use LiteTable\Query;
use LiteTable\Benchmark\Benchmark;

// Setup high-performance in-memory SQLite database for benchmarking
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("
    CREATE TABLE products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT,
        price REAL,
        status TEXT
    )
");

$pdo->exec("
    CREATE TABLE categories (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER,
        category_name TEXT
    )
");

$db = new Database($pdo);
$table = new Table($db);
$table->table('products');

// Pre-seed some initial data for lookup, update, delete, and query tests
for ($j = 1; $j <= 1000; $j++) {
    $pdo->exec("INSERT INTO products (name, price, status) VALUES ('Pre-Product {$j}', " . (rand(10, 1000) + 0.99) . ", 'active')");
    $pdo->exec("INSERT INTO categories (product_id, category_name) VALUES ({$j}, 'Electronics')");
}

// ==========================================
// 1. CRUD BENCHMARKS
// ==========================================

// Benchmark 1.1: Insert (Table::insert)
Benchmark::measure('CRUD - Table Insert Performance', function ($i) use ($table) {
    $table->table('products')->insert([
        'name' => "Product New {$i}",
        'price' => rand(10, 1000) + 0.99,
        'status' => 'active'
    ]);
}, 5000);

// Benchmark 1.2: Find (Table::find)
Benchmark::measure('CRUD - Table Find Performance', function ($i) use ($table) {
    $randomId = rand(1, 800);
    $table->table('products')->find($randomId);
}, 10000);

// Benchmark 1.3: Update (Table::update)
Benchmark::measure('CRUD - Table Update Performance', function ($i) use ($table) {
    $randomId = rand(1, 800);
    $table->table('products')->update(['price' => 199.99], $randomId);
}, 5000);

// Benchmark 1.4: Delete (Table::delete)
Benchmark::measure('CRUD - Table Delete Performance', function ($i) use ($table) {
    // Delete items from the upper range to avoid missing rows during lookups
    $targetId = 801 + ($i % 200);
    $table->table('products')->delete($targetId);
}, 500);


// ==========================================
// 2. QUERY BUILDER BENCHMARKS (Advanced Scenarios)
// ==========================================

// Benchmark 2.1: Complex Select with JOIN, OrderBy, and Limit
Benchmark::measure('Query Builder - Complex Select with JOIN', function ($i) use ($db) {
    (new Query($db))
        ->select('products.name, categories.category_name, products.price')
        ->from('products')
        ->join('categories', 'products.id = categories.product_id', 'INNER')
        ->where('products.price', '>', 500.0)
        ->orderBy('products.price', 'DESC')
        ->limit(10, 0)
        ->all();
}, 3000);

// Benchmark 2.2: IN Clause filtering
Benchmark::measure('Query Builder - IN Clause Performance', function ($i) use ($db) {
    (new Query($db))
        ->select('*')
        ->from('products')
        ->in('id', [10, 20, 30, 40, 50])
        ->all();
}, 5000);

// Benchmark 2.3: Exists check performance
Benchmark::measure('Query Builder - Exists Check Performance', function ($i) use ($db) {
    Query::table('products', $db)
        ->where('status', '=', 'active')
        ->exists();
}, 5000);

// Benchmark 2.4: Safe Raw SQL fragment performance
Benchmark::measure('Query Builder - Safe Raw SQL Performance', function ($i) use ($db) {
    (new Query($db))
        ->select('id, name')
        ->from('products')
        ->raw('WHERE price < ? AND status = ?', [200.0, 'active'])
        ->all();
}, 3000);