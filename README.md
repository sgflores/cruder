# CRUDer - Laravel CRUD Service Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful, feature-rich CRUD service package for Laravel applications that provides comprehensive database operations with advanced features like caching, query logging, column validation, export capabilities, and more.

## 🚀 Features

- **Complete CRUD Operations** - Create, Read, Update, Delete with full validation
- **Bulk Operations** - Efficient bulk create, update, and delete operations
- **Advanced Filtering & Search** - Column-based filtering, sorting, and text search
- **Export Functionality** - CSV and JSON export with strategy pattern
- **Query Caching** - Built-in query caching with configurable lifetime
- **Performance Monitoring** - Query logging and slow query detection
- **Soft Delete Support** - Full soft delete functionality
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

### 1. Create Your CRUD Service

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
    
    // Define your configuration
    protected const QUERY_CACHE_ENABLED = true;
    protected const CACHE_LIFETIME_SECONDS = 3600;
    
    // Define validation rules
    protected const CREATE_VALIDATION_RULES = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'department_id' => 'required|exists:departments,id'
    ];
    
    protected const UPDATE_VALIDATION_RULES = [
        'name' => 'sometimes|string|max:255',
        'email' => 'sometimes|email|unique:users,email,{id}',
        'department_id' => 'sometimes|exists:departments,id'
    ];
    
    // Define searchable columns
    protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'email'];
    
    // Define filterable columns
    protected const DIRECT_FILTERABLE_COLUMNS = ['department_id', 'status'];
    
    // Define sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'email', 'created_at'];
}
```

### 2. Create Your Reader Service (Read-only)

```php
<?php

namespace App\Services;

use SgFlores\Cruder\BaseReaderService;
use App\Models\Product;

class ProductService extends BaseReaderService
{
    public function __construct(Product $product)
    {
        parent::__construct($product);
    }
    
    // Define searchable columns
    protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'description'];
    protected const RELATED_TEXT_SEARCH_COLUMNS = ['category_name'];
    
    // Define filterable columns
    protected const DIRECT_FILTERABLE_COLUMNS = ['status', 'category_id', 'price'];
    protected const RELATED_FILTERABLE_COLUMNS = ['category_name', 'brand_name'];
    
    // Define sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'price', 'created_at'];
    protected const RELATED_SORTABLE_COLUMNS = ['category_name', 'brand_name'];
}
```

### 3. Basic Usage Examples

```php
// Create a new user
$userService = new UserService();
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'department_id' => 1
]);

// Find all users with filtering
$users = $userService->findAll([
    'search' => 'john',
    'status' => 'active',
    'sort_by' => 'name',
    'sort_direction' => 'asc',
    'limit' => 10
]);

// Find user by ID
$user = $userService->findById(1, ['department']);

// Update user
$user = $userService->update(1, [
    'name' => 'John Smith',
    'email' => 'johnsmith@example.com'
]);

// Delete user
$userService->delete(1);

// Bulk operations
$userService->bulkCreate([
    ['name' => 'User 1', 'email' => 'user1@example.com'],
    ['name' => 'User 2', 'email' => 'user2@example.com']
]);

$userService->bulkUpdate(['status' => 'active'], ['department_id' => 1]);
$userService->bulkDelete(['status' => 'inactive']);

// Export functionality
$csvData = $userService->export('csv', [], ['name', 'email']);
$jsonData = $userService->export('json', [], ['name', 'email']);
```

### 4. Advanced Filtering

```php
// Basic filtering
$users = $userService->findAll([
    'status' => 'active',
    'department_id' => 1
]);

// Advanced filtering with operators
$users = $userService->findAll([
    'age' => ['operator' => 'gte', 'value' => 25],
    'salary' => ['operator' => 'between', 'value' => [50000, 100000]],
    'name' => ['operator' => 'like', 'value' => '%john%']
]);

// Related model filtering
$users = $userService->findAll([
    'department_name' => 'Engineering',
    'profile_age' => 25
]);
```

## 📚 Documentation

### 📖 Main Documentation
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Overall application architecture and lifecycle
- **[SERVICES.md](SERVICES.md)** - Detailed service documentation with examples
- **[STRATEGIES.md](STRATEGIES.md)** - Strategy pattern implementations
- **[EXAMPLES.md](EXAMPLES.md)** - Complete example classes and usage

### 🎯 Quick Links
- [Installation Guide](#-installation)
- [Quick Start](#-quick-start)
- [Architecture Overview](ARCHITECTURE.md)
- [Service Documentation](SERVICES.md)
- [Strategy Patterns](STRATEGIES.md)
- [Example Classes](EXAMPLES.md)

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

## 🙏 Acknowledgments & References

### **Core Technologies**
- **[Laravel Framework](https://laravel.com/)** - The foundation that makes this package possible
- **[Laravel Eloquent ORM](https://laravel.com/docs/eloquent)** - Database abstraction and model relationships
- **[Laravel Query Builder](https://laravel.com/docs/queries)** - Fluent query building interface
- **[Laravel Validation](https://laravel.com/docs/validation)** - Input validation system
- **[Laravel Collections](https://laravel.com/docs/collections)** - Powerful data manipulation

### **Testing Framework**
- **[PHPUnit](https://phpunit.de/)** - Unit testing framework
- **[Orchestra Testbench](https://github.com/orchestral/testbench)** - Laravel package testing
- **[Mockery](https://github.com/mockery/mockery)** - Mock object framework

### **Design Patterns & Principles**
- **SOLID Principles** - Clean code architecture foundation
- **Strategy Pattern** - Interchangeable algorithms implementation
- **Observer Pattern** - Event-driven architecture
- **Factory Pattern** - Object creation abstraction
- **Service Layer Pattern** - Business logic encapsulation

### **Inspiration**
- **Laravel Ecosystem** - Following Laravel's conventions and best practices
- **Enterprise Patterns** - Implementing common enterprise requirements
- **Laravel Community** - Learning from the amazing Laravel community
- **Open Source Community** - Contributing back to the open source ecosystem

### **Special Thanks**
- **Laravel Team** - For creating an amazing framework
- **PHP Community** - For continuous innovation and improvement
- **Open Source Contributors** - For inspiring better software development
- **Laravel Package Developers** - For setting high standards in package development

---

**Made with ❤️ for the Laravel community**