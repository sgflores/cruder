# Configuration Methods Documentation

## Overview

The Cruder package uses a trait-based configuration system which provides better type safety, IDE support, and flexibility while maintaining sensible defaults.

## Key Benefits

1. **Type Safety**: Return type hints ensure consistent data types
2. **IDE Support**: Better autocompletion and IntelliSense
3. **Flexibility**: Methods can return computed values
4. **Override Capability**: Child classes can easily override any configuration
5. **Clear Contracts**: Interfaces make the contract explicit
6. **Consistent Naming**: Clear, descriptive names that follow established conventions

## Architecture

### Interfaces

- `ReaderConfigurable`: Defines the contract for reader services
- `CrudConfigurable`: Defines the contract for CRUD-specific configuration (auditing, etc.)

### Traits

- `ReaderConfigurationTrait`: Provides default implementations for reader service methods
- `CrudConfigurationTrait`: Provides default implementations for CRUD-specific methods (auditing, etc.)
- `PerformanceMonitoringTrait`: Provides performance monitoring, timing, and debugging functionality

**Note**: `CrudConfigurationTrait` only provides CRUD-specific methods. Classes using this trait should extend `BaseReaderService` or use `ReaderConfigurationTrait` to get all reader methods.

### Base Classes

- `BaseReaderService`: Implements ReaderConfigurable using ReaderConfigurationTrait
- `BaseCrudService`: Extends BaseReaderService and implements CrudConfigurable using CrudConfigurationTrait

## Naming Convention

### Interface Naming
- **Pattern**: `{Purpose}Configurable`
- **Examples**: `ReaderConfigurable`, `CrudConfigurable`
- **Rationale**: Uses the "able" suffix to indicate capabilities, following PHP conventions like `Serializable`, `Countable`

### Trait Naming
- **Pattern**: `{Purpose}Trait` or `{Purpose}ConfigurationTrait`
- **Examples**: `ReaderConfigurationTrait`, `CrudConfigurationTrait`, `PerformanceMonitoringTrait`
- **Rationale**: Clearly indicates the trait's purpose and functionality

### Benefits of This Naming Convention
1. **Self-Documenting**: Names clearly indicate what each component does
2. **Consistent**: Follows established PHP naming patterns
3. **Discoverable**: Easy to find related components in IDEs
4. **Scalable**: Easy to add new configuration types (e.g., `ApiConfigurable`)

## Configuration Methods

### Query Parameters

```php
public function getSearchParam(): string          // Default: 'search'
// Returns the URL parameter name for text search queries

public function getSortByParam(): string          // Default: 'sort_by'
// Returns the URL parameter name for specifying sort column

public function getSortDirectionParam(): string   // Default: 'sort_direction'
// Returns the URL parameter name for sort direction (asc/desc)

public function getPageParam(): string           // Default: 'page'
// Returns the URL parameter name for page number

public function getPerPageParam(): string         // Default: 'per_page'
// Returns the URL parameter name for items per page

public function getLimitParam(): string           // Default: 'limit'
// Returns the URL parameter name for limiting results (non-paginated collection)
```

### Relations

```php
public function getCollectionRelations(): array   // Default: []
// Returns relations to eager load for collection queries (findAll, search)

public function getSingleRecordRelations(): array // Default: []
// Returns relations to eager load for single record queries (findById, findByIdRaw, create, update)
```

### Column Configuration

```php
// Direct columns (from the main model table)
public function getDirectFilterableColumns(): array     // Default: []
// Returns columns that can be used for filtering (WHERE clauses)

public function getDirectTextSearchColumns(): array     // Default: []
// Returns columns that can be searched using text search

public function getDirectSortableColumns(): array       // Default: []
// Returns columns that can be used for sorting (ORDER BY)

// Related columns (from related model tables)
public function getRelatedFilterableColumns(): array    // Default: []
// Returns related model columns that can be used for filtering

public function getRelatedTextSearchColumns(): array    // Default: []
// Returns related model columns that can be searched using text search

public function getRelatedSortableColumns(): array      // Default: []
// Returns related model columns that can be used for sorting
```

### Filter Column Mapping

```php
public function getFilterColumnMapping(): array       // Default: []
// Maps friendly request parameter keys to internal filterable column names
// Allows API consumers to use user-friendly names while internally using proper column names
```

**Example Usage:**

