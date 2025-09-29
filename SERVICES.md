# CRUDer Services Documentation

## 🏗️ Service Architecture Overview

The CRUDer package provides a comprehensive set of services that work together to deliver powerful CRUD functionality. Each service has a specific responsibility and follows the Single Responsibility Principle.

## 🔧 Core Services

### 1. **BaseCrudService**

The main service class that provides complete CRUD operations.

#### **Purpose**
- Handles Create, Read, Update, Delete operations
- Manages bulk operations
- Provides caching and performance monitoring
- Integrates with all other services

#### **Key Methods**
```php
// Basic CRUD operations
public function create(array $data): Model
public function findById(int $id, array $withRelations = []): ?Model
public function update(int $id, array $data): Model
public function delete(int $id): bool

// Bulk operations
public function bulkCreate(array $dataArray): Collection
public function bulkUpdate(array $data, array $filters = []): int
public function bulkDelete(array $filters = []): int

// Read operations (inherited from BaseReaderService)
public function findAll(array $filters = [], array $withRelations = []): Collection
public function count(array $filters = []): int
public function searchProducts(string $searchTerm, array $filters = []): Collection
```

#### **Configuration Constants**
```php
// Caching configuration
protected const QUERY_CACHE_ENABLED = true;
protected const CACHE_LIFETIME_SECONDS = 3600;
protected const CACHE_TAGS = ['users'];

// Audit trail configuration
protected const AUDIT_TRAIL_ENABLED = true;
protected const CREATOR_COLUMN = 'created_by';
protected const UPDATER_COLUMN = 'updated_by';
protected const DELETER_COLUMN = 'deleted_by';

// Soft delete configuration
protected const INCLUDE_SOFT_DELETED = false;
protected const ONLY_SOFT_DELETED = false;

// Performance configuration
protected const ENABLE_CHUNKED_PROCESSING = true;
protected const CHUNK_SIZE = 1000;
```

#### **Example Usage**
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
    protected const RELATED_TEXT_SEARCH_COLUMNS = ['department_name'];
    
    // Define filterable columns
    protected const DIRECT_FILTERABLE_COLUMNS = ['department_id', 'status'];
    protected const RELATED_FILTERABLE_COLUMNS = ['department_name'];
    
    // Define sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'email', 'created_at'];
    protected const RELATED_SORTABLE_COLUMNS = ['department_name'];
    
    // Custom business methods
    public function getUsersByRole(string $role): Collection
    {
        return $this->findAll(['role' => $role]);
    }
    
    public function getActiveUsers(): Collection
    {
        return $this->findAll(['status' => 'active']);
    }
}
```

### 2. **BaseReaderService**

A read-only service that provides advanced querying capabilities.

#### **Purpose**
- Handles Read operations only
- Provides advanced filtering and search
- Supports export functionality
- Optimized for query performance

#### **Key Methods**
```php
// Read operations
public function findAll(array $filters = [], array $withRelations = []): Collection
public function findById(int $id, array $withRelations = []): ?Model
public function count(array $filters = []): int
public function searchProducts(string $searchTerm, array $filters = []): Collection

// Export operations
public function export(string $format, array $filters = [], array $columns = []): string
public function exportToCsv(array $filters = [], array $columns = []): string
public function exportToJson(array $filters = [], array $columns = []): string

// Statistics
public function getStats(array $filters = []): array
public function getCountByStatus(): array
```

#### **Configuration Constants**
```php
// Search configuration
protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'description'];
protected const RELATED_TEXT_SEARCH_COLUMNS = ['category_name'];

// Filtering configuration
protected const DIRECT_FILTERABLE_COLUMNS = ['status', 'category_id'];
protected const RELATED_FILTERABLE_COLUMNS = ['category_name'];

// Sorting configuration
protected const DIRECT_SORTABLE_COLUMNS = ['name', 'price', 'created_at'];
protected const RELATED_SORTABLE_COLUMNS = ['category_name'];
```

#### **Example Usage**
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
    protected const RELATED_TEXT_SEARCH_COLUMNS = ['category_name', 'brand_name'];
    
    // Define filterable columns
    protected const DIRECT_FILTERABLE_COLUMNS = ['status', 'category_id', 'price'];
    protected const RELATED_FILTERABLE_COLUMNS = ['category_name', 'brand_name'];
    
    // Define sortable columns
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'price', 'created_at'];
    protected const RELATED_SORTABLE_COLUMNS = ['category_name', 'brand_name'];
    
    // Custom business methods
    public function getFeaturedProducts(): Collection
    {
        return $this->findAll(['featured' => true]);
    }
    
    public function getProductsByCategory(int $categoryId): Collection
    {
        return $this->findAll(['category_id' => $categoryId]);
    }
    
    public function getProductStats(): array
    {
        return $this->getStats();
    }
}
```

## 🔍 Supporting Services

### 3. **SearchService**

Manages search strategies and applies them to queries.

#### **Purpose**
- Manages different search strategies
- Applies search logic to queries
- Supports custom search implementations

#### **Key Methods**
```php
// Strategy management
public function addStrategy(string $name, SearchStrategyInterface $strategy): void
public function getStrategy(string $name): ?SearchStrategyInterface
public function getAvailableStrategies(): array

// Search execution
public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
```

#### **Default Strategies**
- **LikeSearchStrategy**: Basic LIKE-based search
- **Custom Strategies**: User-defined search implementations

#### **Example Usage**
```php
// Add custom search strategy
$searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());

// Use search in service
$products = $productService->searchProducts('laptop', [
    'min_price' => 500,
    'search_strategy' => 'elasticsearch'
]);
```

### 4. **ExportService**

Handles data export in various formats.

#### **Purpose**
- Manages export strategies
- Exports data in different formats
- Supports custom export implementations

