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

public function getPaginateParam(): string        // Default: 'page'
// Returns the URL parameter name for pagination

public function getLimitParam(): string           // Default: 'limit'
// Returns the URL parameter name for limiting results per page
```

### Relations

```php
public function getCollectionRelations(): array   // Default: []
// Returns relations to eager load for collection queries (findAll, search)

public function getSingleRecordRelations(): array // Default: []
// Returns relations to eager load for single record queries (findById, create, update)
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
public function shouldEnforceSearchStrategies(): bool  // Default: false
// Determines if the service should only use registered search strategies
// When enabled, bypasses default search implementation completely

public function getDefaultSearchStrategy(): ?string    // Default: null
// Returns the default search strategy to use when strategy enforcement is enabled
// If null, the first registered strategy will be used

public function getSearchStrategyParam(): string       // Default: 'searchStrategy'
// Returns the URL parameter name for specifying single search strategy
// Used in filters: ['searchStrategy' => 'topCustomers']

public function getStrategiesParam(): string           // Default: 'strategies'
// Returns the URL parameter name for specifying multiple search strategies
// Used in filters: ['strategies' => 'like,fulltext']

public function shouldAllowMultipleSearchStrategies(): bool  // Default: false
// Determines if multiple strategies can be executed simultaneously
// When enabled, strategies are combined with AND logic
```

## Usage Examples

### Basic Service Implementation

```php
<?php

namespace App\Services;

use App\Models\User;
use SgFlores\Cruder\BaseCrudService;

class UserService extends BaseCrudService
{
    public function __construct(User $model)
    {
        parent::__construct($model);
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

class ProductService extends BaseCrudService
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
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

### Strategy Enforcement Service Implementation

```php
<?php

namespace App\Services;

use App\Models\Order;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class SalesReportService extends BaseReaderService
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
        $this->setupCustomSearchStrategies();
    }

    // Enable strategy enforcement for reporting
    public function shouldEnforceSearchStrategies(): bool
    {
        return true;
    }

    public function getDefaultSearchStrategy(): ?string
    {
        return 'topCustomers';
    }

    public function getSearchStrategyParam(): string
    {
        return 'searchStrategy';
    }

    public function shouldAllowMultipleSearchStrategies(): bool
    {
        return true;
    }

    protected function setupCustomSearchStrategies(): void
    {
        $this->getSearchService()->addStrategy(TopCustomersStrategy::key(), new TopCustomersStrategy());
        $this->getSearchService()->addStrategy(TopProductsStrategy::key(), new TopProductsStrategy());
        $this->getSearchService()->addStrategy(SalesByDateStrategy::key(), new SalesByDateStrategy());
    }

    // Custom methods for each strategy
    public function getTopCustomers(int $limit = 10)
    {
        return $this->findAll(['searchStrategy' => TopCustomersStrategy::key(), 'limit' => $limit]);
    }

    public function getTopProducts(int $limit = 10)
    {
        return $this->findAll(['searchStrategy' => TopProductsStrategy::key(), 'limit' => $limit]);
    }

    public function getSalesByDate(string $startDate, string $endDate)
    {
        return $this->findAll([
            'searchStrategy' => SalesByDateStrategy::key(),
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
- `getPaginateParam()` - URL parameter for pagination
- `getLimitParam()` - URL parameter for result limit

### Column Configuration
- `getDirectFilterableColumns()` - Main table columns for filtering
- `getDirectTextSearchColumns()` - Main table columns for text search
- `getDirectSortableColumns()` - Main table columns for sorting
- `getRelatedFilterableColumns()` - Related table columns for filtering
- `getRelatedTextSearchColumns()` - Related table columns for text search
- `getRelatedSortableColumns()` - Related table columns for sorting

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
- `shouldEnforceSearchStrategies()` - Enable/disable strategy enforcement
- `getDefaultSearchStrategy()` - Default strategy when enforcement is enabled
- `getSearchStrategyParam()` - URL parameter name for single search strategy
- `getStrategiesParam()` - URL parameter name for multiple search strategies
- `shouldAllowMultipleSearchStrategies()` - Allow multiple strategies execution

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
