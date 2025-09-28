#!/bin/bash

# CRUDer Package Test Runner
echo "Running CRUDer Package Tests..."

# Check if vendor directory exists
if [ ! -d "vendor" ]; then
    echo "Installing dependencies..."
    composer install
fi

# Run PHPUnit tests
echo "Running PHPUnit tests..."
vendor/bin/phpunit

# Check exit code
if [ $? -eq 0 ]; then
    echo "✅ All tests passed!"
else
    echo "❌ Some tests failed!"
    exit 1
fi
