# Testing Guide

This package uses PHPUnit with Orchestra Testbench for testing Laravel packages.

## Prerequisites

- PHP 8.2+
- Composer
- Orchestra Testbench 10.6.0+
- PHPUnit 11.5.42+

## Running Tests

### Basic Test Commands

```bash
# Run all tests without coverage (fastest)
composer test

# Run specific test suites
composer test-unit
composer test-integration
composer test-feature

# Run all test suites explicitly
composer test-all

# Run tests with coverage (slower, for local development)
composer test-coverage
```

### Manual Test Commands

```bash
# Run all tests without coverage
vendor/bin/phpunit --no-coverage

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit --no-coverage
vendor/bin/phpunit --testsuite=Integration --no-coverage
vendor/bin/phpunit --testsuite=Feature --no-coverage

# Run all test suites explicitly
vendor/bin/phpunit --testsuite=Unit --testsuite=Integration --testsuite=Feature --no-coverage

# Run specific test file
vendor/bin/phpunit tests/Unit/Services/EventServiceTest.php --no-coverage

# Run tests with coverage
vendor/bin/phpunit --configuration=phpunit-with-coverage.xml
```

## Test Configuration

### Main Configuration (`phpunit.xml`)
- **Purpose**: Fast test execution without coverage
- **Use case**: CI/CD, quick development testing
- **Coverage**: Disabled for speed

### Coverage Configuration (`phpunit-with-coverage.xml`)
- **Purpose**: Detailed test coverage analysis
- **Use case**: Local development, code quality analysis
- **Coverage**: HTML, text, and XML reports generated

## Test Structure

```
tests/
├── Unit/                    # Unit tests (fast, isolated)
│   ├── Services/           # Service layer tests
│   ├── Strategies/         # Strategy pattern tests
│   └── ...
├── Integration/            # Integration tests (with database)
├── Feature/               # Feature tests (end-to-end)
└── Models/                # Test models
```

## Test Environment

The test environment is configured with:
- **Database**: SQLite in-memory
- **Cache**: Array driver
- **Session**: Array driver
- **Queue**: Sync driver
- **Environment**: Testing

## Coverage Reports

When running with coverage, reports are generated in:
- `coverage/html/` - HTML coverage report
- `coverage/coverage.txt` - Text coverage report
- `coverage/coverage.xml` - XML coverage report (for CI)

## Troubleshooting

### Common Issues

1. **"This test does not define a code coverage target"**
   - Use `--no-coverage` flag or the main `phpunit.xml` configuration
   - This is normal for fast test execution

2. **Database connection issues**
   - Ensure SQLite extension is enabled
   - Check that the test environment is properly configured

3. **Memory issues**
   - Increase PHP memory limit: `php -d memory_limit=512M vendor/bin/phpunit`

### Performance Tips

- Use `--no-coverage` for faster test execution
- Run specific test suites instead of all tests
- Use `--stop-on-failure` to stop on first failure during development

## CI/CD Integration

For continuous integration, use:
```bash
composer test
```

This runs all tests without coverage for maximum speed and reliability.
