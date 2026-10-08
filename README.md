# LiteTable (PHP)

**LiteTable** is a lightweight, high-performance database access library for PHP. It is built for developers who want the simplicity and speed of native `PDO` and associative arrays without the heavy boilerplate and performance traps of traditional ORMs.

---

## 🚀 Why LiteTable?

* **Zero Heavy Serialization:** Results are fetched directly as native PHP associative arrays (`PDO::FETCH_ASSOC`). No complex object hydration overhead.
* **SQL First:** Full control over your queries. Write raw SQL when you need performance and precision, or use built-in helpers for standard CRUD operations.
* **Predictable & Fast:** No hidden magic, no automatic *change tracking*, and zero N+1 query surprises. What you write is exactly what gets executed.
* **Modern & Clean:** Requires PHP 8.1+ with strict typing and clean object-oriented design.

---

## 📦 Installation

Install via Composer:

```bash
composer require litetable/litetable

```

---

## 🛠️ Quick Start

### 1. Establish a Connection

The `Connection` class wraps `PDO` with secure defaults (exceptions enabled, emulated prepares disabled, and associative fetch mode by default).

```php
use LiteTable\Connection;

$db = new Connection(
    dsn: 'mysql:host=localhost;dbname=my_database;charset=utf8mb4',
    username: 'root',
    password: 'secret_password'
);

```

---

### 2. TableDb (Quick CRUD Operations)

The `TableDb` class handles standard table operations instantly without writing repetitive SQL.

```php
use LiteTable\TableDb;

// Instantiate for the 'users' table
$usersTable = new TableDb($db, 'users');

// Insert a record (returns the last insert ID)
$userId = $usersTable->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Find a record by primary key (returns an associative array or null)
$user = $usersTable->find($userId);

// Get all records from the table
$allUsers = $usersTable->all();

// Update a record
$usersTable->update($userId, [
    'name' => 'Johnathan Doe'
]);

// Delete a record
// $usersTable->delete($userId);

```

---

### 3. Query (Custom SQL Execution)

When you need custom queries, joins, or aggregations, use the `Query` class for safe parameter binding.

```php
use LiteTable\Query;

$query = new Query($db);

// Fetch multiple rows (returns an array of associative arrays)
$activeAdmins =$query->raw(
    "SELECT * FROM users WHERE status = :status AND role = :role",
    ['status' => 'active', 'role' => 'admin']
)->get();

foreach ($activeAdmins as$admin) {
    echo $admin['name'] . "\n";
}

// Fetch a single row
$singleUser =$query->raw(
    "SELECT * FROM users WHERE email = :email",
    ['email' => 'john@example.com']
)->first();

```

## 📄 License

Open-source software licensed under the [MIT license](https://www.google.com/search?q=LICENSE).