This feature allows you to map user-friendly request parameters (e.g., `role_names[]`, `branch_ids[]`) to internal related column filters (e.g., `assignedRoles.name`, `branches.id`). This provides a clean API while leveraging the full power of related column filtering.

See [src/Examples/UserService.php](../src/Examples/UserService.php) for a complete implementation example.

```php
// In your service:
public function getRelatedFilterableColumns(): array
{
    return [
        'assignedRoles.name',  // Internal column name for role filtering
        'branches.id',         // Internal column name for branch filtering
    ];
}

public function getFilterColumnMapping(): array
{
    return [
        'role_names' => 'assignedRoles.name',  // Map 'role_names' param to 'assignedRoles.name'
        'branch_ids' => 'branches.id',         // Map 'branch_ids' param to 'branches.id'
    ];
}

// API Usage:
// GET /api/users?role_names[]=Cashier&branch_ids[]=1&branch_ids[]=2
// Internally processes as: assignedRoles.name=Cashier & branches.id=[1,2]
```

**Benefits:**
- **Clean API**: Use friendly parameter names (`role_names`) instead of technical names (`assignedRoles.name`)
- **Type-Safe**: Still uses existing column validation from `getRelatedFilterableColumns()`
- **Flexible**: Supports both single values and arrays automatically
- **Reusable**: Any service can use this feature by overriding `getFilterColumnMapping()`


### Custom Filter Columns

```php
public function getCustomFilterColumns(): array     // Default: []
// Returns an array of virtual/computed filter keys
```

Use this method when you want to expose filter parameters that do not map directly to database columns (for example, an `is_store_owner` flag that checks a pivot table or a computed scope). Columns registered here bypass the automatic validation performed on real columns.

Custom filters can be implemented by overriding `applyCustomFilterColumn()` in your service:

```php
use Illuminate\Database\Eloquent\Builder;

public function getCustomFilterColumns(): array
{
    return ['is_store_owner'];
}

protected function applyCustomFilterColumn(Builder $query, string $column, mixed $value): void
{
    if ($column === 'is_store_owner') {
        $shouldBeOwner = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($shouldBeOwner === true) {
            $query->whereHas('stores', fn (Builder $builder) => $builder->wherePivot('is_owner', true));
        } elseif ($shouldBeOwner === false) {
            $query->whereDoesntHave('stores', fn (Builder $builder) => $builder->wherePivot('is_owner', true));
        }
    }
}
```

> 💡 **Tip:** If you also define a mapping in `getFilterColumnMapping()`, be sure to include both the public key and its mapped value (if different) in `getCustomFilterColumns()`.


### Default Behavior

```php
public function getDefaultSortColumn(): string     // Default: 'id'
// Returns the default column to sort by when no sort column is specified

public function getDefaultSortDirection(): string  // Default: 'asc'
// Returns the default sort direction (asc/desc) when no direction is specified
```

### Caching

```php
public function isQueryCacheEnabled(): bool        // Default: false
// Determines if query results should be cached for performance

public function getCacheLifetimeSeconds(): int     // Default: 3600
// Returns the number of seconds to cache query results

```

### Advanced Features

```php
public function getSelectColumns(): array          // Default: []
// Returns specific columns to select (overrides default SELECT *)

public function getExcludeColumns(): array         // Default: []
// Returns columns to exclude from SELECT queries

public function shouldIncludeSoftDeleted(): bool   // Default: false
// Determines if soft-deleted records should be included in results

public function shouldOnlyShowSoftDeleted(): bool  // Default: false
// Determines if only soft-deleted records should be returned

public function shouldEnableApiResources(): bool   // Default: false
// Determines if API resources should be used to transform responses

public function getApiResourceClass(): ?string     // Default: null
// Returns the API resource class to use for response transformation

public function shouldEnableChunkedProcessing(): bool // Default: false
// Determines if large datasets should be processed in chunks

public function getChunkSize(): int                // Default: 1000
// Returns the number of records to process per chunk

public function getDatabaseConnection(): ?string   // Default: null
// Returns the database connection name to use (null = default)
```

### CRUD-Specific Methods

```php
public function isAuditTrailEnabled(): bool        // Default: false
// Determines if audit trail fields should be automatically populated

public function getCreatorColumn(): string         // Default: 'created_by'
// Returns the column name for tracking who created the record

public function getUpdaterColumn(): string         // Default: 'updated_by'
// Returns the column name for tracking who last updated the record

public function getDeleterColumn(): string         // Default: 'deleted_by'
// Returns the column name for tracking who soft-deleted the record
```

