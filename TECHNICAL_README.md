# Cruder - Technical Documentation

## 🏗️ Architecture Overview

Cruder is built on solid architectural principles using the **Strategy Pattern**, **Service Layer Pattern**, and **Repository Pattern** to provide a flexible, maintainable, and extensible CRUD solution.

### Core Components

```
src/
├── BaseCrudService.php          # Main service class
├── CruderServiceProvider.php    # Laravel service provider
├── Services/                    # Service layer
│   ├── ExportService.php        # Export functionality
│   ├── HookService.php          # Hook management
│   ├── QueryLogger.php          # Query logging
│   └── SearchService.php        # Search functionality
├── Strategies/                  # Strategy pattern implementations
│   ├── Export/                  # Export strategies
│   ├── Hooks/                   # Hook strategies
│   └── Search/                  # Search strategies
└── Traits/                      # Shared functionality
    └── CruderTrait.php          # Common helper methods
```

## 🎯 Design Patterns

### 1. Strategy Pattern

The Strategy Pattern is used extensively throughout the package to provide flexible implementations for different behaviors:

#### Export Strategies
```php
interface ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string;
}

class CsvExportStrategy implements ExportStrategyInterface
class JsonExportStrategy implements ExportStrategyInterface
```

#### Search Strategies
```php
interface SearchStrategyInterface
{
    public function search(Builder $query, string $term, array $columns): Builder;
}

class LikeSearchStrategy implements SearchStrategyInterface
class FullTextSearchStrategy implements SearchStrategyInterface
```

#### Hook Strategies
```php
interface HookInterface
{
    public function execute(array $data, array $context = []): array;
}

class CallableHook implements HookInterface
```

### 2. Service Layer Pattern

The `BaseCrudService` acts as the main service layer, providing a clean interface for all CRUD operations:

```php
class BaseCrudService
{
    // Core CRUD operations
    public function findAll(array $filters = []): Collection|LengthAwarePaginator
    public function find(int $id): ?Model
    public function create(array $data): Model
    public function update(int $id, array $data): Model
    public function delete(int $id): bool
    
    // Bulk operations
    public function bulkCreate(array $data): Collection
    public function bulkUpdate(array $filters, array $data): int
    public function bulkDelete(array $filters): int
    
    // Advanced features
    public function export(string $format, array $filters = []): string
    public function getSearchSuggestions(string $term, int $limit = 10): Collection
}
```

### 3. Repository Pattern

While not explicitly implemented as a separate repository class, the `BaseCrudService` encapsulates all database operations, providing a clean abstraction layer over Eloquent models.

## 🔧 Core Implementation

### BaseCrudService Architecture

The `BaseCrudService` is the heart of the package, providing:

#### 1. Configuration Management
```php
// Service-specific configuration through constants
protected const QUERY_CACHE_ENABLED = true;
protected const CACHE_LIFETIME_SECONDS = 3600;
protected const CREATE_VALIDATION_RULES = [];
protected const UPDATE_VALIDATION_RULES = [];
```

#### 2. Query Building Pipeline
```php
protected function applyQueryFilters(Builder $query, array $filters): Builder
{
    // 1. Apply column filters
    $query = $this->applyColumnFilters($query, $filters);
    
    // 2. Apply advanced filters
    $query = $this->applyAdvancedFilters($query, $filters);
    
    // 3. Apply text search
    $query = $this->applyTextSearch($query, $filters);
    
    // 4. Apply sorting
    $query = $this->applySorting($query, $filters);
    
    // 5. Apply custom constraints
    $query = $this->applyCustomQueryConstraints($query, $filters);
    
    return $query;
}
```

#### 3. Column Validation System
```php
protected function validateColumn(string $column, string $operation): void
{
    $allowedColumns = $this->getFilterableColumns();
    
    if (!in_array($column, $allowedColumns)) {
        throw new InvalidArgumentException(
            "Filtered column '{$column}' is not declared. Allowed columns: " . implode(', ', $allowedColumns)
        );
    }
}
```

