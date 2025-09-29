# CRUDer Architecture & Lifecycle

## 🏗️ Overall Application Architecture

The CRUDer package is built on **SOLID principles** and follows **Laravel best practices** to provide a comprehensive CRUD solution. The architecture is designed to be:

- **Extensible**: Easy to add new features and strategies
- **Maintainable**: Clean separation of concerns
- **Testable**: Comprehensive test coverage
- **Performant**: Built-in caching and optimization
- **Secure**: Column validation and SQL injection prevention

## 🔄 BaseCrudService Lifecycle

### 1. **Initialization Phase**
```php
// Constructor sets up the model and initializes services
public function __construct(Model $model)
{
    $this->model = $model;
    $this->initializeServices();
    $this->setupEventListeners();
    $this->configureServices();
}
```

**What happens:**
- Model is set and validated
- Services are initialized (Search, Export, Event, Validation, QueryLogger)
- Event listeners are set up
- Custom services are configured
- Default strategies are registered

### 2. **Read Operations Lifecycle**

#### `findAll()` Method Flow:
```php
findAll(array $filters = [], array $withRelations = [])
├── 1. Apply soft delete constraints
├── 2. Apply field selection (SELECT specific columns)
├── 3. Apply search strategies (like, custom)
├── 4. Apply column filters (exact matches)
├── 5. Apply advanced filters (operators)
├── 6. Apply sorting
├── 7. Apply pagination
├── 8. Execute query with caching
├── 9. Load relationships
├── 10. Transform response
└── 11. Return results
```

#### `findById()` Method Flow:
```php
findById(int $id, array $withRelations = [])
├── 1. Apply soft delete constraints
├── 2. Apply field selection
├── 3. Load relationships
├── 4. Execute query with caching
├── 5. Transform response
└── 6. Return single model
```

### 3. **Create Operations Lifecycle**

#### `create()` Method Flow:
```php
create(array $data)
├── 1. Validate input data
├── 2. Fire 'before_create' event
├── 3. Prepare create data (audit trail, custom logic)
├── 4. Create model in database transaction
├── 5. Fire 'after_create' event
├── 6. Clear related caches
├── 7. Log query performance
└── 8. Return created model
```

### 4. **Update Operations Lifecycle**

#### `update()` Method Flow:
```php
update(int $id, array $data)
├── 1. Find existing model
├── 2. Validate input data
├── 3. Fire 'before_update' event
├── 4. Prepare update data (audit trail, custom logic)
├── 5. Update model in database transaction
├── 6. Fire 'after_update' event
├── 7. Clear related caches
├── 8. Log query performance
└── 9. Return updated model
```

### 5. **Delete Operations Lifecycle**

#### `delete()` Method Flow:
```php
delete(int $id)
├── 1. Find existing model
├── 2. Fire 'before_delete' event
├── 3. Prepare delete data (custom logic)
├── 4. Delete model (soft or hard) in database transaction
├── 5. Fire 'after_delete' event
├── 6. Clear related caches
├── 7. Log query performance
└── 8. Return deletion result
```

### 6. **Bulk Operations Lifecycle**

#### `bulkCreate()` Method Flow:
```php
bulkCreate(array $dataArray)
├── 1. Validate all data
├── 2. Fire 'before_bulk_create' event
├── 3. Process in chunks for memory efficiency
├── 4. Create all models in single transaction
├── 5. Fire 'after_bulk_create' event
├── 6. Clear related caches
└── 7. Return created models
```

## 🔍 BaseReaderService Lifecycle

### 1. **Initialization Phase**
```php
// Constructor sets up the model and initializes services
public function __construct(Model $model)
{
    $this->model = $model;
    $this->initializeServices();
    $this->setupEventListeners();
    $this->configureServices();
}
```

**What happens:**
- Model is set and validated
- Services are initialized (Search, Export, Event, QueryLogger)
- Event listeners are set up
- Custom services are configured
- Default search strategy (like) is registered

### 2. **Read Operations Lifecycle**

