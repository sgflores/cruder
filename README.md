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
| `{related_column}` | mixed | Related column filter | `?department_name=Engineering` |
| `export` | string | Export format (csv/json) | `?export=csv` |
| `search_suggestions` | string | Get search suggestions | `?search_suggestions=john&limit=5` |

**Note:** 
- **Relationships** are loaded via the `$withRelations` parameter in code, not URL parameters
- **Advanced filtering** with operators is done in code using associative arrays, not URL parameters

```php
// Load relationships in code
$users = $userService->findAll($filters, ['department', 'profile']);
$user = $userService->findById($id, ['department', 'posts']);

// Advanced filtering in code
$users = $userService->findAll([
    'age' => ['operator' => 'gte', 'value' => 25],
    'salary' => ['operator' => 'between', 'value' => [50000, 100000]]
]);
```

## 🔍 Advanced Filtering System

Cruder provides a powerful and secure filtering system that supports both direct column filtering and related model filtering.

### Advanced Filtering Examples

#### 1. Basic Direct Filtering
```php
// Service configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'status', 'department_id', 'role', 'is_active'
];

// Code usage
$users = $userService->findAll([
    'status' => 'active',
    'department_id' => 1,
    'role' => 'admin'
]);
```

#### 2. Related Model Filtering
```php
// Service configuration
protected const RELATED_FILTERABLE_COLUMNS = [
    'department_name', 'department_location', 'profile_age', 'company_size'
];

// Code usage
$users = $userService->findAll([
    'department_name' => 'Engineering',
    'profile_age' => 25
]);
```

#### 3. Advanced Filtering with Operators
```php
// Service configuration
protected const DIRECT_FILTERABLE_COLUMNS = [
    'age', 'salary', 'created_at', 'status', 'name'
];

// Code usage with advanced operators
$users = $userService->findAll([
    // Numeric comparisons
    'age' => ['operator' => 'gte', 'value' => 25],
    'salary' => ['operator' => 'between', 'value' => [50000, 100000]],
    
    // Date filtering
    'created_at' => ['operator' => 'gte', 'value' => '2024-01-01'],
    
    // Text filtering
    'name' => ['operator' => 'like', 'value' => '%John%'],
    'status' => ['operator' => 'in', 'value' => ['active', 'pending']],
    
    // Null checks
    'deleted_at' => ['operator' => 'is_null', 'value' => null]
]);
```

**Advanced Filtering Structure:**
Advanced filtering uses associative arrays with two required keys:
- `operator`: The comparison operator to use
- `value`: The value to compare against

```php
// Basic structure
'column_name' => [
    'operator' => 'gte',        // Required: comparison operator
    'value' => 25              // Required: comparison value
]

// Examples
'age' => ['operator' => 'gte', 'value' => 25]
'name' => ['operator' => 'like', 'value' => '%john%']
'status' => ['operator' => 'in', 'value' => ['active', 'pending']]
'price' => ['operator' => 'between', 'value' => [100, 500]]
```

#### 4. Available Advanced Operators

| Operator | Description | Example |
|----------|-------------|---------|
| `gte` | Greater than or equal | `['operator' => 'gte', 'value' => 25]` |
| `gt` | Greater than | `['operator' => 'gt', 'value' => 100]` |
| `lte` | Less than or equal | `['operator' => 'lte', 'value' => 65]` |
| `lt` | Less than | `['operator' => 'lt', 'value' => 1000]` |
| `like` | LIKE pattern matching | `['operator' => 'like', 'value' => '%john%']` |
| `not_like` | NOT LIKE pattern matching | `['operator' => 'not_like', 'value' => '%admin%']` |
| `in` | IN array | `['operator' => 'in', 'value' => [1, 2, 3]]` |
| `not_in` | NOT IN array | `['operator' => 'not_in', 'value' => [4, 5, 6]]` |
| `between` | BETWEEN two values | `['operator' => 'between', 'value' => [100, 500]]` |
| `not_between` | NOT BETWEEN two values | `['operator' => 'not_between', 'value' => [0, 50]]` |
| `is_null` | IS NULL | `['operator' => 'is_null', 'value' => null]` |
| `is_not_null` | IS NOT NULL | `['operator' => 'is_not_null', 'value' => null]` |


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


## 🔧 Overridable Methods

Cruder provides several protected methods that can be overridden in child service classes to customize behavior:

### Data Preparation Methods

#### `prepareCreateData(array $data): array`
Called before creating a record to modify the data array.

```php
protected function prepareCreateData(array $data): array
{
    // Add custom logic before creating
    $data['custom_field'] = 'custom_value';
    $data['slug'] = Str::slug($data['name']);
    
    // Call parent to maintain audit trail
    return parent::prepareCreateData($data);
}
```

#### `prepareUpdateData(array $data): array`
Called before updating a record to modify the data array.

```php
protected function prepareUpdateData(array $data): array
{
    // Add custom logic before updating
    if (isset($data['name'])) {
        $data['slug'] = Str::slug($data['name']);
    }
    
    // Call parent to maintain audit trail
    return parent::prepareUpdateData($data);
}
```

#### `prepareDeleteData(Model $model): Model`
Called before deleting a record to modify the model.

```php
protected function prepareDeleteData(Model $model): Model
{
    // Add custom logic before deleting
    $model->deleted_reason = 'User requested deletion';
    
    // Call parent to maintain audit trail
    return parent::prepareDeleteData($model);
}
```

### Query Customization Methods

#### `applyCustomQueryConstraints(Builder $query, array $filters): void`
Override to add custom query constraints that are applied to all queries. This method is called after all standard filters are applied.

```php
protected function applyCustomQueryConstraints(Builder $query, array $filters): void
{
    // Add custom WHERE clauses
    $query->where('status', '!=', 'archived');
    
    // Add custom joins
    $query->leftJoin('user_permissions', 'users.id', '=', 'user_permissions.user_id');
    
    // Add custom conditions based on user role
    if (Auth::user()->role !== 'admin') {
        $query->where('users.visible', true);
    }
    
    // Add custom conditions based on filters
    if (isset($filters['custom_condition'])) {
        $query->where('custom_field', $filters['custom_condition']);
    }
}
```

**When to Use:**
- Add global query constraints that apply to all operations
- Implement business logic that affects all queries
- Add custom joins or subqueries
- Apply user-specific filtering based on permissions or roles

### Data Transformation Methods

#### `transformResponse($data, array $filters = []): mixed`
Override to transform the response data before returning it.

```php
protected function transformResponse($data, array $filters = []): mixed
{
    // Transform collection data
    if ($data instanceof Collection) {
        return $data->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'formatted_created_at' => $item->created_at->format('Y-m-d H:i:s'),
                'custom_field' => $this->calculateCustomField($item)
            ];
        });
    }
    
    // Transform single model
    if ($data instanceof Model) {
        return [
            'id' => $data->id,
            'name' => $data->name,
            'custom_data' => $this->getCustomData($data)
        ];
    }
    
    return $data;
}
```

### Cache Management Methods

#### `getCacheTags(): array`
Override to customize cache tags for better cache invalidation.

```php
protected function getCacheTags(): array
{
    return [
        'users',
        'user_' . Auth::id(),
        'department_' . $this->getCurrentDepartmentId()
    ];
}
```

### Service Configuration Methods

#### `configureServices(): void`
Override to customize service configurations.

```php
protected function configureServices(): void
{
    parent::configureServices();
    
    // Add custom export strategy
    $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    
    // Add custom search strategy
    $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
}
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