#### 4. Caching System
```php
protected function buildCacheKey(array $filters, string $operation = 'all'): string
{
    return sprintf(
        '%s:%s:%s:%s',
        $this->model->getTable(),
        $operation,
        md5(json_encode($filters)),
        Auth::id() ?? 'guest'
    );
}
```

## 🚀 Service Implementations

### ExportService

The `ExportService` uses the Strategy Pattern to provide flexible export functionality:

```php
class ExportService
{
    protected array $strategies = [
        'csv' => CsvExportStrategy::class,
        'json' => JsonExportStrategy::class,
    ];
    
    public function export(string $format, Collection $data, array $options = []): string
    {
        $strategy = $this->getStrategy($format);
        return $strategy->export($data, $options);
    }
}
```

**Real-world Example:**
```php
// Export user data to CSV
$users = $userService->findAll(['department_id' => 1]);
$csv = $exportService->export('csv', $users, ['columns' => ['name', 'email']]);

// Export product catalog to JSON
$products = $productService->findAll(['category_id' => 2]);
$json = $exportService->export('json', $products, ['pretty' => true]);
```

### SearchService

The `SearchService` provides intelligent search capabilities:

```php
class SearchService
{
    protected array $strategies = [
        'like' => LikeSearchStrategy::class,
        'fulltext' => FullTextSearchStrategy::class,
    ];
    
    public function search(Builder $query, string $term, array $columns): Builder
    {
        $strategy = $this->getStrategy($this->getSearchStrategy());
        return $strategy->search($query, $term, $columns);
    }
}
```

**Real-world Example:**
```php
// Search users by name or email
$users = $userService->findAll(['search' => 'john', 'search_columns' => ['name', 'email']]);

// Full-text search in product descriptions
$products = $productService->findAll(['search' => 'laptop gaming', 'search_strategy' => 'fulltext']);
```

### HookService

The `HookService` provides a flexible hook system for custom business logic:

```php
class HookService
{
    protected array $hooks = [];
    
    public function registerHook(string $event, HookInterface $hook): void
    public function executeHooks(string $event, array $data, array $context = []): array
}
```

**Real-world Example:**
```php
// Register a hook for user creation
$hookService->registerHook('before_create', new CallableHook(function($data) {
    $data['created_by'] = Auth::id();
    return $data;
}));

// Register a hook for data validation
$hookService->registerHook('after_update', new CallableHook(function($data) {
    // Send notification email
    Mail::to($data['email'])->send(new UserUpdatedNotification($data));
    return $data;
}));
```

### QueryLogger

The `QueryLogger` provides comprehensive query logging with performance monitoring:

```php
class QueryLogger
{
    public function logQuery(string $operation, Builder $query, float $executionTime = 0, array $context = []): void
    {
        // Log query with context, execution time, and bindings
        $logData = $this->prepareLogData($operation, $query, $executionTime, $context);
        $this->writeLog($this->getLogLevel($executionTime), $logData);
    }
}
```

**Real-world Example:**
```php
// Enable query logging in config/cruder.php
'query_logging' => [
    'enabled' => true,
    'log_all_operations' => true,
    'slow_query_threshold' => 1000, // 1 second
    'include_bindings' => true,
    'include_execution_time' => true,
],
```

## 🔄 Data Flow

### 1. Request Processing Flow

```
HTTP Request
    ↓
Controller
    ↓
Service (BaseCrudService)
    ↓
Query Building Pipeline
    ↓
Column Validation
    ↓
Cache Check
    ↓
Database Query
    ↓
Hook Execution
    ↓
Response Transformation
    ↓
HTTP Response
```

### 2. Query Building Pipeline

```
Raw Filters
    ↓
Column Filters (WHERE clauses)
    ↓
Advanced Filters (Date ranges, etc.)
    ↓
Text Search (LIKE or Full-text)
    ↓
Sorting (ORDER BY)
    ↓
Pagination (LIMIT/OFFSET)
    ↓
Final Query
```

### 3. Caching Flow

```
Query Request
    ↓
Build Cache Key
    ↓
Check Cache
    ↓
Cache Hit? → Return Cached Data
    ↓
Cache Miss? → Execute Query
    ↓
Store in Cache
    ↓
Return Data
```

## 🎯 Real-World Use Cases

