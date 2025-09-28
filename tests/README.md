# CRUDer Package Tests

This directory contains comprehensive PHPUnit tests for the CRUDer package, covering all functionality and scenarios.

## Test Structure

```
tests/
├── Feature/                    # Feature tests (integration-like)
│   └── BaseCrudServiceTest.php
├── Integration/                # Full integration tests
│   └── BaseCrudServiceIntegrationTest.php
├── Unit/                       # Unit tests
│   ├── Services/              # Service layer tests
│   │   ├── SearchServiceTest.php
│   │   ├── ExportServiceTest.php
│   │   └── HookServiceTest.php
│   └── Strategies/            # Strategy pattern tests
│       ├── Search/
│       │   ├── FullTextSearchStrategyTest.php
│       │   └── LikeSearchStrategyTest.php
│       ├── Export/
│       │   ├── CsvExportStrategyTest.php
│       │   └── JsonExportStrategyTest.php
│       └── Hooks/
│           └── CallableHookTest.php
├── Models/                     # Test models
│   ├── User.php
│   └── Department.php
├── Factories/                  # Model factories
│   ├── UserFactory.php
│   └── DepartmentFactory.php
├── Services/                   # Test service implementations
│   └── TestUserService.php
├── database/                   # Test database migrations
│   └── migrations/
├── TestCase.php               # Base test class
└── bootstrap.php              # Test bootstrap
```

## Test Categories

### 1. Unit Tests
- **Strategy Tests**: Test individual search, export, and hook strategies
- **Service Tests**: Test service layer functionality
- **Hook Tests**: Test hook execution and chaining

### 2. Feature Tests
- **CRUD Operations**: Create, Read, Update, Delete operations
- **Search & Filtering**: Text search, column filtering, sorting
- **Export Functionality**: CSV and JSON export
- **Mass Operations**: Bulk create, update, delete
- **Validation**: Input validation and error handling
- **Caching**: Query result caching
- **Performance**: Slow query logging and performance monitoring

### 3. Integration Tests
- **Full Workflow**: Complete CRUD workflow testing
- **Search Integration**: End-to-end search functionality
- **Filtering Integration**: Complex filtering scenarios
- **Sorting Integration**: Various sorting combinations
- **Export Integration**: Export with different formats and options
- **Hooks Integration**: Hook execution in real scenarios
- **Cache Integration**: Cache behavior and invalidation
- **Performance Integration**: Large dataset performance testing
- **Error Handling**: Graceful error handling

## Test Coverage

The tests cover:

### ✅ Core CRUD Operations
- `findAll()` with various parameters
- `findById()` with and without relations
- `create()` with validation and audit trail
- `update()` with validation and audit trail
- `delete()` with soft delete support
- `count()` with filtering

### ✅ Search & Filtering
- Text search (LIKE and full-text)
- Column filtering (direct and related)
- Sorting (direct and related columns)
- Pagination and limiting
- Search suggestions

### ✅ Export Functionality
- CSV export with proper escaping
- JSON export with formatting options
- Export with filtering and column selection
- Custom export options

### ✅ Mass Operations
- Bulk create with audit trail
- Bulk update with filtering
- Bulk delete with soft delete support

### ✅ Advanced Features
- Query hooks (before/after operations)
- Caching with invalidation
- Performance monitoring
- Validation rules
- Audit trail
- Soft delete handling

### ✅ Error Handling
- Validation errors
- Database errors
- Missing records
- Invalid parameters

### ✅ SOLID Principles
- Strategy pattern implementation
- Service layer separation
- Interface segregation
- Dependency inversion

## Running Tests

### Run All Tests
```bash
composer test
```

### Run Specific Test Suites
```bash
# Unit tests only
vendor/bin/phpunit tests/Unit

# Feature tests only
vendor/bin/phpunit tests/Feature

# Integration tests only
vendor/bin/phpunit tests/Integration
```

### Run Specific Test Classes
```bash
# BaseCrudService tests
vendor/bin/phpunit tests/Feature/BaseCrudServiceTest.php

# Search strategy tests
vendor/bin/phpunit tests/Unit/Strategies/Search/FullTextSearchStrategyTest.php
```

### Run with Coverage
```bash
composer test-coverage
```

## Test Data

The tests use:
- **Test Models**: `User` and `Department` with relationships
- **Factories**: Laravel model factories for data generation
- **Migrations**: Database schema for testing
- **Mocking**: Mockery for external dependencies

## Test Scenarios

### Basic CRUD
- Create single record
- Create with validation
- Read single record
- Read collection
- Update single record
- Delete single record
- Soft delete

### Search & Filter
- Text search in direct columns
- Text search in related columns
- Filter by single column
- Filter by multiple columns
- Sort by direct columns
- Sort by related columns
- Pagination
- Limiting results

### Export
- CSV export with headers
- CSV export without headers
- JSON export with pretty print
- JSON export with custom flags
- Export with column selection
- Export with filtering

### Mass Operations
- Bulk create multiple records
- Bulk update with filters
- Bulk delete with filters
- Bulk force delete

### Advanced Features
- Query hooks execution
- Cache behavior
- Performance monitoring
- Audit trail
- Validation rules
- Error handling

## Test Assertions

The tests verify:
- **Return Types**: Correct return types for all methods
- **Data Integrity**: Data is correctly stored and retrieved
- **Relationships**: Eager loading works correctly
- **Validation**: Input validation works as expected
- **Error Handling**: Errors are handled gracefully
- **Performance**: Operations complete within reasonable time
- **Caching**: Cache is used and invalidated correctly
- **Hooks**: Hooks are executed at the right times
- **Audit Trail**: Audit fields are populated correctly

## Continuous Integration

The tests are designed to run in CI environments with:
- SQLite in-memory database
- No external dependencies
- Fast execution
- Comprehensive coverage
- Clear error reporting