#### `findAll()` Method Flow:
```php
findAll(array $filters = [], array $withRelations = [])
├── 1. Apply soft delete constraints
├── 2. Apply field selection (SELECT specific columns)
├── 3. Apply search strategies (like, custom)
├── 4. Apply column filters (exact matches)
├── 5. Apply advanced filters (operators)
├── 6. Apply sorting
├── 7. Apply pagination
├── 8. Execute query with caching
├── 9. Load relationships
├── 10. Transform response
└── 11. Return results
```

#### `searchProducts()` Method Flow:
```php
searchProducts(string $searchTerm, array $filters = [])
├── 1. Add search term to filters
├── 2. Call findAll() with enhanced filters
└── 3. Return search results
```

## 🧩 Service Architecture

### 1. **Core Services**

#### **SearchService**
- **Purpose**: Manages search strategies and applies them to queries
- **Lifecycle**: Initialized in constructor, strategies registered in configureServices()
- **Strategies**: LikeSearchStrategy (default), custom strategies

#### **ExportService**
- **Purpose**: Handles data export in various formats
- **Lifecycle**: Initialized in constructor, strategies registered in configureServices()
- **Strategies**: CsvExportStrategy, JsonExportStrategy, custom strategies

#### **EventService**
- **Purpose**: Manages event listeners and firing
- **Lifecycle**: Initialized in constructor, listeners set up in setupEventListeners()
- **Events**: before_create, after_create, before_update, after_update, before_delete, after_delete

#### **ValidationService**
- **Purpose**: Handles data validation using Laravel Validator
- **Lifecycle**: Initialized in constructor, rules defined in constants
- **Validation**: CREATE_VALIDATION_RULES, UPDATE_VALIDATION_RULES

#### **QueryLogger**
- **Purpose**: Logs queries and performance metrics
- **Lifecycle**: Initialized in constructor, used throughout operations
- **Features**: Query logging, slow query detection, performance monitoring

### 2. **Strategy Pattern Implementation**

#### **Search Strategies**
```php
interface SearchStrategyInterface
{
    public function search(Builder $query, array $filters, ?string $searchTerm = null, array $config = []): Builder;
}
```

#### **Export Strategies**
```php
interface ExportStrategyInterface
{
    public function export(Collection $data, array $options = []): string;
}
```

#### **Validation Strategies**
```php
interface ValidationStrategyInterface
{
    public function validate(array $data, string $operation): array;
}
```

## 🔄 Data Flow Architecture

### 1. **Request Flow**
```
Controller → Service → Strategy → Database
    ↓           ↓         ↓         ↓
  Request   Validation  Strategy  Query
    ↓           ↓         ↓         ↓
  Response ← Transform ← Result ← Database
```

### 2. **Caching Flow**
```
Query → Cache Check → Database → Cache Store
  ↓         ↓           ↓           ↓
Result ← Cache Hit ← Cache Miss ← Cache Store
```

### 3. **Event Flow**
```
Operation → Before Event → Business Logic → After Event
    ↓            ↓              ↓              ↓
  Result ← Event Handler ← Event Handler ← Event Handler
```

## 🏛️ Design Patterns Used

### 1. **Strategy Pattern**
- **Search Strategies**: Interchangeable search algorithms
- **Export Strategies**: Different export formats
- **Validation Strategies**: Custom validation logic

### 2. **Observer Pattern**
- **Event System**: Listeners for before/after operations
- **Hook System**: Extensible business logic

### 3. **Factory Pattern**
- **ValidationFactory**: Creates validators
- **Service Factory**: Creates service instances

### 4. **Template Method Pattern**
- **BaseCrudService**: Defines algorithm structure
- **BaseReaderService**: Defines read operations structure

### 5. **Dependency Injection**
- **Service Dependencies**: Injected services
- **Strategy Dependencies**: Injected strategies

## 🔧 Configuration Architecture

### 1. **Service Configuration**
```php
// Constants define behavior
protected const QUERY_CACHE_ENABLED = true;
protected const CACHE_LIFETIME_SECONDS = 3600;
protected const AUDIT_TRAIL_ENABLED = true;
```

### 2. **Column Configuration**
```php
// Define allowed columns for security
protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'email'];
protected const DIRECT_FILTERABLE_COLUMNS = ['status', 'department_id'];
protected const DIRECT_SORTABLE_COLUMNS = ['name', 'created_at'];
```

