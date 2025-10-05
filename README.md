# CRUDer - Laravel CRUD Service Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful, feature-rich CRUD service package for Laravel applications built on SOLID principles and design patterns. Provides comprehensive database operations with advanced features like caching, query logging, column validation, export capabilities, and more.

## 🚀 Key Features

- **Complete CRUD Operations** - Create, Read, Update, Delete with full validation
- **Bulk Operations** - Efficient bulk create, update, and delete operations
- **Advanced Filtering & Search** - Column-based filtering, sorting, and text search
- **Search Strategy System** - Pluggable search strategies for custom search implementations
- **Export Functionality** - CSV, JSON, and custom export formats
- **Query Caching** - Built-in query caching with configurable lifetime
- **Performance Monitoring** - Query logging and slow query detection
- **Column Validation** - Secure column validation for all operations
- **Event System** - Extensible event system for custom business logic
- **Trait-Based Configuration** - Type-safe, IDE-friendly configuration system
- **Audit Trail** - Automatic tracking of record changes (created_by, updated_by, deleted_by)

## 📋 Requirements

- PHP ^8.1
- Laravel ^10.0|^11.0|^12.0
- Illuminate Support ^10.0|^11.0|^12.0
- Illuminate Database ^10.0|^11.0|^12.0
- Illuminate Validation ^10.0|^11.0|^12.0
- Illuminate Pagination ^10.0|^11.0|^12.0

## 📦 Installation

### 1. Install via Composer

```bash
composer require sgflores/cruder
```

### 2. Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="SgFlores\Cruder\CruderServiceProvider" --tag="cruder-config"
```

### 3. Service Provider (Auto-discovered)

The package will be automatically discovered by Laravel.

## 🎯 Quick Start

### 1. Create Your Service

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
    
    // Override configuration methods as needed
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
    
    public function isAuditTrailEnabled(): bool
    {
        return true;
    }
}
```

> **💡 Configuration**: The service uses a trait-based configuration system. See [CONFIGURATION.md](CONFIGURATION.md) for all available configuration methods and examples.

### 2. Basic Usage

```php
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;

// Create service with proper dependency injection
$eventService = new EventService();
$validationService = new ValidationService();
$userService = new UserService(new User(), $eventService, $validationService);

// Find all users with filtering and pagination
$users = $userService->findAll([
    'search' => 'john',
    'status' => 'active',
    'sort_by' => 'name',
    'sort_direction' => 'asc',
    'limit' => 10
]);

// Find user by ID
$user = $userService->findById(1, ['department']);

// Create user
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Update user
$user = $userService->update(1, [
    'name' => 'John Smith'
]);

// Delete user
$userService->delete(1);

// Bulk operations
$userService->bulkCreate([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com']
]);
```

> **📝 Note**: For export functionality, you'll need to extend `BaseReaderService` with `SearchService` and `ExportService` injected. See [Examples/README.md](Examples/README.md) for complete examples.

### 3. Search Strategies

Create custom search implementations for complex reporting and analytics using the new strategy pattern.

**Key Features:**
- **Custom Search Strategies** - Pluggable strategies using the `key()` method pattern
- **Strategy Registration** - Use `Strategy::key()` for consistent strategy registration
- **Query Type Flexibility** - Support both Eloquent Builder and QueryBuilder
- **Proper Service Injection** - Extend `BaseReaderService` with `SearchService` injected

**Example Strategy Implementation:**
```php
class TopOrdersStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'top_orders';
    }
    
    public function search(Builder $query, array $filters, array $config): Builder
    {
        return $query->orderBy('total_amount', 'desc');
    }
}
```

**Usage:**
```php
// Register strategy
$this->searchService->addStrategy(TopOrdersStrategy::key(), new TopOrdersStrategy());

// Use in queries
$results = $this->findAll(['strategies' => TopOrdersStrategy::key()]);
```

**Complete Example:** See [Examples/SalesReportService.php](Examples/SalesReportService.php) for full implementation.

## 📚 Documentation

- **[CONFIGURATION.md](CONFIGURATION.md)** - Complete configuration methods reference and examples
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Package architecture, design patterns, and request lifecycle
- **[Examples/README.md](Examples/README.md)** - Comprehensive examples documentation and usage patterns
- **[Tests/README.md](tests/README.md)** - Testing documentation and examples

### 📖 Available Examples

**Quick Reference:**
- **[SimpleSalesExample.php](Examples/SimpleSalesExample.php)** - Basic CRUD operations
- **[SalesInventoryExample.php](Examples/SalesInventoryExample.php)** - Advanced inventory management
- **[SalesReportService.php](Examples/SalesReportService.php)** - Custom search strategies for reporting
- **[ProductExample.php](Examples/ProductExample.php)** - Custom validation strategies
- **[ExportExample.php](Examples/ExportExample.php)** - Data export functionality

See [Examples/README.md](Examples/README.md) for comprehensive documentation.

## 🧪 Testing

```bash
composer test              # Run test suite
composer test-coverage     # Run with coverage
```

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**sgflores**
- Email: floresopic@gmail.com

## 🙏 Acknowledgments

Built with ❤️ for the Laravel community using:

- **[Laravel Framework](https://laravel.com/)** - Foundation and ORM
- **[Laravel Query Builder](https://laravel.com/docs/queries)** - Fluent query building
- **[Laravel Validation](https://laravel.com/docs/validation)** - Input validation
- **SOLID Principles** - Clean architecture foundation
- **Strategy Pattern** - Interchangeable algorithms
- **[PHPUnit](https://phpunit.de/)** - Unit testing framework
- **[Orchestra Testbench](https://github.com/orchestral/testbench)** - Laravel package testing