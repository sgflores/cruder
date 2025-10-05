# CRUDer Package Tests

This directory contains comprehensive PHPUnit tests for the CRUDer package, covering all functionality and scenarios.

## Test Structure

```
tests/
├── Feature/                    # Feature tests (package integration)
│   └── PackageIntegrationTest.php
├── Integration/                # Full integration tests
│   ├── BaseCrudServiceIntegrationTest.php
│   └── SearchStrategyIntegrationTest.php
├── Unit/                       # Unit tests
│   ├── Services/              # Service layer tests
│   │   ├── SearchServiceTest.php
│   │   ├── ExportServiceTest.php
│   │   └── EventServiceTest.php
│   ├── Strategies/            # Strategy pattern tests
│   │   ├── Search/
│   │   │   └── LikeSearchStrategyTest.php
│   │   └── Export/
│   │       ├── CsvExportStrategyTest.php
│   │       └── JsonExportStrategyTest.php
│   ├── BaseCrudServiceTest.php
│   ├── CacheTest.php
│   ├── ColumnValidationTest.php
│   ├── ConfigurationTraitTest.php
│   ├── EventServiceValidationTest.php
│   ├── QueryLoggingTest.php
│   ├── SearchStrategyFeaturesTest.php
│   └── ServiceInjectionValidationTest.php
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
└── UnitTestCase.php           # Unit test base class
```

## Test Categories

### 1. Unit Tests
- **Service Tests**: Test individual service functionality (SearchService, ExportService, EventService)
- **Strategy Tests**: Test individual search and export strategies with key() method pattern
- **Dependency Injection Tests**: Test proper service injection and validation
- **Configuration Tests**: Test trait-based configuration system
- **Column Validation Tests**: Test column security and validation
- **Event System Tests**: Test event handling with static constants
- **Caching Tests**: Test query result caching
- **Query Logging Tests**: Test performance monitoring

### 2. Feature Tests
- **Package Integration**: End-to-end package functionality testing
- **Service Integration**: Service interaction and dependency injection
- **Event Integration**: Event system integration with CRUD operations

### 3. Integration Tests
- **CRUD Integration**: Complete CRUD workflow testing with proper dependency injection
- **Search Strategy Integration**: End-to-end search functionality with custom strategies
- **Service Injection Integration**: Test service injection patterns and validation
- **Event Integration**: Event handling in real scenarios with static constants

## Test Coverage

The tests cover:

### ✅ Dependency Injection & Service Validation
- Service injection patterns for BaseCrudService and BaseReaderService
- Per-use service validation with clear error messages
- Optional service handling and graceful degradation
- Service dependency validation at runtime

### ✅ Strategy Pattern Implementation
- Strategy registration using `key()` method pattern
- Custom search strategies with proper interfaces
- Export strategies with `addStrategyByKey()` method
- Strategy interchangeability and testing

### ✅ Event System with Static Constants
- Event handling using EventService static constants
- Before/after operation events (BEFORE_CREATE, AFTER_CREATE, etc.)
- Event listener registration and execution
- Event-driven business logic integration

### ✅ Core CRUD Operations
- CRUD operations with proper service injection
- Validation with ValidationService
- Audit trail with EventService
- Bulk operations with event handling

### ✅ Search & Export Functionality
- Search functionality with SearchService injection
- Export functionality with ExportService injection
- Custom search strategies implementation
- Export strategies with proper formatting

### ✅ Configuration & Validation
- Trait-based configuration system
- Column validation and security
- Input validation with ValidationService
- Configuration method testing

### ✅ Performance & Monitoring
- Query caching with invalidation
- Query logging with QueryLogger
- Performance monitoring and metrics
- Memory management testing

### ✅ Error Handling & Edge Cases
- Service injection validation errors
- Strategy not found errors
- Missing service dependency errors
- Graceful degradation scenarios

## Running Tests

### Run All Tests
```bash
composer test
```

## Test Data

The tests use:
- **Test Models**: `User` and `Department` with relationships
- **Test Services**: `TestUserService` with proper dependency injection
- **Factories**: Laravel model factories for data generation
- **Migrations**: Database schema for testing with proper relationships
- **Service Injection**: Proper service instantiation for testing dependency injection patterns

## Test Scenarios

### Service Injection & Validation
- Service injection validation for BaseCrudService
- Service injection validation for BaseReaderService
- Per-use service validation with error messages
- Optional service handling and graceful degradation
- Missing service dependency error handling

### Event System Testing
- Event handling with static constants (EventService::BEFORE_CREATE, etc.)
- Event listener registration and execution
- Event-driven business logic integration
- Event validation and error handling

### Strategy Pattern Testing
- Strategy registration using key() method pattern
- Custom search strategy implementation
- Export strategy with addStrategyByKey() method
- Strategy interchangeability testing

### Dependency Injection Testing
- Constructor injection patterns
- Service dependency validation
- Optional service handling
- Concrete service injection patterns

### CRUD Operations with Services
- CRUD operations with proper service injection
- Validation with ValidationService
- Event handling with EventService
- Search functionality with SearchService
- Export functionality with ExportService

### Configuration & Security
- Trait-based configuration testing
- Column validation and security
- Input validation with ValidationService
- Configuration method testing

### Performance & Monitoring
- Query caching behavior
- Query logging with QueryLogger
- Performance monitoring
- Memory management testing

## Test Assertions

The tests verify:
- **Service Injection**: Proper dependency injection patterns
- **Service Validation**: Per-use service validation with clear error messages
- **Event Handling**: Events are fired using static constants at correct times
- **Strategy Pattern**: Strategies are registered and executed correctly using key() method
- **Error Handling**: Service injection errors are handled gracefully with clear messages
- **Return Types**: Correct return types for all methods
- **Data Integrity**: Data is correctly stored and retrieved with proper services
- **Validation**: Input validation works as expected with ValidationService
- **Performance**: Operations complete within reasonable time with proper monitoring
- **Caching**: Cache is used and invalidated correctly
- **Audit Trail**: Audit fields are populated correctly with EventService

## Continuous Integration

The tests are designed to run in CI environments with:
- SQLite in-memory database
- No external dependencies
- Fast execution with proper service injection testing
- Comprehensive coverage of dependency injection patterns
- Clear error reporting for service validation failures
- Proper service instantiation for testing dependency injection
