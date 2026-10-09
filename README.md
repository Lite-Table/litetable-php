# LiteTable (PHP)

**LiteTable** is a lightweight, high-performance database access library for PHP. It is built for developers who want the simplicity and speed of native `PDO` and objects without the heavy boilerplate and performance traps of traditional ORMs.

---

## 🚀 Why LiteTable?

* **Zero Heavy Hydration:** Results are fetched directly as standard PHP objects (`PDO::FETCH_OBJ`). No complex mapping overhead.
* **SQL First & Secure:** Full control over your queries using native prepared statements exclusively. Zero risk of SQL injection.
* **Predictable & Fast:** No hidden magic, no automatic *change tracking*, and zero N+1 query surprises. What you write is exactly what gets executed.
* **Modern & Clean:** Requires PHP 8.1+ with strict typing, clean object-oriented design, and modular architecture.

---

## 📦 Installation

Install via Composer:

```bash
composer require litetable/litetable

```

---

## 🔌 1. Establish a Database Connection

The `Database` class wraps `PDO` with secure defaults (exceptions enabled, emulated prepares disabled).

```php
use LiteTable\Database;

$db = new Database(
    dsn: 'mysql:host=localhost;dbname=my_database;charset=utf8mb4',
    username: 'root',
    password: 'secret_password'
);

```

---

## 📋 2. Table (Quick CRUD & Batch Operations)

The `Table` class handles standard table operations instantly. You can use it fluently or extend it in your models.

```php
use LiteTable\Table;

// Instantiate fluently for the 'users' table
$usersTable = (new Table($db))->table('users');

// Insert a record
$usersTable->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
$userId = $usersTable->getLastInsertId();

// Find a record by primary key (returns an object or null)
$user = $usersTable->find($userId);

// Update a record by ID
$usersTable->update([
    'name' => 'Johnathan Doe'
], $userId);

// Batch insert support
$usersTable->insertBatch([
    ['name' => 'Alice', 'email' => 'alice@example.com'],
    ['name' => 'Bob', 'email' => 'bob@example.com']
]);

// Delete a record
// $usersTable->delete($userId);

```

---

## 🔍 3. Query (Fluent Read-Only Builder)

When you need custom filters, joins, or aggregations, use the `Query` builder for clean, expressive, and safe queries.

```php
use LiteTable\Query;

// Fetch multiple rows using fluent conditions
$activeAdmins = Query::table('users', $db)
    ->select(['id', 'name', 'email'])
    ->where('status', '=', 'active')
    ->and('role', '=', 'admin')
    ->orderBy('name', 'ASC')
    ->all();

foreach ($activeAdmins as $admin) {
    echo $admin->name . "\n";
}

// Fetch a single row with IN clauses
$singleUser = Query::table('users', $db)
    ->where('status', '=', 'active')
    ->andIn('id', [1, 2, 3])
    ->one();

// Get scalar values (e.g., aggregations)
$totalActive = Query::table('users', $db)
    ->select('COUNT(*)')
    ->where('status', '=', 'active')
    ->value();

```

---

## 📄 License

Open-source software licensed under the [MIT license](https://www.google.com/search?q=LICENSE).