### Search Strategy Configuration

```php
public function getStrategiesParam(): string           // Default: 'strategies'
// Returns the URL parameter name for specifying search strategies
// Supports both array and comma-separated string: ['strategies' => 'like,fulltext'] or ['strategies' => ['like', 'fulltext']]
```

## Usage Examples

### Basic Service Implementation

```php
<?php

namespace App\Services;

use App\Models\User;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;

class UserService extends BaseCrudService
{
    public function __construct(
        User $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    // Override only the methods you need to customize
    public function getDirectFilterableColumns(): array
    {
        return ['id', 'name', 'email', 'status'];
    }

    public function getDirectTextSearchColumns(): array
    {
        return ['name', 'email'];
    }

    public function isAuditTrailEnabled(): bool
    {
        return true;
    }
}
```

### Advanced Service Implementation

```php
<?php

namespace App\Services;

use App\Models\Product;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;

class ProductService extends BaseCrudService
{
    public function __construct(
        Product $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'id', 'name', 'sku', 'price', 'status',
            'category_id', 'created_at', 'updated_at'
        ];
    }

    public function getRelatedFilterableColumns(): array
    {
        return [
            'category.name',
            'category.status'
        ];
    }

    public function getCollectionRelations(): array
    {
        return ['category', 'images'];
    }

    public function isQueryCacheEnabled(): bool
    {
        return true;
    }

    public function getCacheLifetimeSeconds(): int
    {
        return 1800; // 30 minutes
    }


    public function shouldEnableApiResources(): bool
    {
        return true;
    }

    public function getApiResourceClass(): string
    {
        return \App\Http\Resources\ProductResource::class;
    }

    // Dynamic configuration based on runtime conditions
    public function getDefaultSortColumn(): string
    {
        return request()->has('featured') ? 'featured_at' : 'name';
    }
}
```

### Custom Search Strategy Service Implementation

```php
<?php

namespace App\Services;

use App\Models\Order;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class SalesReportService extends BaseReaderService
{
    public function __construct(
        Order $model,
        SearchService $searchService,
        ExportService $exportService,
        EventService $eventService,
        QueryLogger $queryLogger
    ) {
        parent::__construct($model, $searchService, $exportService, $eventService, $queryLogger);
        $this->setupCustomSearchStrategies();
    }

    protected function setupCustomSearchStrategies(): void
    {
        $this->searchService->addStrategy(TopCustomersStrategy::key(), new TopCustomersStrategy());
        $this->searchService->addStrategy(TopProductsStrategy::key(), new TopProductsStrategy());
        $this->searchService->addStrategy(SalesByDateStrategy::key(), new SalesByDateStrategy());
    }

    // Custom methods for each strategy
    public function getTopCustomers(int $limit = 10)
    {
        return $this->findAll(['strategies' => TopCustomersStrategy::key(), 'limit' => $limit]);
    }

    public function getTopProducts(int $limit = 10)
    {
        return $this->findAll(['strategies' => TopProductsStrategy::key(), 'limit' => $limit]);
    }

    public function getSalesByDate(string $startDate, string $endDate)
    {
        return $this->findAll([
            'strategies' => SalesByDateStrategy::key(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
    }
}

// Custom search strategy implementation
class TopCustomersStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'topCustomers';
    }

    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        return DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select([
                'customers.id',
                'customers.name as customer_name',
                'customers.email',
                DB::raw('SUM(orders.total_amount) as total_spent'),
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('AVG(orders.total_amount) as average_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            ])
            ->where('orders.status', 'completed')
            ->where('orders.deleted_at', null)
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderBy('total_spent', 'desc');
    }
}
```

### Using the Service

```php
<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use App\Models\User;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        // The service will use the configured columns and behavior
        $users = $this->userService->findAll([
            'search' => request('search'),
            'status' => 'active',
            'sort_by' => 'name',
            'page' => 15
        ]);

        return response()->json($users);
    }

    public function show($id)
    {
        $user = $this->userService->findById($id);
        
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request)
    {
        $user = $this->userService->create($request->validated());
        return response()->json($user, 201);
    }
}
```

## Best Practices

