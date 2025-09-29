# CRUDER Package Examples

This directory contains comprehensive examples demonstrating how to use all the features of the CRUDER package.

## 📁 Example Files

### Core Service Examples

#### `UserCrudService.php`
- **Purpose**: Complete CRUD service example using `BaseCrudService`
- **Features Demonstrated**:
  - Column validation configuration
  - Event listeners for business logic
  - Custom query constraints
  - Audit trail configuration
  - Custom business methods
- **Key Methods**:
  - `setupEventListeners()` - Shows how to configure event hooks
  - `applyCustomQueryConstraints()` - Custom filtering logic
  - `getUsersByRole()` - Business-specific query methods

#### `ProductReaderService.php`
- **Purpose**: Read-only service example using `BaseReaderService`
- **Features Demonstrated**:
  - Advanced filtering and search
  - Export functionality (optional - not included by default)
  - Default like search strategy
  - Custom query constraints
  - Statistics and reporting methods
- **Key Methods**:
  - `getFeaturedProducts()` - Custom business queries
  - `exportProductsToCsv()` - Export functionality
  - `getProductStats()` - Statistics and reporting

### Strategy Examples

#### `CustomValidationStrategy.php`
- **Purpose**: Custom validation strategy implementation
- **Features Demonstrated**:
  - Complex business rule validation
  - Database-dependent validation
  - Custom error messages
  - Post-validation processing
  - Operation-specific rules
- **Key Features**:
  - Email domain validation
  - Company user limit checking
  - Business hours validation
  - Role assignment validation

#### `CustomSearchStrategy.php`
- **Purpose**: Advanced search strategy implementation
- **Features Demonstrated**:
  - Fuzzy matching with SOUNDEX
  - Weighted search results
  - Stop word filtering
  - Related model searching
  - Complex search term processing
  - New interface signature: `search(Builder $query, array $filters, ?string $searchTerm = null, array $config = [])`
- **Key Features**:
  - Multi-term search support
  - Search result weighting
  - Related column searching
  - Fuzzy matching algorithms

#### `CustomExportStrategy.php`
- **Purpose**: Custom export strategy for XML format
- **Features Demonstrated**:
  - XML generation with metadata
  - Nested data handling
  - Custom formatting
  - Field name cleaning
  - Date/time formatting
- **Key Features**:
  - Structured XML output
  - Metadata inclusion
  - Nested object support
  - Custom field formatting

### Service Usage Examples

#### `EventServiceExample.php`
- **Purpose**: Event system usage patterns
- **Features Demonstrated**:
  - Event listener registration
  - Business event patterns
  - Event firing scenarios
  - Event management utilities
- **Key Scenarios**:
  - User lifecycle events
  - Order processing events
  - Notification events
  - Audit and security events

#### `QueryLoggerExample.php`
- **Purpose**: Query logging usage patterns
- **Features Demonstrated**:
  - Different query types logging
  - Performance monitoring
  - Slow query detection
  - Context data inclusion
- **Key Scenarios**:
  - CRUD operation logging
  - Search query logging
  - Bulk operation logging
  - Performance analysis

#### `ServiceUsageExample.php`
- **Purpose**: Complete integration example
- **Features Demonstrated**:
  - Service integration patterns
  - Strategy usage
  - Event system integration
  - End-to-end workflows
- **Key Workflows**:
  - User management workflow
  - Product management workflow
  - Custom validation workflow
  - Export and search workflows

## 🚀 Quick Start

### Search Strategy Interface (Updated)
```php
// New interface signature for search strategies
public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
{
    // Extract search term from filters if not provided
    $term = $searchTerm ?? $filters['search'] ?? '';
    
    // Handle array search terms
    if (is_array($term)) {
        $term = implode(' ', $term);
    }
    
    // Apply search logic...
    return $query;
}
```

### 1. Basic CRUD Service
```php
use SgFlores\Cruder\Examples\UserCrudService;

$userService = new UserCrudService();

// Create user with validation
$user = $userService->create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => 'securepassword123',
    'password_confirmation' => 'securepassword123',
    'status' => 'active',
    'terms_accepted' => true
], ['profile', 'roles'], $validationRules);

// Find users with filters
$users = $userService->findAll([
    'status' => 'active',
    'search' => 'john',
    'sort_by' => 'created_at',
    'sort_direction' => 'desc'
]);
```

