# Cruder - Comprehensive CRUD Service Package

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/laravel-%5E10.0%7C%5E11.0%7C%5E12.0-red.svg)](https://laravel.com/)

A powerful, feature-rich CRUD service package for Laravel applications that provides a comprehensive solution for database operations with advanced features like caching, query logging, column validation, export capabilities, and more.

## 🚀 Features

### 🔧 Core CRUD Operations
- **Complete CRUD Operations** - Create, Read, Update, Delete with full validation
- **Bulk Operations** - Efficient bulk create, update, and delete operations
- **Soft Delete Support** - Full soft delete functionality with configurable behavior
- **Column Validation** - Secure column validation for all operations

### 🔍 Advanced Query Features
- **Advanced Filtering & Sorting** - Column-based filtering, sorting, and text search
- **Smart Caching** - Built-in query caching with configurable lifetime and tags
- **Pagination Support** - Built-in pagination with customizable parameters
- **Search Suggestions** - Intelligent search suggestions with configurable strategies
- **Relationship Loading** - Eager loading for related models

### 📊 Monitoring & Performance
- **Query Logging** - Comprehensive query logging with performance monitoring
- **Performance Monitoring** - Slow query detection and logging
- **Execution Time Tracking** - Detailed execution time measurement
- **Memory Optimization** - Chunked processing for large datasets

### 📤 Export & Integration
- **Export Functionality** - CSV and JSON export with strategy pattern
- **API Resource Support** - Built-in API resource transformation
- **Hook System** - Extensible hook system for custom business logic
- **Custom Strategies** - Pluggable strategies for search, export, and hooks

### 🔒 Security & Validation
- **Input Validation** - Comprehensive validation rules for create/update operations
- **Column Security** - Prevents SQL injection through column name validation
- **Access Control** - User context in cache keys and operations
- **Audit Logging** - Built-in audit logging capabilities

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

This will publish the `config/cruder.php` file where you can configure query logging and other options.

### 3. Service Provider (Auto-discovered)

The package will be automatically discovered by Laravel. If you need to manually register it, add to `config/app.php`:

```php
'providers' => [
    // ...
    SgFlores\Cruder\CruderServiceProvider::class,
],
```

## 🎯 Quick Start

### 1. Create Your Service

Create a service that extends `BaseCrudService`:

```php
<?php

namespace App\Services;

use SgFlores\Cruder\BaseCrudService;
use App\Models\User;

class UserService extends BaseCrudService
{
    protected $model = User::class;
    
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

### 2. Use in Your Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;
    
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    
    public function index(Request $request)
    {
        // Get users with filtering, sorting, and pagination
        $users = $this->userService->findAll($request->all());
        
        return response()->json($users);
    }
    
    public function store(Request $request)
    {
        $user = $this->userService->create($request->validated());
        
        return response()->json($user, 201);
    }
    
    public function show($id)
    {
        $user = $this->userService->find($id);
        
        return response()->json($user);
    }
    
    public function update(Request $request, $id)
    {
        $user = $this->userService->update($id, $request->validated());
        
        return response()->json($user);
    }
    
    public function destroy($id)
    {
        $this->userService->delete($id);
        
        return response()->json(null, 204);
    }
}
```

## 🔧 URL Parameters & Examples

### Basic CRUD Operations

```php
// Get all users
GET /users

// Get user by ID
GET /users/1

// Create user
POST /users
{
    "name": "John Doe",
    "email": "john@example.com",
    "department_id": 1
}

// Update user
PUT /users/1
{
    "name": "John Smith"
}

// Delete user
DELETE /users/1
```

### Advanced Query Parameters

```php
// Filtering
GET /users?department_id=1&status=active

// Sorting
GET /users?sort=name&order=asc
GET /users?sort=created_at&order=desc

// Text Search
GET /users?search=john

// Pagination
GET /users?page=2&per_page=10

// Field Selection
GET /users?fields=id,name,email

// Include Relations
GET /users?with=department,posts

// Advanced Filtering
GET /users?filters[department_id]=1&filters[status]=active&filters[created_at][from]=2024-01-01

// Export
GET /users?export=csv
GET /users?export=json

// Search Suggestions
GET /users?search_suggestions=john&limit=5
```

### Real-World Examples

#### 🛒 E-commerce Product Management

```php
// Get products with advanced filters
GET /products?category_id=1&price_min=100&price_max=500&in_stock=true&sort=price&order=asc

// Search products with multiple criteria
GET /products?search=laptop&category_id=electronics&brand_id=2&status=active

// Export product catalog with specific columns
GET /products?export=csv&filters[category_id]=1&export_columns=name,price,stock

// Get product suggestions
GET /products?search_suggestions=laptop&limit=10
```

#### 👥 User Management Dashboard

```php
// Get users with pagination and sorting
GET /users?page=1&per_page=20&sort=created_at&order=desc

// Filter active users by department
GET /users?filters[status]=active&filters[department_id]=2

// Search users by name or email
GET /users?search=john&fields=id,name,email,department

// Get users with relationships
GET /users?with=department,profile&filters[role]=admin
```

#### 📰 Content Management System

```php
// Get published articles with pagination
GET /articles?status=published&page=1&per_page=15&sort=published_at&order=desc

// Search articles by title or content
GET /articles?search=laravel tutorial&search_columns=title,content

// Filter articles by date range
GET /articles?filters[published_at][from]=2024-01-01&filters[published_at][to]=2024-12-31

// Export article data
GET /articles?export=json&filters[category_id]=1&export_columns=title,author,published_at
```

#### 📊 Analytics Dashboard

```php
// Get sales data with complex filters
GET /sales?filters[date][from]=2024-01-01&filters[date][to]=2024-12-31&filters[status]=completed

// Get top performing products
GET /products?sort=sales_count&order=desc&per_page=10&filters[status]=active

// Export analytics data
GET /analytics?export=csv&filters[period]=monthly&export_columns=date,revenue,orders
```

## 🔍 Feature Deep Dive

### 🚀 Query Logging

Cruder includes comprehensive query logging with performance monitoring:

```php
// Enable in config/cruder.php
'query_logging' => [
    'enabled' => true,
    'log_all_operations' => true,
    'slow_query_threshold' => 1000, // 1 second
    'include_bindings' => true,
    'include_execution_time' => true,
],
```

**Benefits:**
- Monitor query performance
- Debug slow queries
- Track database usage
- Audit database operations

### 🪝 Hook System

Extensible hook system for custom business logic:

```php
// Register hooks
$hookService->registerHook('before_create', new CallableHook(function($data) {
    $data['created_by'] = Auth::id();
    return $data;
}));

$hookService->registerHook('after_update', new CallableHook(function($data) {
    // Send notification
    Mail::to($data['email'])->send(new UserUpdatedNotification($data));
    return $data;
}));
```

**Available Hooks:**
- `before_create` / `after_create`
- `before_update` / `after_update`
- `before_delete` / `after_delete`

### 📤 Export Service

Flexible export functionality with strategy pattern:

```php
// Export to CSV
$csv = $userService->export('csv', $users, [
    'columns' => ['name', 'email', 'created_at'],
    'headers' => ['name' => 'Full Name', 'email' => 'Email']
]);

// Export to JSON
$json = $userService->export('json', $users, ['pretty' => true]);
```

**Supported Formats:**
- CSV with customizable delimiters
- JSON with pretty printing
- Extensible for custom formats

### 🔍 Advanced Search

Intelligent search with multiple strategies:

```php
// Like search (default)
$users = $userService->findAll(['search' => 'john']);

// Full-text search
$users = $userService->findAll([
    'search' => 'john doe',
    'search_strategy' => 'fulltext'
]);

// Search suggestions
$suggestions = $userService->getSearchSuggestions('john', 5);
```

**Search Features:**
- Multiple search strategies
- Column-specific search
- Search suggestions
- Case-insensitive search

### ⚡ Performance Features

Built-in performance optimizations:

```php
// Smart caching
protected const QUERY_CACHE_ENABLED = true;
protected const CACHE_LIFETIME_SECONDS = 3600;

// Chunked processing for large datasets
protected const ENABLE_CHUNKED_PROCESSING = true;
protected const CHUNK_SIZE = 1000;

// Pagination
GET /users?page=1&per_page=20
```

**Performance Benefits:**
- Query result caching
- Chunked processing
- Efficient pagination
- Memory optimization

## 📚 Documentation

### 📖 Main Documentation
- **[README.md](README.md)** - This file - Package overview, installation, and quick start guide
- **[TECHNICAL_README.md](TECHNICAL_README.md)** - Architecture, patterns, and implementation details
- **[OPTIONS_README.md](OPTIONS_README.md)** - Complete configuration options reference
- **[QUERY_LOGGING.md](QUERY_LOGGING.md)** - Comprehensive query logging documentation

### 🎯 Quick Links
- [Installation Guide](#-installation)
- [Quick Start](#-quick-start)
- [URL Parameters & Examples](#-url-parameters--examples)
- [Real-World Examples](#-real-world-examples)
- [Configuration Reference](OPTIONS_README.md)
- [Architecture Overview](TECHNICAL_README.md)
- [Query Logging Setup](QUERY_LOGGING.md)

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

- Laravel Framework
- PHP Community
- All contributors and testers

---

**Made with ❤️ for the Laravel community**