### 1. E-commerce Product Management

```php
class ProductService extends BaseCrudService
{
    protected $model = Product::class;
    
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'category_id', 'brand_id', 'price', 'in_stock', 'status'
    ];
    
    protected const DIRECT_SORTABLE_COLUMNS = [
        'name', 'price', 'created_at', 'updated_at'
    ];
    
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [
        'name', 'description', 'sku'
    ];
}

// Usage
$products = $productService->findAll([
    'category_id' => 1,
    'price_min' => 100,
    'price_max' => 500,
    'in_stock' => true,
    'sort' => 'price',
    'order' => 'asc',
    'search' => 'laptop',
    'page' => 1,
    'per_page' => 20
]);
```

### 2. User Management System

```php
class UserService extends BaseCrudService
{
    protected $model = User::class;
    
    protected const CREATE_VALIDATION_RULES = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'role' => 'required|in:admin,user,moderator',
        'department_id' => 'required|exists:departments,id'
    ];
    
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'role', 'department_id', 'status', 'created_at'
    ];
}

// Usage
$users = $userService->findAll([
    'role' => 'user',
    'department_id' => 2,
    'status' => 'active',
    'search' => 'john',
    'sort' => 'created_at',
    'order' => 'desc'
]);
```

### 3. Content Management System

```php
class ArticleService extends BaseCrudService
{
    protected $model = Article::class;
    
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [
        'title', 'content', 'excerpt'
    ];
    
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'status', 'category_id', 'author_id', 'published_at'
    ];
}

// Usage
$articles = $articleService->findAll([
    'status' => 'published',
    'category_id' => 3,
    'search' => 'laravel tutorial',
    'sort' => 'published_at',
    'order' => 'desc',
    'with' => ['author', 'category']
]);
```

## 🔧 Extension Points

### 1. Custom Export Strategies

```php
class ExcelExportStrategy implements ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string
    {
        // Implement Excel export logic
        return $excelContent;
    }
}

// Register in ExportService
$exportService->registerStrategy('excel', ExcelExportStrategy::class);
```

### 2. Custom Search Strategies

```php
class ElasticsearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, string $term, array $columns): Builder
    {
        // Implement Elasticsearch search logic
        return $query;
    }
}

// Register in SearchService
$searchService->registerStrategy('elasticsearch', ElasticsearchStrategy::class);
```

### 3. Custom Hooks

```php
class AuditLogHook implements HookInterface
{
    public function execute(array $data, array $context = []): array
    {
        // Log the operation for audit purposes
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => $context['operation'],
            'model' => $context['model'],
            'data' => $data
        ]);
        
        return $data;
    }
}

// Register hook
$hookService->registerHook('after_create', new AuditLogHook());
$hookService->registerHook('after_update', new AuditLogHook());
$hookService->registerHook('after_delete', new AuditLogHook());
```

## 🚀 Performance Considerations

### 1. Caching Strategy
- Query results are cached with configurable lifetime
- Cache keys include user context for security
- Cache can be invalidated granularly using tags

### 2. Query Optimization
- Eager loading for relationships
- Column selection to reduce data transfer
- Efficient filtering and sorting
- Pagination to limit result sets

### 3. Memory Management
- Chunked processing for large datasets
- Lazy loading for relationships
- Efficient data structures

## 🔒 Security Features

### 1. Column Validation
- All filterable, sortable, and searchable columns must be explicitly declared
- Prevents SQL injection through column names
- Throws exceptions for invalid column usage

### 2. Input Validation
- Comprehensive validation rules for create/update operations
- Sanitization of user input
- Type checking and format validation

### 3. Access Control
- User context in cache keys
- Soft delete support for data retention
- Audit logging capabilities

---

## 📚 Related Documentation

- **[README.md](README.md)** - Package overview, installation, and quick start guide
- **[OPTIONS_README.md](OPTIONS_README.md)** - Complete configuration options reference
- **[QUERY_LOGGING.md](QUERY_LOGGING.md)** - Comprehensive query logging documentation

This technical documentation provides a comprehensive understanding of how Cruder works internally, its design patterns, and how to extend it for your specific needs.
