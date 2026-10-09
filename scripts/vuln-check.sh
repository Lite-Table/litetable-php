#!/usr/bin/env bash

set -e

echo "----------------------------------------"
echo " Checking Composer Dependencies for Vulnerabilities"
echo "----------------------------------------"

# Check if composer.lock has actual dependency packages or if it doesn't exist
if grep -q '"packages": \[\s*\]' composer.lock 2>/dev/null || [ ! -f "composer.lock" ]; then
    echo "ℹ Zero-dependency library detected. No external packages found to audit."
else
    composer audit --locked
fi

echo "----------------------------------------"
echo " Vulnerability check completed successfully!"
echo "----------------------------------------"