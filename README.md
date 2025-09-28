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
        $user = $this->userService->findById($id);
        
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

### Supported URL Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | int | Page number for pagination | `?page=2` |
| `limit` | int | Number of records per page | `?limit=20` |
| `search` | string | Text search term | `?search=john` |
| `sort_by` | string | Column to sort by | `?sort_by=name` |
| `sort_direction` | string | Sort direction (asc/desc) | `?sort_direction=desc` |
| `{column}` | mixed | Direct column filter | `?status=active` |
| `filters[{column}]` | mixed | Advanced column filter | `?filters[status]=active` |
| `filters[{column}][from]` | date | Date range from | `?filters[created_at][from]=2024-01-01` |
| `filters[{column}][to]` | date | Date range to | `?filters[created_at][to]=2024-12-31` |
| `export` | string | Export format (csv/json) | `?export=csv` |
| `search_suggestions` | string | Get search suggestions | `?search_suggestions=john&limit=5` |

**Note:** Relationships are loaded via the `$withRelations` parameter in code, not URL parameters:
```php
// Load relationships in code
$users = $userService->findAll($filters, ['department', 'profile']);
$user = $userService->findById($id, ['department', 'posts']);
```

## 🔍 Advanced Filtering System

Cruder provides a powerful and secure filtering system that supports both direct column filtering and related model filtering.

### Direct Column Filtering

Direct column filtering allows you to filter by columns directly on the main model:

```php
// In your service class
protected const DIRECT_FILTERABLE_COLUMNS = [
    'status', 'department_id', 'role', 'created_at'
];

// Usage via URL parameters
GET /users?status=active&department_id=1&role=admin
```

### Related Column Filtering

Related column filtering allows you to filter by columns on related models:

```php
// In your service class
protected const RELATED_FILTERABLE_COLUMNS = [
    'department_name', 'profile_age', 'company_location'
];

// Usage via URL parameters
GET /users?filters[department_name]=Engineering&filters[profile_age][min]=25
```

### Advanced Filtering Examples

#### 1. Basic Direct Filtering
```php
// Service configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'status', 'department_id', 'role', 'is_active'
];

// URL examples
GET /users?status=active
GET /users?department_id=1&role=admin
GET /users?is_active=true&status=active
```

#### 2. Related Model Filtering
```php
// Service configuration
protected const RELATED_FILTERABLE_COLUMNS = [
    'department_name', 'department_location', 'profile_age', 'company_size'
];

// URL examples
GET /users?filters[department_name]=Engineering
GET /users?filters[department_location]=New York
GET /users?filters[profile_age][min]=25&filters[profile_age][max]=65
GET /users?filters[company_size]=large
```

#### 3. Date Range Filtering
```php
// Service configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'created_at', 'updated_at', 'last_login_at'
];

// URL examples
GET /users?filters[created_at][from]=2024-01-01
GET /users?filters[created_at][to]=2024-12-31
GET /users?filters[last_login_at][from]=2024-01-01&filters[last_login_at][to]=2024-01-31
```

#### 4. Numeric Range Filtering
```php
// Service configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'age', 'salary', 'experience_years'
];

// URL examples
GET /users?filters[age][min]=25&filters[age][max]=65
GET /users?filters[salary][gte]=50000&filters[salary][lte]=100000
GET /users?filters[experience_years][gt]=5
```

### Complete Filtering Example

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
    
    // Direct column filtering (columns on users table)
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'status', 'role', 'is_active', 'created_at', 'age', 'salary'
    ];
    
    // Related column filtering (columns on related models)
    protected const RELATED_FILTERABLE_COLUMNS = [
        'department_name', 'department_location', 'profile_bio', 'company_size'
    ];
    
    // Searchable columns
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [
        'name', 'email', 'phone'
    ];
    
    protected const RELATED_TEXT_SEARCH_COLUMNS = [
        'department_name', 'profile_bio'
    ];
    
    // Sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = [
        'name', 'email', 'created_at', 'age', 'salary'
    ];
    
    protected const RELATED_SORTABLE_COLUMNS = [
        'department_name', 'company_size'
    ];
}
```

### Real-World Filtering Use Cases

#### E-commerce Product Filtering
```php
// ProductService configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'category_id', 'brand_id', 'price', 'in_stock', 'status', 'created_at'
];

protected const RELATED_FILTERABLE_COLUMNS = [
    'category_name', 'brand_name', 'reviews_rating'
];

// URL examples
GET /products?category_id=1&in_stock=true&status=active
GET /products?filters[price][min]=100&filters[price][max]=500
GET /products?filters[category_name]=Electronics&filters[brand_name]=Apple
GET /products?filters[reviews_rating][gte]=4.0
```

#### User Management Filtering
```php
// UserService configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'role', 'status', 'department_id', 'created_at', 'last_login_at'
];

protected const RELATED_FILTERABLE_COLUMNS = [
    'department_name', 'profile_age', 'company_location'
];

// URL examples
GET /users?role=admin&status=active
GET /users?filters[department_name]=Engineering&filters[profile_age][min]=25
GET /users?filters[last_login_at][from]=2024-01-01&filters[company_location]=New York
```

### Security Features

The filtering system includes built-in security:

1. **Column Validation**: Only columns declared in `DIRECT_FILTERABLE_COLUMNS` and `RELATED_FILTERABLE_COLUMNS` can be used for filtering
2. **SQL Injection Prevention**: Column names are validated before being used in queries
3. **Error Messages**: Clear error messages when invalid columns are used

```php
// This will throw an InvalidArgumentException
GET /users?invalid_column=value

// Error: "Filtered column 'invalid_column' is not declared. 
// Allowed columns: status, department_id, role, created_at"
```

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

### Real-World Examples

#### 🛒 E-commerce Product Management

```php
// Get products with advanced filters
GET /products?category_id=1&price_min=100&price_max=500&in_stock=true&sort_by=price&sort_direction=asc

// Search products with multiple criteria
GET /products?search=laptop&category_id=electronics&brand_id=2&status=active

// Export product catalog
GET /products?export=csv&filters[category_id]=1

// Get product suggestions
GET /products?search_suggestions=laptop&limit=10
```

#### 👥 User Management Dashboard

```php
// Get users with pagination and sorting
GET /users?page=1&limit=20&sort_by=created_at&sort_direction=desc

// Filter active users by department
GET /users?filters[status]=active&filters[department_id]=2

// Search users by name or email
GET /users?search=john

// Get users with relationships (via withRelations parameter in code)
$users = $userService->findAll($filters, ['department', 'profile']);
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
// Register hooks using the service method
$userService->addHook('before_create', function($data) {
    $data['created_by'] = Auth::id();
    return $data;
});

$userService->addHook('after_update', function($data) {
    // Send notification
    Mail::to($data['email'])->send(new UserUpdatedNotification($data));
    return $data;
});
```

**Available Hooks:**
- `before_create` / `after_create`
- `before_update` / `after_update`
- `before_delete` / `after_delete`

### 📤 Export Service

Flexible export functionality with strategy pattern:

```php
// Export to CSV
$csv = $userService->export('csv', [], ['name', 'email', 'created_at']);

// Export to JSON
$json = $userService->export('json', [], [], ['pretty' => true]);
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

// Search suggestions
$suggestions = $userService->getSearchSuggestions('john', 5);
```

**Search Features:**
- Text search across declared searchable columns
- Search suggestions
- Configurable search strategies

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
GET /users?page=1&limit=20
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