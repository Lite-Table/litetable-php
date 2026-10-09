#!/usr/bin/env bash

# LiteTable Linter Script
# Runs PHP syntax checks and static analysis tools if available.

set -e

echo "----------------------------------------"
echo " Running LiteTable Linter & Syntax Check"
echo "----------------------------------------"

# Basic PHP Syntax Linting across src/, tests/, benchmarks/, and examples/
echo "=> Checking PHP syntax compliance..."
find src/ tests/ benchmarks/ examples/ -name "*.php" -print0 | xargs -0 -n 1 php -l

echo "✔ PHP syntax check passed successfully!"

# Optional: Run PHPStan if installed in development mode
if [ -f "vendor/bin/phpstan" ]; then
    echo "=> Running PHPStan static analysis..."
    vendor/bin/phpstan analyse src --level=max
    echo "✔ PHPStan static analysis passed!"
else
    echo "ℹ PHPStan not found in vendor/. Skipping advanced static analysis."
fi

echo "----------------------------------------"
echo " All lint checks completed successfully!"
echo "----------------------------------------"