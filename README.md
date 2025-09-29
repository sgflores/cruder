# CRUDer - Laravel CRUD Service Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful, feature-rich CRUD service package for Laravel applications built on SOLID principles and design patterns. Provides comprehensive database operations with advanced features like caching, query logging, column validation, export capabilities, and more.

## 🚀 Key Features

- **Complete CRUD Operations** - Create, Read, Update, Delete with full validation
- **Bulk Operations** - Efficient bulk create, update, and delete operations
- **Advanced Filtering & Search** - Column-based filtering, sorting, and text search
- **Export Functionality** - CSV, JSON, and custom export formats
- **Query Caching** - Built-in query caching with configurable lifetime
- **Performance Monitoring** - Query logging and slow query detection
- **Column Validation** - Secure column validation for all operations
- **Event System** - Extensible event system for custom business logic
- **Strategy Pattern** - Pluggable strategies for search, export, and validation

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
use App\Models\User;

class UserService extends BaseCrudService
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }
    
    // Define searchable columns
    protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'email'];
    
    // Define filterable columns
    protected const DIRECT_FILTERABLE_COLUMNS = ['status', 'department_id'];
    
    // Define sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'email', 'created_at'];
}
```

### 2. Basic Usage

```php
$userService = new UserService();

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

// Export data
$csvData = $userService->export('csv', [], ['name', 'email']);
$jsonData = $userService->export('json', [], ['name', 'email']);
```

## 📚 Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Package architecture, design patterns, and request lifecycle
- **[CONFIGURATION.md](CONFIGURATION.md)** - Complete configuration options and constants reference
- **[Examples/](Examples/)** - Real-world examples and usage patterns

### 📖 Available Examples
- **[SimpleSalesExample.php](Examples/SimpleSalesExample.php)** - Basic CRUD operations with sales system
- **[SalesInventoryExample.php](Examples/SalesInventoryExample.php)** - Advanced inventory management
- **[ReportExample.php](Examples/ReportExample.php)** - Custom search strategies for reporting
- **[ProductExample.php](Examples/ProductExample.php)** - Custom validation strategies
- **[ExportExample.php](Examples/ExportExample.php)** - Data export functionality

## 🧪 Testing

Run the test suite:

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
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

### Core Technologies
- **[Laravel Framework](https://laravel.com/)** - The foundation that makes this package possible
- **[Laravel Eloquent ORM](https://laravel.com/docs/eloquent)** - Database abstraction and model relationships
- **[Laravel Query Builder](https://laravel.com/docs/queries)** - Fluent query building interface
- **[Laravel Validation](https://laravel.com/docs/validation)** - Input validation system

### Design Patterns & Principles
- **SOLID Principles** - Clean architecture foundation
- **Strategy Pattern** - Interchangeable algorithms implementation
- **Observer Pattern** - Event-driven architecture
- **Factory Pattern** - Object creation abstraction
- **Template Method Pattern** - Operation skeleton definition

### Inspiration & References

- **[Laravel Generator](https://github.com/InfyOmLabs/laravel-generator)** - CRUD generator for Laravel
- **[Grocery CRUD](https://www.grocerycrud.com/)** - PHP CRUD library for CodeIgniter
- **[Craftable](https://github.com/BRACKETS-by-TRIAD/craftable)** - Laravel admin panel toolkit
- **[Scaffold Interface](https://github.com/amranidev/scaffold-interface)** - Laravel CRUD generator
- **[Spatie Laravel-Query-Builder](https://github.com/spatie/laravel-query-builder)** - Query building package

### Open Source Community
- **Laravel Community** - For continuous innovation and best practices
- **PHP Community** - For language improvements and ecosystem growth
- **Open Source Contributors** - For inspiring better software development
- **Laravel Package Developers** - For setting high standards in package development

### Testing & Quality
- **[PHPUnit](https://phpunit.de/)** - Unit testing framework
- **[Orchestra Testbench](https://github.com/orchestral/testbench)** - Laravel package testing
- **[Mockery](https://github.com/mockery/mockery)** - Mock object framework