### 3. **Validation Configuration**
```php
// Define validation rules
protected const CREATE_VALIDATION_RULES = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users'
];
```

## 🚀 Performance Architecture

### 1. **Caching Strategy**
- **Query Caching**: Cache query results
- **Cache Tags**: Granular cache invalidation
- **Cache Lifetime**: Configurable cache duration

### 2. **Memory Management**
- **Chunked Processing**: Process large datasets in chunks
- **Memory Optimization**: Efficient memory usage
- **Lazy Loading**: Load relationships only when needed

### 3. **Query Optimization**
- **Eager Loading**: Load relationships efficiently
- **Query Logging**: Monitor query performance
- **Slow Query Detection**: Identify performance issues

## 🔒 Security Architecture

### 1. **Column Validation**
- **Whitelist Approach**: Only declared columns are allowed
- **SQL Injection Prevention**: Column names are validated
- **Error Handling**: Clear error messages for invalid columns

### 2. **Input Validation**
- **Laravel Validation**: Uses Laravel's validation system
- **Custom Rules**: Support for custom validation rules
- **Error Handling**: Comprehensive error handling

### 3. **Access Control**
- **User Context**: User-specific cache keys
- **Permission Checks**: Role-based access control
- **Audit Trail**: Track all operations

## 📊 Monitoring Architecture

### 1. **Query Logging**
- **Performance Metrics**: Execution time tracking
- **Slow Query Detection**: Identify performance bottlenecks
- **Memory Usage**: Monitor memory consumption

### 2. **Event Tracking**
- **Operation Events**: Track all CRUD operations
- **Custom Events**: Support for custom events
- **Event Logging**: Log all events for debugging

### 3. **Error Handling**
- **Exception Handling**: Comprehensive exception handling
- **Error Logging**: Log all errors for debugging
- **Graceful Degradation**: Handle errors gracefully

## 🔗 Integration Points

### 1. **Laravel Integration**
- **Service Provider**: Auto-discovery
- **Configuration**: Laravel config system
- **Validation**: Laravel validation system
- **Caching**: Laravel cache system

### 2. **Database Integration**
- **Eloquent ORM**: Uses Laravel's Eloquent ORM
- **Query Builder**: Uses Laravel's query builder
- **Migrations**: Supports Laravel migrations
- **Relationships**: Supports Eloquent relationships

### 3. **Third-party Integration**
- **Export Formats**: CSV, JSON, custom formats
- **Search Engines**: Elasticsearch, custom search
- **Caching**: Redis, Memcached, file cache
- **Logging**: Laravel logging system

## 🎯 Best Practices

### 1. **Service Design**
- **Single Responsibility**: Each service has one responsibility
- **Open/Closed**: Open for extension, closed for modification
- **Dependency Inversion**: Depend on abstractions, not concretions

### 2. **Strategy Design**
- **Interchangeable**: Strategies can be swapped at runtime
- **Testable**: Each strategy can be tested independently
- **Configurable**: Strategies can be configured per service

### 3. **Event Design**
- **Loose Coupling**: Events decouple components
- **Extensible**: Easy to add new event listeners
- **Testable**: Events can be tested independently

### 4. **Caching Design**
- **Granular**: Cache at the right level
- **Invalidation**: Proper cache invalidation
- **Performance**: Cache for performance, not convenience

## 📈 Scalability Considerations

### 1. **Horizontal Scaling**
- **Stateless Services**: Services are stateless
- **Cache Distribution**: Cache can be distributed
- **Database Sharding**: Supports database sharding

### 2. **Vertical Scaling**
- **Memory Optimization**: Efficient memory usage
- **CPU Optimization**: Efficient CPU usage
- **I/O Optimization**: Efficient I/O operations

### 3. **Performance Monitoring**
- **Metrics Collection**: Collect performance metrics
- **Alerting**: Alert on performance issues
- **Optimization**: Continuous performance optimization

---

This architecture provides a solid foundation for building scalable, maintainable, and performant CRUD operations in Laravel applications.
