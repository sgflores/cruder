# CRUDer Examples Documentation

## 📁 Example Classes Overview

The CRUDer package includes comprehensive example classes that demonstrate all features and usage patterns. These examples serve as:

- **Learning Resources**: Understand how to use the package
- **Implementation Guides**: Copy and adapt for your needs
- **Best Practices**: Follow established patterns
- **Testing References**: Use for testing your implementations

## 🏗️ Core Service Examples

### 1. **UserCrudService.php**

**Purpose**: Complete CRUD service example using `BaseCrudService`

**Features Demonstrated**:
- Column validation configuration
- Event listeners for business logic
- Custom query constraints
- Audit trail configuration
- Custom business methods

**Key Methods**:
- `setupEventListeners()` - Shows how to configure event hooks
- `applyCustomQueryConstraints()` - Custom filtering logic
- `getUsersByRole()` - Business-specific query methods

**Example Usage**:
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

**File Location**: `src/Examples/UserCrudService.php`

### 2. **ProductReaderService.php**

**Purpose**: Read-only service example using `BaseReaderService`

**Features Demonstrated**:
- Advanced filtering and search
- Export functionality (optional - not included by default)
- Default like search strategy
- Custom query constraints
- Statistics and reporting methods

**Key Methods**:
- `getFeaturedProducts()` - Custom business queries
- `exportProductsToCsv()` - Export functionality
- `getProductStats()` - Statistics and reporting

**Example Usage**:
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

**File Location**: `src/Examples/ProductReaderService.php`

## 🔍 Strategy Examples

### 3. **CustomSearchStrategy.php**

**Purpose**: Advanced search strategy implementation

**Features Demonstrated**:
- Fuzzy matching with SOUNDEX
- Weighted search results
- Stop word filtering
- Related model searching
- Complex search term processing
- New interface signature: `search(Builder $query, array $filters, ?string $searchTerm = null, array $config = [])`

**Key Features**:
- Multi-term search support
- Search result weighting
- Related column searching
- Fuzzy matching algorithms

**Example Usage**:
```php
<?php

namespace App\Strategies\Search;

use Illuminate\Database\Eloquent\Builder;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

class CustomSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder
    {
        // Use searchTerm parameter if provided, otherwise extract from filters
        $term = $searchTerm ?? $filters['search'] ?? '';
        
        if (empty($term)) {
            return $query;
        }
        
        // Get search configuration
        $columns = $config['direct_columns'] ?? [];
        $relatedColumns = $config['related_columns'] ?? [];
        $fuzzyMatch = $config['fuzzy_match'] ?? false;
        $weightedSearch = $config['weighted_search'] ?? false;
        
        // Clean and prepare search term
        $searchTerms = $this->prepareSearchTerms($term);
        
        if (empty($searchTerms)) {
            return $query;
        }
        
        // Apply advanced search logic
        $query->where(function (Builder $subQuery) use ($searchTerms, $columns, $relatedColumns, $fuzzyMatch, $weightedSearch) {
            foreach ($searchTerms as $searchTerm) {
                // Direct column search
                foreach ($columns as $column) {
                    $subQuery->orWhere($column, 'like', '%' . $searchTerm . '%');
                    
                    if ($fuzzyMatch) {
                        $subQuery->orWhereRaw("SOUNDEX({$column}) = SOUNDEX(?)", [$searchTerm]);
                    }
                }
                
                // Related column search
                foreach ($relatedColumns as $column) {
                    $relationParts = $this->parseRelationColumn($column);
                    if (!empty($relationParts['relation'])) {
                        $subQuery->orWhereHas($relationParts['relation'], function (Builder $relationQuery) use ($relationParts, $searchTerm, $fuzzyMatch) {
                            $relationQuery->where($relationParts['column'], 'like', '%' . $searchTerm . '%');
                            
                            if ($fuzzyMatch) {
                                $relationQuery->orWhereRaw("SOUNDEX({$relationParts['column']}) = SOUNDEX(?)", [$searchTerm]);
                            }
                        });
                    }
                }
            }
        });
        
        return $query;
    }
    
    private function prepareSearchTerms(string $term): array
    {
        // Remove stop words and clean the term
        $stopWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by'];
        $words = explode(' ', strtolower($term));
        $words = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        return array_values($words);
    }
}
```

**File Location**: `src/Examples/CustomSearchStrategy.php`

### 4. **CustomExportStrategy.php**

**Purpose**: Custom export strategy for XML format

**Features Demonstrated**:
- XML export functionality
- Custom data formatting
- Nested data handling
- Custom headers and metadata

