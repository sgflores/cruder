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
    public function findById(int $id): ?Model
    public function create(array $data): Model
    public function update(int $id, array $data): Model
    public function delete(int $id): bool
    
    // Bulk operations
    public function bulkCreate(array $data): Collection
    public function bulkUpdate(array $filters, array $data): int
    public function bulkDelete(array $filters): int
    
    // Advanced features
    public function export(string $format, array $filters = [], array $columns = [], array $options = []): string
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

#### 4. Advanced Filtering System

The filtering system supports both direct column filtering and related model filtering:

**Direct Column Filtering:**
```php
// Filters columns directly on the main model
protected const DIRECT_FILTERABLE_COLUMNS = [
    'status', 'department_id', 'role', 'created_at'
];

// Usage: GET /users?status=active&department_id=1
```

**Related Column Filtering:**
```php
// Filters columns on related models using underscore notation
protected const RELATED_FILTERABLE_COLUMNS = [
    'department_name', 'profile_age', 'company_location'
];

// Usage: GET /users?department_name=Engineering
```

**Filtering Implementation:**
The filtering system validates columns against declared filterable columns and applies appropriate WHERE clauses based on column type (direct vs related).

**Advanced Filtering:**
Supports range operations with operators like `gte`, `gt`, `lte`, `lt`, `from`, `to`, `min`, `max` for complex filtering scenarios.

#### 4. Caching System
Cache keys are built using table name, operation type, filter hash, and user context for security and uniqueness.

## 🚀 Service Implementations

### ExportService

The `ExportService` uses the Strategy Pattern to provide flexible export functionality with pluggable strategies for different formats (CSV, JSON, etc.).


### SearchService

The `SearchService` provides intelligent search capabilities with configurable strategies (LIKE, full-text, etc.).


### HookService

The `HookService` provides a flexible hook system for custom business logic with support for before/after operations.


### QueryLogger

The `QueryLogger` provides comprehensive query logging with performance monitoring, execution time tracking, and configurable log levels.


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


## 🔧 Extension Points

### 1. Custom Export Strategies

Implement `ExportStrategyInterface` to create custom export formats (Excel, PDF, etc.).

### 2. Custom Search Strategies

Implement `SearchStrategyInterface` to create custom search strategies (Elasticsearch, Algolia, etc.).

### 3. Custom Hooks

Implement `HookInterface` to create custom business logic hooks for audit logging, notifications, etc.

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

## 🔧 Overridable Methods

The BaseCrudService provides several protected methods that can be overridden in child classes to customize behavior:

### Data Preparation Methods

#### `prepareCreateData(array $data): array`
Called before creating a record to modify the data array. The base implementation handles audit trail fields.

```php
protected function prepareCreateData(array $data): array
{
    // Custom logic before creating
    $data['slug'] = Str::slug($data['name']);
    $data['hash'] = Hash::make($data['password']);
    
    // Call parent to maintain audit trail
    return parent::prepareCreateData($data);
}
```

#### `prepareUpdateData(array $data): array`
Called before updating a record to modify the data array.

```php
protected function prepareUpdateData(array $data): array
{
    // Custom logic before updating
    if (isset($data['password'])) {
        $data['password'] = Hash::make($data['password']);
    }
    
    return parent::prepareUpdateData($data);
}
```

#### `prepareDeleteData(Model $model): Model`
Called before deleting a record to modify the model.

```php
protected function prepareDeleteData(Model $model): Model
{
    // Custom logic before deleting
    $model->deleted_reason = request('reason', 'No reason provided');
    
    return parent::prepareDeleteData($model);
}
```

### Query Customization Methods

#### `applyCustomQueryConstraints(Builder $query, array $filters): void`
Override to add custom query constraints that are applied to all queries. This method is called after all standard filters are applied.

```php
protected function applyCustomQueryConstraints(Builder $query, array $filters): void
{
    // Add global constraints
    $query->where('status', '!=', 'archived');
    
    // Add custom joins
    $query->leftJoin('user_permissions', 'users.id', '=', 'user_permissions.user_id');
    
    // Add role-based filtering
    if (Auth::user()->role !== 'admin') {
        $query->where('users.visible', true);
    }
}
```

### Data Transformation Methods

#### `transformResponse($data, array $filters = []): mixed`
Override to transform the response data before returning it.

```php
protected function transformResponse($data, array $filters = []): mixed
{
    if ($data instanceof Collection) {
        return $data->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'formatted_date' => $item->created_at->format('Y-m-d'),
                'custom_field' => $this->calculateCustomField($item)
            ];
        });
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
    
    // Add custom strategies
    $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
}
```

---

## 📚 Related Documentation

- **[README.md](README.md)** - Package overview, installation, and quick start guide
- **[OPTIONS_README.md](OPTIONS_README.md)** - Complete configuration options reference
- **[QUERY_LOGGING.md](QUERY_LOGGING.md)** - Comprehensive query logging documentation

This technical documentation provides a comprehensive understanding of how Cruder works internally, its design patterns, and how to extend it for your specific needs.