### 2. Reader Service
```php
use SgFlores\Cruder\Examples\ProductReaderService;

$productService = new ProductReaderService();

// Search products (uses default like strategy)
$products = $productService->searchProducts('laptop', [
    'min_price' => 500,
    'max_price' => 2000,
    'category_id' => 1
]);

// Export to CSV (requires export strategies to be added)
$csvData = $productService->exportProductsToCsv(['status' => 'published']);
```

### 3. Custom Validation
```php
use SgFlores\Cruder\Examples\CustomValidationStrategy;

$validationStrategy = new CustomValidationStrategy();
$userService->setValidationStrategy($validationStrategy);

// Validation will now use custom business rules
$user = $userService->create($userData, $relations, $validationStrategy);
```

### 4. Event System
```php
$eventService = $userService->getEventService();

// Add event listeners
$eventService->listen('user.created', function ($user) {
    // Send welcome email
    // Create user profile
    // Assign default role
});

// Fire events
$eventService->fire('user.created', $user);
```

## 📋 Configuration Examples

### Column Validation
```php
// Define filterable columns
protected const DIRECT_FILTERABLE_COLUMNS = [
    'id', 'name', 'email', 'status'
];

// Define searchable columns
protected const DIRECT_TEXT_SEARCH_COLUMNS = [
    'name', 'email'
];

// Define sortable columns
protected const DIRECT_SORTABLE_COLUMNS = [
    'id', 'name', 'email', 'created_at'
];
```

### Event Listeners
```php
protected function setupEventListeners(): void
{
    $this->getEventService()->listen('before_create', function ($data) {
        // Validate business rules
        // Check permissions
        // Log creation attempt
    });
    
    $this->getEventService()->listen('after_create', function ($user) {
        // Send notifications
        // Create related records
        // Log successful creation
    });
}
```

### Custom Query Constraints
```php
protected function applyCustomQueryConstraints($query, array $filters): void
{
    // Only show active records by default
    if (!isset($filters['include_inactive'])) {
        $query->where('status', 'active');
    }
    
    // Filter by date range
    if (isset($filters['created_from'])) {
        $query->where('created_at', '>=', $filters['created_from']);
    }
}
```

## 🔧 Customization

### Recent Changes

#### Search Strategy Interface Update
- **New Interface**: `search(Builder $query, array $filters, ?string $searchTerm = null, array $config = [])`
- **Parameters**: 
  - `$query`: Eloquent query builder
  - `$filters`: Array of all query options
  - `$searchTerm`: Optional search term (can be extracted from `$filters['search']`)
  - `$config`: Optional configuration (contains `direct_columns`, `related_columns`)

#### Default Strategy Configuration
- **Search Strategy**: Like strategy is included by default
- **Export Strategies**: Must be explicitly added by child classes
- **Configuration**: Override `configureServices()` to add custom strategies

### Adding Custom Strategies
```php
// Add custom search strategy
$searchService->addStrategy('custom', new CustomSearchStrategy());

// Add custom search strategy
$productService->getSearchService()->addStrategy('custom', new CustomSearchStrategy());

// Add export strategies (optional - not included by default)
$exportService->addStrategy('csv', new CsvExportStrategy());
$exportService->addStrategy('json', new JsonExportStrategy());
$exportService->addStrategy('xml', new CustomExportStrategy());

// Add custom validation strategy
$userService->setValidationStrategy(new CustomValidationStrategy());
```

### Event Management
```php
// Check if event has listeners
if ($eventService->hasListeners('user.created')) {
    // Event has listeners
}

// Get all registered events
$events = $eventService->getEvents();

// Clear all listeners
$eventService->flush();
```

## 📝 Notes

- All examples use placeholder models and data
- Replace `App\Models\User` and `App\Models\Product` with your actual models
- Authentication helpers (`auth()->user()`, `auth()->id()`) are simplified for examples
- Business logic is represented with comments for clarity
- Examples focus on demonstrating patterns rather than complete implementations

## 🎯 Best Practices

1. **Column Validation**: Always define filterable, searchable, and sortable columns
2. **Event Listeners**: Use events for business logic that should be decoupled
3. **Custom Constraints**: Override `applyCustomQueryConstraints()` for business-specific filtering
4. **Validation Strategies**: Use custom strategies for complex business rules
5. **Export Strategies**: Create custom strategies for specific data formats
6. **Search Strategies**: Implement custom search for advanced search requirements
7. **Error Handling**: Always wrap service calls in try-catch blocks
8. **Performance**: Use caching and query optimization for better performance