**Example Usage**:
```php
<?php

namespace App\Strategies\Export;

use Illuminate\Support\Collection;
use SgFlores\Cruder\Strategies\Export\ExportStrategyInterface;

class CustomExportStrategy implements ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string
    {
        $rootElement = $options['root_element'] ?? 'data';
        $itemElement = $options['item_element'] ?? 'item';
        $includeMetadata = $options['include_metadata'] ?? true;
        
        $xml = new \SimpleXMLElement("<{$rootElement}></{$rootElement}>");
        
        if ($includeMetadata) {
            $metadata = $xml->addChild('metadata');
            $metadata->addChild('export_date', date('Y-m-d H:i:s'));
            $metadata->addChild('total_records', $data->count());
            $metadata->addChild('exported_by', 'System');
        }
        
        $items = $xml->addChild('items');
        
        foreach ($data as $item) {
            $itemXml = $items->addChild($itemElement);
            $this->addItemData($itemXml, $item);
        }
        
        return $xml->asXML();
    }
    
    private function addItemData(\SimpleXMLElement $xml, $item): void
    {
        foreach ($item->getAttributes() as $key => $value) {
            $xml->addChild($key, htmlspecialchars($value));
        }
    }
}
```

**File Location**: `src/Examples/CustomExportStrategy.php`

### 5. **CustomValidationStrategy.php**

**Purpose**: Custom validation strategy with business rules

**Features Demonstrated**:
- Business rule validation
- Custom validation logic
- Email domain validation
- Age validation
- Company user limit checking

**Example Usage**:
```php
<?php

namespace App\Strategies\Validation;

use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;

class CustomValidationStrategy implements ValidationStrategyInterface
{
    public function validate(array $data, string $operation): array
    {
        $errors = [];
        
        // Basic validation
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        }
        
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        }
        
        // Business rule validation
        $errors = array_merge($errors, $this->validateBusinessRules($data, $operation));
        
        return $errors;
    }
    
    private function validateBusinessRules(array $data, string $operation): array
    {
        $errors = [];
        
        // Business rule: User must be 18 or older
        if (isset($data['birth_date'])) {
            $age = \Carbon\Carbon::parse($data['birth_date'])->age;
            if ($age < 18) {
                $errors['birth_date'] = 'User must be 18 or older';
            }
        }
        
        // Business rule: Email domain validation
        if (isset($data['email'])) {
            $domain = substr(strrchr($data['email'], "@"), 1);
            if (!in_array($domain, ['company.com', 'partner.com'])) {
                $errors['email'] = 'Email must be from approved domains';
            }
        }
        
        return $errors;
    }
}
```

**File Location**: `src/Examples/CustomValidationStrategy.php`

## 🔧 Service Usage Examples

### 6. **ServiceUsageExample.php**

**Purpose**: Comprehensive example demonstrating the integration of all services and strategies

**Features Demonstrated**:
- User management workflow
- Product management workflow
- Custom validation workflow
- Export and search workflows

**Key Methods**:
- `demonstrateUserManagement()` - Complete user CRUD workflow
- `demonstrateProductManagement()` - Product read operations
- `demonstrateCustomValidation()` - Custom validation workflow
- `demonstrateExportFunctionality()` - Export workflows
- `demonstrateCustomSearch()` - Custom search workflows

**Example Usage**:
```php
<?php

namespace App\Examples;

use SgFlores\Cruder\Examples\UserCrudService;
use SgFlores\Cruder\Examples\ProductReaderService;
use SgFlores\Cruder\Examples\CustomSearchStrategy;
use SgFlores\Cruder\Examples\CustomValidationStrategy;
use SgFlores\Cruder\Examples\CustomExportStrategy;

class ServiceUsageExample
{
    private UserCrudService $userService;
    private ProductReaderService $productService;
    private CustomSearchStrategy $searchStrategy;
    private CustomValidationStrategy $validationStrategy;
    private CustomExportStrategy $exportStrategy;
    
    public function __construct()
    {
        $this->userService = new UserCrudService();
        $this->productService = new ProductReaderService();
        $this->searchStrategy = new CustomSearchStrategy();
        $this->validationStrategy = new CustomValidationStrategy();
        $this->exportStrategy = new CustomExportStrategy();
    }
    
    public function demonstrateUserManagement(): void
    {
        echo "=== User Management Workflow ===\n\n";
        
        // 1. Create user
        echo "1. Creating user...\n";
        $user = $this->userService->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'department_id' => 1
        ]);
        echo "   ✓ User created with ID: " . $user->id . "\n";
        
        // 2. Find user
        echo "\n2. Finding user...\n";
        $foundUser = $this->userService->findById($user->id);
        echo "   ✓ User found: " . $foundUser->name . "\n";
        
        // 3. Update user
        echo "\n3. Updating user...\n";
        $updatedUser = $this->userService->update($user->id, [
            'name' => 'John Smith'
        ]);
        echo "   ✓ User updated: " . $updatedUser->name . "\n";
        
        // 4. Search users
        echo "\n4. Searching users...\n";
        $users = $this->userService->findAll([
            'search' => 'john',
            'status' => 'active'
        ]);
        echo "   ✓ Found " . $users->count() . " users\n";
        
        // 5. Delete user
        echo "\n5. Deleting user...\n";
        $this->userService->delete($user->id);
        echo "   ✓ User deleted\n";
        
        echo "\n=== User Management Workflow Completed ===\n\n";
    }
}
```