#### **Key Methods**
```php
// Strategy management
public function addStrategy(string $name, ExportStrategyInterface $strategy): void
public function getStrategy(string $name): ?ExportStrategyInterface
public function getAvailableStrategies(): array

// Export execution
public function export(Collection $data, string $format, array $options = []): string
```

#### **Default Strategies**
- **CsvExportStrategy**: CSV export with proper escaping
- **JsonExportStrategy**: JSON export with formatting options
- **Custom Strategies**: User-defined export implementations

#### **Example Usage**
```php
// Add custom export strategy
$exportService->addStrategy('excel', new ExcelExportStrategy());

// Export data
$csvData = $productService->export('csv', [], ['name', 'price']);
$excelData = $productService->export('excel', [], ['name', 'price']);
```

### 5. **EventService**

Manages event listeners and firing.

#### **Purpose**
- Manages event listeners
- Fires events at appropriate times
- Supports custom business logic

#### **Key Methods**
```php
// Event management
public function listen(string $event, callable $callback): void
public function fire(string $event, $data = null): mixed
public function getListeners(string $event): array
```

#### **Available Events**
- `before_create` / `after_create`
- `before_update` / `after_update`
- `before_delete` / `after_delete`
- `before_bulk_create` / `after_bulk_create`
- `before_bulk_update` / `after_bulk_update`
- `before_bulk_delete` / `after_bulk_delete`

#### **Example Usage**
```php
// Setup event listeners
protected function setupEventListeners(): void
{
    $this->getEventService()->listen('after_create', function ($user) {
        // Send welcome email
        Mail::to($user->email)->send(new WelcomeEmail($user));
        return $user;
    });
    
    $this->getEventService()->listen('before_update', function ($data) {
        // Log update attempt
        Log::info('User update attempted', $data);
        return $data;
    });
}
```

### 6. **ValidationService**

Handles data validation using Laravel Validator.

#### **Purpose**
- Validates input data
- Uses Laravel validation rules
- Supports custom validation strategies

#### **Key Methods**
```php
// Validation execution
public function validate(array $data, string $operation): array
public function validateCreate(array $data): array
public function validateUpdate(array $data): array
```

#### **Example Usage**
```php
// Define validation rules in service
protected const CREATE_VALIDATION_RULES = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users'
];

// Validation is automatic in create/update methods
$user = $userService->create($data); // Validates automatically
```

### 7. **QueryLogger**

Logs queries and performance metrics.

#### **Purpose**
- Logs all database queries
- Tracks query performance
- Identifies slow queries
- Monitors memory usage

#### **Key Methods**
```php
// Logging
public function logQuery(string $query, float $executionTime, array $bindings = []): void
public function logSlowQuery(string $query, float $executionTime, array $bindings = []): void
public function logMemoryUsage(): void

// Performance monitoring
public function startTimer(): void
public function endTimer(): float
public function getPerformanceMetrics(): array
```

#### **Configuration**
```php
// Enable query logging
protected const ENABLE_QUERY_LOGGING = true;
protected const SLOW_QUERY_THRESHOLD = 1.0; // seconds
```

## 🔧 Service Integration

### 1. **Service Initialization**

All services are automatically initialized in the constructor:

```php
public function __construct(Model $model)
{
    $this->model = $model;
    $this->initializeServices();
    $this->setupEventListeners();
    $this->configureServices();
}
```

### 2. **Service Configuration**

Override `configureServices()` to customize service behavior:

```php
protected function configureServices(): void
{
    // Add custom search strategies
    $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
    
    // Add custom export strategies
    $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    
    // Add custom validation strategies
    $this->validationService->addStrategy('custom', new CustomValidationStrategy());
}
```

### 3. **Event Integration**

Services work together through events:

```php
// SearchService triggers events
$this->eventService->fire('search_executed', $results);

// ExportService triggers events
$this->eventService->fire('export_completed', $exportData);

// ValidationService triggers events
$this->eventService->fire('validation_failed', $errors);
```

## 🎯 Best Practices

### 1. **Service Design**
- **Single Responsibility**: Each service has one clear purpose
- **Dependency Injection**: Services are injected, not instantiated
- **Interface Segregation**: Use interfaces for service contracts
- **Open/Closed**: Services are open for extension, closed for modification

### 2. **Configuration**
- **Constants**: Use constants for configuration
- **Validation**: Validate all configuration values
- **Documentation**: Document all configuration options
- **Examples**: Provide configuration examples

### 3. **Error Handling**
- **Exceptions**: Use appropriate exception types
- **Logging**: Log all errors and warnings
- **Recovery**: Implement graceful error recovery
- **User Feedback**: Provide clear error messages

### 4. **Performance**
- **Caching**: Use caching appropriately
- **Lazy Loading**: Load data only when needed
- **Query Optimization**: Optimize database queries
- **Memory Management**: Manage memory efficiently

## 📚 Example Classes

For complete examples of service usage, see:

- **[UserCrudService.php](src/Examples/UserCrudService.php)** - Complete CRUD service example
- **[ProductReaderService.php](src/Examples/ProductReaderService.php)** - Read-only service example
- **[ServiceUsageExample.php](src/Examples/ServiceUsageExample.php)** - Comprehensive usage examples
- **[EventServiceExample.php](src/Examples/EventServiceExample.php)** - Event system examples
- **[QueryLoggerExample.php](src/Examples/QueryLoggerExample.php)** - Query logging examples

## 🔗 Related Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Overall architecture and lifecycle
- **[STRATEGIES.md](STRATEGIES.md)** - Strategy pattern implementations
- **[EXAMPLES.md](EXAMPLES.md)** - Complete example classes and usage

---

This documentation provides a comprehensive guide to all services in the CRUDer package, their purposes, configurations, and usage patterns.
