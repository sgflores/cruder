# Cruder - Configuration Options Reference

This document provides a comprehensive reference for all available configuration options in the Cruder package.

## 📋 Table of Contents

- [Service Configuration](#service-configuration)
- [Query Configuration](#query-configuration)
- [Caching Configuration](#caching-configuration)
- [Validation Configuration](#validation-configuration)
- [Export Configuration](#export-configuration)
- [Search Configuration](#search-configuration)
- [Hook Configuration](#hook-configuration)
- [Query Logging Configuration](#query-logging-configuration)
- [Performance Configuration](#performance-configuration)
- [URL Parameters](#url-parameters)

## 🔧 Service Configuration

### Core Service Constants

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `$model` | string | Required | Eloquent model class | `User::class` |
| `QUERY_CACHE_ENABLED` | bool | `false` | Enable query caching | `true` |
| `CACHE_LIFETIME_SECONDS` | int | `3600` | Cache lifetime in seconds | `7200` |
| `CACHE_TAGS` | array | `[]` | Cache tags for invalidation | `['users', 'test']` |

### Soft Delete Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `INCLUDE_SOFT_DELETED` | bool | `false` | Include soft deleted records | `true` |
| `ONLY_SOFT_DELETED` | bool | `false` | Only show soft deleted records | `false` |

### API Resources Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `ENABLE_API_RESOURCES` | bool | `false` | Enable API resource transformation | `true` |
| `API_RESOURCE_CLASS` | string\|null | `null` | API resource class for transformation | `UserResource::class` |

### Chunked Processing Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `ENABLE_CHUNKED_PROCESSING` | bool | `false` | Enable chunked processing for large datasets | `true` |
| `CHUNK_SIZE` | int | `1000` | Number of records per chunk | `500` |

## 🔍 Query Configuration

### Column Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `SELECT_COLUMNS` | array | `[]` | Specific columns to select | `['id', 'name', 'email']` |
| `EXCLUDE_COLUMNS` | array | `[]` | Columns to exclude from results | `['password', 'remember_token']` |

### Filtering Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `DIRECT_FILTERABLE_COLUMNS` | array | `[]` | Columns that can be filtered directly | `['status', 'department_id']` |
| `RELATION_FILTERABLE_COLUMNS` | array | `[]` | Related model columns for filtering | `['department.name', 'profile.age']` |

### Sorting Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `DIRECT_SORTABLE_COLUMNS` | array | `[]` | Columns that can be sorted directly | `['name', 'created_at']` |
| `RELATION_SORTABLE_COLUMNS` | array | `[]` | Related model columns for sorting | `['department.name', 'profile.created_at']` |

### Search Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `DIRECT_TEXT_SEARCH_COLUMNS` | array | `[]` | Columns for direct text search | `['name', 'email']` |
| `RELATION_TEXT_SEARCH_COLUMNS` | array | `[]` | Related model columns for text search | `['department.name', 'profile.bio']` |
| `SEARCH_STRATEGY` | string | `'like'` | Default search strategy | `'fulltext'` |

### Pagination Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `PAGINATE_PARAM` | string | `'page'` | URL parameter for pagination | `'page'` |
| `LIMIT_PARAM` | string | `'per_page'` | URL parameter for limit | `'limit'` |
| `DEFAULT_PAGE_SIZE` | int | `15` | Default number of records per page | `20` |

## ✅ Validation Configuration

### Validation Rules

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `CREATE_VALIDATION_RULES` | array | `[]` | Validation rules for create operations | `['name' => 'required\|string']` |
| `UPDATE_VALIDATION_RULES` | array | `[]` | Validation rules for update operations | `['name' => 'sometimes\|string']` |

### Example Validation Rules

```php
protected const CREATE_VALIDATION_RULES = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'password' => 'required|string|min:8',
    'department_id' => 'required|exists:departments,id',
    'status' => 'required|in:active,inactive',
    'role' => 'required|in:admin,user,moderator'
];

protected const UPDATE_VALIDATION_RULES = [
    'name' => 'sometimes|string|max:255',
    'email' => 'sometimes|email|unique:users,email,{id}',
    'password' => 'sometimes|string|min:8',
    'department_id' => 'sometimes|exists:departments,id',
    'status' => 'sometimes|in:active,inactive',
    'role' => 'sometimes|in:admin,user,moderator'
];
```

## 📤 Export Configuration

### Export Service Configuration

| Property | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `$exportStrategies` | array | `['csv' => CsvExportStrategy::class, 'json' => JsonExportStrategy::class]` | Available export strategies | `['excel' => ExcelExportStrategy::class]` |

### Export Options

| Option | Type | Description | Example |
|--------|------|-------------|---------|
| `columns` | array | Specific columns to export | `['name', 'email', 'created_at']` |
| `headers` | array | Custom column headers | `['name' => 'Full Name', 'email' => 'Email Address']` |
| `pretty` | bool | Pretty print JSON | `true` |
| `delimiter` | string | CSV delimiter | `','` |
| `enclosure` | string | CSV enclosure | `'"'` |

## 🔍 Search Configuration

### Search Strategies

| Strategy | Class | Description | Use Case |
|----------|-------|-------------|----------|
| `like` | `LikeSearchStrategy` | SQL LIKE queries | General text search |
| `fulltext` | `FullTextSearchStrategy` | Full-text search | Advanced text search |

### Search Options

| Option | Type | Description | Example |
|--------|------|-------------|---------|
| `search` | string | Search term | `'john doe'` |
| `search_columns` | array | Columns to search in | `['name', 'email']` |
| `search_strategy` | string | Search strategy to use | `'fulltext'` |
| `search_case_sensitive` | bool | Case sensitive search | `false` |

## 🪝 Hook Configuration

### Hook Events

| Event | Description | Context Data |
|-------|-------------|--------------|
| `before_create` | Before creating a record | `['data' => $data]` |
| `after_create` | After creating a record | `['data' => $data, 'model' => $model]` |
| `before_update` | Before updating a record | `['data' => $data, 'id' => $id]` |
| `after_update` | After updating a record | `['data' => $data, 'model' => $model]` |
| `before_delete` | Before deleting a record | `['id' => $id]` |
| `after_delete` | After deleting a record | `['id' => $id, 'model' => $model]` |

### Hook Types

| Type | Class | Description | Example |
|------|-------|-------------|---------|
| `callable` | `CallableHook` | PHP callable function | `function($data) { return $data; }` |
| `class` | Custom class | Custom hook class | `class AuditLogHook implements HookInterface` |

## 📊 Query Logging Configuration

### Basic Configuration

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `enabled` | bool | `false` | Enable query logging | `true` |
| `log_all_operations` | bool | `true` | Log all operations | `true` |
| `log_level` | string | `'debug'` | Default log level | `'info'` |
| `include_bindings` | bool | `true` | Include query bindings | `true` |
| `include_execution_time` | bool | `true` | Include execution time | `true` |

### Operation-Specific Logging

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `operations.find` | bool | `true` | Log find operations | `true` |
| `operations.create` | bool | `true` | Log create operations | `true` |
| `operations.update` | bool | `true` | Log update operations | `true` |
| `operations.delete` | bool | `true` | Log delete operations | `true` |
| `operations.count` | bool | `true` | Log count operations | `true` |
| `operations.bulkCreate` | bool | `true` | Log bulk create operations | `true` |
| `operations.bulkUpdate` | bool | `true` | Log bulk update operations | `true` |
| `operations.bulkDelete` | bool | `true` | Log bulk delete operations | `true` |
| `operations.export` | bool | `true` | Log export operations | `true` |
| `operations.searchSuggestions` | bool | `true` | Log search suggestion operations | `true` |

### Performance Monitoring

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `log_slow_queries_only` | bool | `false` | Only log slow queries | `true` |
| `slow_query_threshold` | int | `1000` | Slow query threshold in milliseconds | `500` |
| `slow_query_log_level` | string | `'warning'` | Log level for slow queries | `'error'` |

### Log Channels

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `channels.default` | string | `'single'` | Default log channel | `'daily'` |
| `channels.slow_queries` | string | `'single'` | Slow queries log channel | `'slow_queries'` |

## ⚡ Performance Configuration

### Caching Performance

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `CACHE_LIFETIME_SECONDS` | int | `3600` | Cache lifetime | `7200` |
| `CACHE_TAGS` | array | `[]` | Cache tags | `['users', 'departments']` |

### Query Performance

| Option | Type | Default | Description | Example |
|--------|------|---------|-------------|---------|
| `DEFAULT_PAGE_SIZE` | int | `15` | Default page size | `20` |
| `CHUNK_SIZE` | int | `1000` | Chunk size for processing | `500` |

## 🌐 URL Parameters

### Basic Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `page` | int | Page number for pagination | `?page=2` |
| `per_page` | int | Number of records per page | `?per_page=20` |
| `sort` | string | Column to sort by | `?sort=name` |
| `order` | string | Sort order (asc/desc) | `?order=desc` |
| `search` | string | Search term | `?search=john` |
| `fields` | string | Comma-separated fields to select | `?fields=id,name,email` |
| `with` | string | Comma-separated relationships to load | `?with=department,posts` |

### Filtering Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `{column}` | mixed | Direct column filter | `?status=active` |
| `filters[{column}]` | mixed | Advanced column filter | `?filters[status]=active` |
| `filters[{column}][from]` | date | Date range from | `?filters[created_at][from]=2024-01-01` |
| `filters[{column}][to]` | date | Date range to | `?filters[created_at][to]=2024-12-31` |
| `filters[{column}][min]` | numeric | Numeric range minimum | `?filters[price][min]=100` |
| `filters[{column}][max]` | numeric | Numeric range maximum | `?filters[price][max]=500` |

### Export Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `export` | string | Export format (csv/json) | `?export=csv` |
| `export_columns` | string | Comma-separated columns to export | `?export=csv&export_columns=name,email` |

### Search Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search_columns` | string | Comma-separated columns to search | `?search=john&search_columns=name,email` |
| `search_strategy` | string | Search strategy to use | `?search=john&search_strategy=fulltext` |
| `search_suggestions` | string | Get search suggestions | `?search_suggestions=john&limit=5` |

## 🔧 Configuration Examples

### Complete Service Configuration

```php
<?php

namespace App\Services;

use SgFlores\Cruder\BaseCrudService;
use App\Models\User;

class UserService extends BaseCrudService
{
    // Core configuration
    protected $model = User::class;
    
    // Caching configuration
    protected const QUERY_CACHE_ENABLED = true;
    protected const CACHE_LIFETIME_SECONDS = 3600;
    protected const CACHE_TAGS = ['users', 'test'];
    
    // Soft delete configuration
    protected const INCLUDE_SOFT_DELETED = false;
    protected const ONLY_SOFT_DELETED = false;
    
    // API resources configuration
    protected const ENABLE_API_RESOURCES = false;
    protected const API_RESOURCE_CLASS = null;
    
    // Chunked processing configuration
    protected const ENABLE_CHUNKED_PROCESSING = false;
    protected const CHUNK_SIZE = 1000;
    
    // Field selection
    protected const SELECT_COLUMNS = [];
    protected const EXCLUDE_COLUMNS = ['password', 'remember_token'];
    
    // Validation rules
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
    
    // Column configuration
    protected const DIRECT_FILTERABLE_COLUMNS = ['department_id', 'status'];
    protected const RELATION_FILTERABLE_COLUMNS = ['department.name'];
    
    protected const DIRECT_SORTABLE_COLUMNS = ['name', 'email', 'created_at'];
    protected const RELATION_SORTABLE_COLUMNS = ['department.name'];
    
    protected const DIRECT_TEXT_SEARCH_COLUMNS = ['name', 'email'];
    protected const RELATION_TEXT_SEARCH_COLUMNS = ['department.name'];
    
    // Search configuration
    protected const SEARCH_STRATEGY = 'like';
    
    // Pagination configuration
    protected const PAGINATE_PARAM = 'page';
    protected const LIMIT_PARAM = 'per_page';
    protected const DEFAULT_PAGE_SIZE = 15;
}
```

### Query Logging Configuration

```php
// config/cruder.php
return [
    'query_logging' => [
        'enabled' => env('CRUDER_QUERY_LOGGING_ENABLED', false),
        'log_all_operations' => env('CRUDER_LOG_ALL_OPERATIONS', true),
        'operations' => [
            'find' => env('CRUDER_LOG_FIND_OPERATIONS', true),
            'create' => env('CRUDER_LOG_CREATE_OPERATIONS', true),
            'update' => env('CRUDER_LOG_UPDATE_OPERATIONS', true),
            'delete' => env('CRUDER_LOG_DELETE_OPERATIONS', true),
            'count' => env('CRUDER_LOG_COUNT_OPERATIONS', true),
            'bulkCreate' => env('CRUDER_LOG_BULK_CREATE_OPERATIONS', true),
            'bulkUpdate' => env('CRUDER_LOG_BULK_UPDATE_OPERATIONS', true),
            'bulkDelete' => env('CRUDER_LOG_BULK_DELETE_OPERATIONS', true),
            'export' => env('CRUDER_LOG_EXPORT_OPERATIONS', true),
            'searchSuggestions' => env('CRUDER_LOG_SEARCH_SUGGESTIONS_OPERATIONS', true),
        ],
        'log_level' => env('CRUDER_QUERY_LOG_LEVEL', 'debug'),
        'include_bindings' => env('CRUDER_INCLUDE_BINDINGS', true),
        'include_execution_time' => env('CRUDER_INCLUDE_EXECUTION_TIME', true),
        'log_slow_queries_only' => env('CRUDER_LOG_SLOW_QUERIES_ONLY', false),
        'slow_query_threshold' => env('CRUDER_SLOW_QUERY_THRESHOLD', 1000),
        'channels' => [
            'default' => env('CRUDER_QUERY_LOG_CHANNEL', 'single'),
            'slow_queries' => env('CRUDER_SLOW_QUERY_LOG_CHANNEL', 'single'),
        ],
    ],
    'performance' => [
        'slow_query_log_level' => env('CRUDER_PERFORMANCE_SLOW_QUERY_LOG_LEVEL', 'warning'),
    ],
];
```

---

## 📚 Related Documentation

- **[README.md](README.md)** - Package overview, installation, and quick start guide
- **[TECHNICAL_README.md](TECHNICAL_README.md)** - Architecture, patterns, and implementation details
- **[QUERY_LOGGING.md](QUERY_LOGGING.md)** - Comprehensive query logging documentation

This comprehensive configuration reference provides all the options available in the Cruder package.