**File Location**: `src/Examples/ServiceUsageExample.php`

### 7. **CustomSearchExample.php**

**Purpose**: Dedicated example for custom search functionality

**Features Demonstrated**:
- Custom search strategy integration
- Search strategy switching
- Advanced search features
- Search performance testing

**Example Usage**:
```php
<?php

namespace App\Examples;

use SgFlores\Cruder\Examples\ProductReaderService;
use SgFlores\Cruder\Examples\CustomSearchStrategy;

class CustomSearchExample
{
    private ProductReaderService $productService;
    private CustomSearchStrategy $customSearchStrategy;
    
    public function __construct()
    {
        $this->productService = new ProductReaderService();
        $this->customSearchStrategy = new CustomSearchStrategy();
    }
    
    public function demonstrateCustomSearch(): void
    {
        echo "=== Custom Search Workflow ===\n\n";
        
        // 1. Add custom search strategy
        echo "1. Adding custom search strategy 'advanced'...\n";
        $this->productService->getSearchService()->addStrategy('advanced', $this->customSearchStrategy);
        echo "   ✓ Custom 'advanced' search strategy added.\n";
        
        // 2. Show available strategies
        echo "\n2. Available search strategies:\n";
        $strategies = $this->productService->getSearchService()->getAvailableStrategies();
        echo "   ✓ Available: " . implode(', ', $strategies) . "\n";
        
        // 3. Test search with default strategy
        echo "\n3. Testing search with default strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000,
                'category_id' => 1
            ]);
            echo "   ✓ Search executed with default strategy\n";
            echo "   ✓ Found " . $products->count() . " products\n";
        } catch (\Exception $e) {
            echo "   ✗ Default search failed: " . $e->getMessage() . "\n";
        }
        
        // 4. Test search with custom strategy
        echo "\n4. Testing search with custom strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000,
                'category_id' => 1,
                'search_strategy' => 'advanced'  // Specify custom strategy
            ]);
            echo "   ✓ Search executed with custom 'advanced' strategy\n";
            echo "   ✓ Found " . $products->count() . " products\n";
        } catch (\Exception $e) {
            echo "   ✗ Custom search failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Custom Search Workflow Completed ===\n";
    }
}
```

**File Location**: `src/Examples/CustomSearchExample.php`

## 🔧 Service Integration Examples

### 8. **EventServiceExample.php**

**Purpose**: Demonstrates event system usage

**Features Demonstrated**:
- Event listener setup
- Event firing
- Event data transformation
- Business logic integration

**File Location**: `src/Examples/EventServiceExample.php`

### 9. **QueryLoggerExample.php**

**Purpose**: Demonstrates query logging and performance monitoring

**Features Demonstrated**:
- Query logging setup
- Performance monitoring
- Slow query detection
- Memory usage tracking

**File Location**: `src/Examples/QueryLoggerExample.php`

## 📚 Complete Examples Directory

All example classes are located in the `src/Examples/` directory:

```
src/Examples/
├── UserCrudService.php              # Complete CRUD service example
├── ProductReaderService.php         # Read-only service example
├── CustomSearchStrategy.php        # Advanced search strategy
├── CustomExportStrategy.php        # Custom export strategy
├── CustomValidationStrategy.php    # Custom validation strategy
├── ServiceUsageExample.php        # Comprehensive usage examples
├── CustomSearchExample.php         # Custom search examples
├── EventServiceExample.php         # Event system examples
├── QueryLoggerExample.php          # Query logging examples
└── README.md                       # Examples documentation
```

## 🎯 How to Use Examples

### 1. **Copy and Adapt**
```php
// Copy the example class
cp src/Examples/UserCrudService.php app/Services/UserService.php

// Modify for your needs
class UserService extends BaseCrudService
{
    // Your custom implementation
}
```

### 2. **Learn from Examples**
```php
// Study the examples to understand patterns
$userService = new UserCrudService();
$user = $userService->create($data);
```

### 3. **Test Your Implementation**
```php
// Use examples as test references
$example = new ServiceUsageExample();
$example->demonstrateUserManagement();
```

### 4. **Extend Examples**
```php
// Extend examples with your own features
class MyCustomService extends UserCrudService
{
    // Your custom methods
}
```

## 🔗 Related Documentation

- **[SERVICES.md](SERVICES.md)** - Service documentation
- **[STRATEGIES.md](STRATEGIES.md)** - Strategy pattern documentation
- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Overall architecture

## 📝 Example README

For detailed documentation of all examples, see:

- **[Examples README](src/Examples/README.md)** - Complete examples documentation

---

This documentation provides a comprehensive guide to all example classes in the CRUDer package, their purposes, features, and usage patterns.
