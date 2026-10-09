# Contributing to LiteTable

First off, thank you for taking the time to contribute! LiteTable is built on the philosophy of zero external dependencies, high performance, and absolute architectural clarity. We appreciate your help in keeping it lightweight, robust, and developer-friendly.

Please read through these guidelines to ensure a smooth contribution process.

---

## Code of Conduct

By participating in this project, you agree to abide by its terms:
- Be respectful, welcoming, and inclusive in all interactions.
- Constructive criticism is welcome, but personal attacks will not be tolerated.
- Focus on what is best for the community and the long-term maintainability of the project.

---

## Directory Structure

LiteTable maintains a strictly modular architecture to keep production code completely isolated from development, testing, and benchmarking utilities:

- **`src/`** — Production-ready code only (Database driver, Query builder, Table abstraction, and Traits). **No test assets or external dependencies are permitted here.**
- **`tests/`** — Unit, integration, and feature tests, including the custom testing framework (`tests/Testing/Test.php`).
- **`benchmarks/`** — Performance measurement scripts to track speed, memory allocation, and operations per second.
- **`examples/`** — Practical implementation examples showing CRUD and query operations in action.
- **`scripts/`** — Development automation helpers, static analysis, and security checks.

---

## Development Setup

To get your local development environment up and running:

1. **Fork and Clone the Repository:**
   ```bash
   git clone https://github.com/ortizaad1994/litetable-php.git
   cd litetable-php
   ```

2. **Install Dependencies:**
   Make sure you have PHP 8.1+ and Composer installed, then run:
   ```bash
   make install
   ```

---

## Development Workflow & Makefile

We standardize our local development workflow using a `Makefile`. Whenever possible, execute tasks through these automated commands rather than manual scripts:

- **Install Dependencies:**
  ```bash
  make install
  ```
- **Run the Test Suite:**
  Executes all functional and architecture tests against an in-memory SQLite database.
  ```bash
  make test
  ```
- **Run Performance Benchmarks:**
  Measures throughput and memory peaks across CRUD and Query scenarios.
  ```bash
  make benchmark
  ```
- **Run Linter / Static Analysis:**
  Validates code style and syntax rules.
  ```bash
  make lint
  ```
- **Check Security Vulnerabilities:**
  Scans project composition for known security advisories.
  ```bash
  make vuln-check
  ```
- **Clean Temporary Files:**
  Removes caches and build artifacts.
  ```bash
  make clean
  ```

---

## Coding Standards

- **Strict Types:** All PHP source files must declare `declare(strict_types=1);`.
- **PSR-12:** Follow modern PSR-12 coding style guidelines (indentation, brace placement, naming conventions).
- **Zero Dependencies:** Do not introduce third-party Composer packages into `require`. LiteTable must remain standalone.
- **Documentation:** Document all public classes, methods, and traits with clear, concise English docblocks.

---

## Submitting a Pull Request

1. **Create a Branch:** Name your branch descriptively (e.g., `fix/query-join-bug` or `feature/json-support`).
2. **Write Tests:** If you are fixing a bug or adding a new capability, include comprehensive tests in the `tests/` directory.
3. **Verify Locally:** Run `make test`, `make benchmark`, and `make lint` to ensure everything passes cleanly.
4. **Open a Pull Request:** Push your branch to GitHub and open a pull request against the `main` branch. Provide a clear summary of changes and the problem they solve.

Thank you for helping keep LiteTable fast, clean, and elegant!