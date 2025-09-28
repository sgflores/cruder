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
| `RELATED_FILTERABLE_COLUMNS` | array | `[]` | Related model columns for filtering | `['department.name', 'profile.age']` |


### Sorting Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `DIRECT_SORTABLE_COLUMNS` | array | `[]` | Columns that can be sorted directly | `['name', 'created_at']` |
| `RELATED_SORTABLE_COLUMNS` | array | `[]` | Related model columns for sorting | `['department.name', 'profile.created_at']` |

### Search Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `DIRECT_TEXT_SEARCH_COLUMNS` | array | `[]` | Columns for direct text search | `['name', 'email']` |
| `RELATED_TEXT_SEARCH_COLUMNS` | array | `[]` | Related model columns for text search | `['department.name', 'profile.bio']` |
| `ENABLE_FULLTEXT_SEARCH` | bool | `false` | Enable full-text search | `true` |
| `FULLTEXT_SEARCH_COLUMNS` | array | `[]` | Columns for full-text search | `['title', 'content']` |

### Pagination Configuration

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `PAGINATE_PARAM` | string | `'page'` | URL parameter for pagination | `'page'` |
| `LIMIT_PARAM` | string | `'limit'` | URL parameter for limit | `'limit'` |

## ✅ Validation Configuration

### Validation Rules

| Constant | Type | Default | Description | Example |
|----------|------|---------|-------------|---------|
| `CREATE_VALIDATION_RULES` | array | `[]` | Validation rules for create operations | `['name' => 'required\|string']` |
| `UPDATE_VALIDATION_RULES` | array | `[]` | Validation rules for update operations | `['name' => 'sometimes\|string']` |


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

### Hook Methods

| Method | Description | Example |
|--------|-------------|---------|
| `addHook()` | Add a hook for an operation | `$service->addHook('before_create', $hook)` |
| `executeHooks()` | Execute hooks for an operation | `$service->executeHooks('before_create', $data)` |
| `getAvailableOperations()` | Get all registered operations | `$service->getAvailableOperations()` |
| `getHooksForOperation()` | Get hooks for specific operation | `$service->getHooksForOperation('before_create')` |
| `hasHooks()` | Check if operation has hooks | `$service->hasHooks('before_create')` |

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
| `limit` | int | Number of records per page | `?limit=20` |
| `sort_by` | string | Column to sort by | `?sort_by=name` |
| `sort_direction` | string | Sort order (asc/desc) | `?sort_direction=desc` |
| `search` | string | Search term | `?search=john` |

### Filtering Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `{column}` | mixed | Direct column filter | `?status=active` |
| `{column}_gte` | numeric | Greater than or equal | `?age_gte=25` |
| `{column}_gt` | numeric | Greater than | `?salary_gt=50000` |
| `{column}_lte` | numeric | Less than or equal | `?age_lte=65` |
| `{column}_lt` | numeric | Less than | `?price_lt=1000` |
| `{column}_from` | date | Date range from | `?created_at_from=2024-01-01` |
| `{column}_to` | date | Date range to | `?created_at_to=2024-12-31` |
| `{column}_min` | numeric | Numeric range minimum | `?price_min=100` |
| `{column}_max` | numeric | Numeric range maximum | `?price_max=500` |

### Export Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `export` | string | Export format (csv/json) | `?export=csv` |

### Search Parameters

| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| `search_suggestions` | string | Get search suggestions | `?search_suggestions=john&limit=5` |


---

## 📚 Related Documentation

- **[README.md](README.md)** - Package overview, installation, and quick start guide
- **[TECHNICAL_README.md](TECHNICAL_README.md)** - Architecture, patterns, and implementation details
- **[QUERY_LOGGING.md](QUERY_LOGGING.md)** - Comprehensive query logging documentation

This comprehensive configuration reference provides all the options available in the Cruder package.
