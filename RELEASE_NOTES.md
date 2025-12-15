# CRUDer - Release Notes

## 🎉 Version 1.0.0 - Initial Release

**Release Date:** December 15, 2025

### Overview

**CRUDer** is a powerful, feature-rich CRUD service package for Laravel applications built on **SOLID principles** and proven design patterns. It provides comprehensive database operations with advanced features like caching, query logging, column validation, export capabilities, and more.

### ✨ Key Features

- **📝 Complete CRUD Operations** - Create, Read, Update, Delete with full validation
- **📦 Bulk Operations** - Efficient bulk create, update, and delete operations
- **🔍 Advanced Filtering & Search** - Column-based filtering, sorting, and text search
- **🎯 Search Strategy System** - Pluggable search strategies for custom search implementations
- **📊 Export Functionality** - CSV, JSON, and custom export formats
- **🔧 Filter Helpers** - One-line helpers for common filter patterns (e.g., between ranges)
- **⚡ Query Caching** - Built-in query caching with configurable lifetime
- **📈 Performance Monitoring** - Query logging and slow query detection
- **🔒 Column Validation** - Secure column validation for all operations
- **🎨 Virtual Filters** - Safely expose computed filter columns via `getCustomFilterColumns()`
- **🎪 Event System** - Extensible event system for custom business logic
- **📋 Trait-Based Configuration** - Type-safe, IDE-friendly configuration system
- **📝 Audit Trail** - Automatic tracking of record changes (created_by, updated_by, deleted_by)
- **🔄 Resource Transformation** - Optional API resource transformation for consistent responses
- **🔍 Single Record Helpers** - `findByIdRaw()` and `findById()` for flexible record retrieval

### 📋 Requirements

- **PHP:** ^8.1
- **Laravel:** ^10.0|^11.0|^12.0
- **Dependencies:**
  - `illuminate/support` ^10.0|^11.0|^12.0
  - `illuminate/database` ^10.0|^11.0|^12.0
  - `illuminate/validation` ^10.0|^11.0|^12.0
  - `illuminate/pagination` ^10.0|^11.0|^12.0

### 🚀 Installation

```bash
composer require sgflores/cruder
```

### 📖 Quick Start

#### 1. Create Your Service

```php
<?php

namespace App\Services;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;
use App\Models\User;

class UserService extends BaseCrudService
{
    public function __construct(
        User $user,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($user, $eventService, $validationService);
    }
    
    public function getDirectTextSearchColumns(): array
    {
        return ['name', 'email'];
    }
    
    public function getDirectFilterableColumns(): array
    {
        return ['status', 'department_id'];
    }
    
    public function getDirectSortableColumns(): array
    {
        return ['name', 'email', 'created_at'];
    }
}
```

#### 2. Basic Usage

```php
// Find all with filtering and pagination
$users = $userService->findAll([
    'search' => 'john',
    'status' => 'active',
    'sort_by' => 'name',
    'sort_direction' => 'asc',
    'per_page' => 10,
    'page' => 1
]);

// Find by ID (resource-aware)
$user = $userService->findById(1, ['department']);

// Find raw model (no transformation) for policy checks
$rawUser = $userService->findByIdRaw(1, ['department']);

// Create
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update
$user = $userService->update(1, [
    'name' => 'John Smith'
]);

// Delete
$userService->delete(1);

// Bulk operations
$userService->bulkCreate([...]);
```

### 🎯 What's Included

#### Core Services

- **BaseReaderService** - Abstract base class for read-only operations
- **BaseCrudService** - Extends BaseReaderService with full CRUD operations
- **SearchService** - Manages search strategies and applies them to queries
- **ValidationService** - Manages validation strategies for data integrity
- **ExportService** - Handles data export in various formats
- **EventService** - Provides event-driven architecture for business logic
- **QueryLogger** - Performance monitoring and slow query detection

#### Design Patterns

- **Strategy Pattern** - Interchangeable algorithms for search, validation, and export
- **Template Method Pattern** - Defines operation skeletons with customizable steps
- **Observer Pattern** - Event-driven architecture for loose coupling
- **Factory Pattern** - Creates appropriate strategy instances

#### Configuration System

- **ReaderConfigurable Interface** - Contract for reader services
- **CrudConfigurable Interface** - Contract for CRUD-specific configuration
- **ReaderConfigurationTrait** - Default implementations for reader methods
- **CrudConfigurationTrait** - Default implementations for CRUD methods
- **PerformanceMonitoringTrait** - Performance monitoring and debugging

#### Built-in Strategies

- **Search Strategies:**
  - `LikeSearchStrategy` - SQL LIKE-based search
  - Custom strategies via `SearchStrategyInterface`

- **Export Strategies:**
  - `CsvExportStrategy` - CSV export
  - `JsonExportStrategy` - JSON export
  - Custom strategies via `ExportStrategyInterface`

- **Validation Strategies:**
  - Array-based validation rules
  - Custom strategies via `ValidationStrategyInterface`

### 📚 Documentation

- **[README.md](README.md)** - Quick start guide and basic usage
- **[CONFIGURATION.md](CONFIGURATION.md)** - Complete configuration methods reference
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Package architecture and design patterns
- **[Examples/README.md](Examples/README.md)** - Comprehensive examples and usage patterns

### 🔧 Key Features Explained

#### Between Filter Helper

Convenience helper for range filters:

```php
$filters = [
    'created_from' => '2025-01-01',
    'created_to'   => '2025-01-31',
];

$this->mergeBetweenFilter(
    $filters,
    'orders.created_at', // database column
    'created_from',      // input key for lower bound
    'created_to',        // input key for upper bound
    'Y-m-d'              // optional Carbon format
);
```

#### Search Strategies

Create custom search implementations:

```php
class TopOrdersStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'top_orders';
    }
    
    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        return $query->orderBy('total_amount', 'desc');
    }
}

// Usage
$results = $this->findAll(['strategies' => TopOrdersStrategy::key()]);
```

#### Event System

Hook into CRUD operations:

```php
$this->eventService->listen(EventService::BEFORE_CREATE, function($data) {
    // Custom logic before creation
    return $data;
});

$this->eventService->listen(EventService::AFTER_UPDATE, function($model) {
    // Custom logic after update
});
```

### 🧪 Testing

```bash
composer test              # Run test suite
composer test-coverage     # Run with coverage
composer test-unit         # Run unit tests only
composer test-integration  # Run integration tests only
composer test-feature      # Run feature tests only
composer test-all          # Run all test suites
```

### 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

### 👨‍💻 Author

**sgflores**
- Email: floresopic@gmail.com

### 🙏 Acknowledgments

Built with ❤️ for the Laravel community using:

- **[Laravel Framework](https://laravel.com/)** - Foundation and ORM
- **[Laravel Query Builder](https://laravel.com/docs/queries)** - Fluent query building
- **[Laravel Validation](https://laravel.com/docs/validation)** - Input validation
- **SOLID Principles** - Clean architecture foundation
- **Strategy Pattern** - Interchangeable algorithms
- **[PHPUnit](https://phpunit.de/)** - Unit testing framework
- **[Orchestra Testbench](https://github.com/orchestral/testbench)** - Laravel package testing

---

**Made with ❤️ for the Laravel community**