1. **Override Only What You Need**: Don't override methods that use the default values
2. **Use Type Hints**: Always specify return types for better IDE support
3. **Document Your Choices**: Add comments explaining why you're overriding specific methods
4. **Consider Performance**: Use caching and chunked processing for large datasets
5. **Test Your Configuration**: Ensure your column configurations work as expected
6. **Avoid Nested Traits**: Don't use traits within traits - use them directly in classes
7. **Keep Traits Focused**: Each trait should have a single responsibility
8. **Use Descriptive Names**: Trait names should clearly indicate their purpose
9. **Follow Interface Naming Patterns**: Use "able" suffix for interfaces that define capabilities

## Troubleshooting

### Common Issues

1. **Method Not Found**: Ensure you're extending the correct base class
2. **Type Errors**: Check that your return types match the interface
3. **Column Validation Errors**: Make sure all columns are properly declared in the appropriate methods

### Debugging

Use the service methods to inspect your configuration:

```php
$service = new UserService(new User());

// Check what columns are filterable
$filterableColumns = $service->getFilterableColumns();

// Check if caching is enabled
$cacheEnabled = $service->isQueryCacheEnabled();

// Check cache lifetime
$cacheLifetime = $service->getCacheLifetimeSeconds();
```

## Quick Reference

### Query Parameters
- `getSearchParam()` - URL parameter for text search
- `getSortByParam()` - URL parameter for sort column
- `getSortDirectionParam()` - URL parameter for sort direction
- `getPageParam()` - URL parameter for page number
- `getPerPageParam()` - URL parameter for items per page
- `getLimitParam()` - URL parameter for result limit (non-paginated)

### Column Configuration
- `getDirectFilterableColumns()` - Main table columns for filtering
- `getDirectTextSearchColumns()` - Main table columns for text search
- `getDirectSortableColumns()` - Main table columns for sorting
- `getRelatedFilterableColumns()` - Related table columns for filtering
- `getRelatedTextSearchColumns()` - Related table columns for text search
- `getRelatedSortableColumns()` - Related table columns for sorting
- `getFilterColumnMapping()` - Map friendly parameter names to internal column names

### Relations
- `getCollectionRelations()` - Relations for collection queries
- `getSingleRecordRelations()` - Relations for single record queries

### Caching
- `isQueryCacheEnabled()` - Enable/disable query caching
- `getCacheLifetimeSeconds()` - Cache duration in seconds

### CRUD Operations
- `isAuditTrailEnabled()` - Enable/disable audit trail
- `getCreatorColumn()` - Column for creator tracking
- `getUpdaterColumn()` - Column for updater tracking
- `getDeleterColumn()` - Column for deleter tracking

### Search Strategy Configuration
- `getStrategiesParam()` - URL parameter name for search strategies (supports array or comma-separated string)

### Advanced Features
- `getSelectColumns()` - Specific columns to select
- `getExcludeColumns()` - Columns to exclude
- `shouldIncludeSoftDeleted()` - Include soft-deleted records
- `shouldOnlyShowSoftDeleted()` - Show only soft-deleted records
- `shouldEnableApiResources()` - Enable API resource transformation
- `getApiResourceClass()` - API resource class to use
- `shouldEnableChunkedProcessing()` - Enable chunked processing
- `getChunkSize()` - Records per chunk
- `getDatabaseConnection()` - Database connection name

## Dependency Injection Patterns

### BaseCrudService Constructor

The `BaseCrudService` requires proper dependency injection for optimal functionality:

```php
public function __construct(
    Model $model,
    EventService $eventService,
    ValidationService $validationService
)
```

**Required Services:**
- `Model $model` - The Eloquent model to operate on
- `EventService $eventService` - Event handling service (optional)
- `ValidationService $validationService` - Validation service (optional, defaults to new instance)

### BaseReaderService Constructor

The `BaseReaderService` optional services to but needs all services for full functionality:

```php
public function __construct(
    Model $model,
    ?SearchService $searchService = null,
    ?ExportService $exportService = null,
    ?EventService $eventService = null,
    ?QueryLogger $queryLogger = null
)
```

**Services:**
- `Model $model` - The Eloquent model to operate on
- `SearchService $searchService` - Search functionality (optional)
- `ExportService $exportService` - Export functionality (optional)
- `EventService $eventService` - Event handling (optional)
- `QueryLogger $queryLogger` - Query logging (optional)

### Service Validation

Services validate their dependencies at runtime and provide clear error messages:

```php
// This will throw an InvalidArgumentException if SearchService is not injected
$results = $service->findAll(['strategies' => 'custom_strategy']);

// This will throw an InvalidArgumentException if ExportService is not injected
$data = $service->export('csv', $data);
```