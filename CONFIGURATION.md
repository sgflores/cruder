# CRUDer Configuration Guide

This document provides a comprehensive guide to all available configuration options, constants, and settings for the CRUDer package.

## 📋 Table of Contents

- [BaseReaderService Constants](#basereaderservice-constants)
- [BaseCrudService Constants](#basecrudservice-constants)
- [Configuration File Options](#configuration-file-options)
- [Usage Examples](#usage-examples)

---

## BaseReaderService Constants

### 🔍 Search & Filter Constants

| Constant | Type | Default | Purpose | Description | URL Example |
|----------|------|---------|---------|-------------|-------------|
| `SEARCH_PARAM` | string | `'search'` | Search parameter name | The query parameter name used for text search | `?search=john` |
| `SORT_BY_PARAM` | string | `'sort_by'` | Sort parameter name | The query parameter name for specifying sort column | `?sort_by=name` |
| `SORT_DIRECTION_PARAM` | string | `'sort_direction'` | Sort direction parameter | The query parameter name for sort direction (asc/desc) | `?sort_direction=desc` |
| `PAGINATE_PARAM` | string | `'page'` | Pagination parameter | The query parameter name for pagination | `?page=2` |
| `LIMIT_PARAM` | string | `'limit'` | Limit parameter | The query parameter name for result limit | `?limit=50` |

#### 📝 URL Parameter Examples

Here are some practical examples of how these parameters work together:

**Basic Search:**
```
GET /api/users?search=john
```

**Search with Sorting:**
```
GET /api/users?search=john&sort_by=name&sort_direction=asc
```

**Search with Pagination:**
```
GET /api/users?search=john&page=2&limit=20
```

**Complete Query Example:**
```
GET /api/users?search=john&status=active&sort_by=created_at&sort_direction=desc&page=1&limit=10
```

**Filtering by Column Values:**
```
GET /api/users?status=active&department_id=5
```

**Related Model Filtering:**
```
GET /api/products?category_name=Electronics&brand_name=Samsung
```

### 🔗 Relationship Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `COLLECTION_RELATIONS` | array | `[]` | Collection relations | Relations to eager load for collection queries (findAll) |
| `SINGLE_RECORD_RELATIONS` | array | `[]` | Single record relations | Relations to eager load for single record queries (findById) |
| `MAGIC_COUNT` | string | `'_is_count_'` | Count magic key | Special key used for count operations |

### 🏷️ Column Configuration Constants

#### Direct Model Columns

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `DIRECT_FILTERABLE_COLUMNS` | array | `[]` | Filterable columns | Columns that can be used for exact filtering |
| `DIRECT_TEXT_SEARCH_COLUMNS` | array | `[]` | Searchable columns | Columns that can be searched using text search |
| `DIRECT_SORTABLE_COLUMNS` | array | `[]` | Sortable columns | Columns that can be used for sorting |

#### Related Model Columns

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `RELATED_FILTERABLE_COLUMNS` | array | `[]` | Related filterable columns | Related model columns for filtering (e.g., 'user_name') |
| `RELATED_TEXT_SEARCH_COLUMNS` | array | `[]` | Related searchable columns | Related model columns for text search |
| `RELATED_SORTABLE_COLUMNS` | array | `[]` | Related sortable columns | Related model columns for sorting |

### 📊 Default Behavior Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `DEFAULT_SORT_COLUMN` | string | `'id'` | Default sort column | Column used for default sorting |
| `DEFAULT_SORT_DIRECTION` | string | `'asc'` | Default sort direction | Default sort direction (asc/desc) |

### ⚡ Performance Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `QUERY_CACHE_ENABLED` | bool | `false` | Query caching | Enable/disable query result caching |
| `CACHE_LIFETIME_SECONDS` | int | `3600` | Cache lifetime | Cache expiration time in seconds |
| `CACHE_TAGS` | array | `[]` | Cache tags | Tags for cache invalidation |
| `ENABLE_CHUNKED_PROCESSING` | bool | `false` | Chunked processing | Enable chunked processing for large datasets |
| `CHUNK_SIZE` | int | `1000` | Chunk size | Number of records per chunk |

### 🔧 Data Selection Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `SELECT_COLUMNS` | array | `[]` | Select columns | Specific columns to select (if empty, selects all) |
| `EXCLUDE_COLUMNS` | array | `[]` | Exclude columns | Columns to exclude from selection |
| `DATABASE_CONNECTION` | string|null | `null` | Database connection | Specific database connection to use |

### 🗑️ Soft Delete Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `INCLUDE_SOFT_DELETED` | bool | `false` | Include soft deleted | Include soft deleted records in queries |
| `ONLY_SOFT_DELETED` | bool | `false` | Only soft deleted | Only return soft deleted records |

### 🎯 API Resource Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `ENABLE_API_RESOURCES` | bool | `false` | API resources | Enable Laravel API resource transformation |
| `API_RESOURCE_CLASS` | string|null | `null` | API resource class | Custom API resource class to use |

---

## BaseCrudService Constants

### 📝 Audit Trail Constants

| Constant | Type | Default | Purpose | Description |
|----------|------|---------|---------|-------------|
| `AUDIT_TRAIL_ENABLED` | bool | `false` | Audit trail | Enable automatic audit trail tracking |
| `CREATOR_COLUMN` | string | `'created_by'` | Creator column | Column name for tracking record creator |
| `UPDATER_COLUMN` | string | `'updated_by'` | Updater column | Column name for tracking record updater |
| `DELETER_COLUMN` | string | `'deleted_by'` | Deleter column | Column name for tracking record deleter |

---

## Configuration File Options

The `config/cruder.php` file provides global configuration options for the package.

### 📊 Query Logging Configuration

| Option | Type | Default | Purpose | Description |
|--------|------|---------|---------|-------------|
| `query_logging.enabled` | bool | `false` | Enable logging | Global query logging toggle |
| `query_logging.log_all_operations` | bool | `true` | Log all operations | Log all CRUD operations by default |
| `query_logging.log_level` | string | `'debug'` | Log level | Logging level (debug, info, warning, error) |
| `query_logging.include_bindings` | bool | `true` | Include bindings | Include query bindings in logs |
| `query_logging.include_execution_time` | bool | `true` | Include execution time | Include query execution time in logs |
| `query_logging.slow_query_threshold` | int | `1000` | Slow query threshold | Threshold in milliseconds for slow queries |
| `query_logging.log_slow_queries_only` | bool | `false` | Log slow queries only | Only log queries that exceed threshold |

### 🔧 Operation-Specific Logging

| Operation | Type | Default | Purpose | Description |
|-----------|------|---------|---------|-------------|
| `query_logging.operations.find` | bool | `true` | Find operations | Log find/findAll operations |
| `query_logging.operations.create` | bool | `true` | Create operations | Log create operations |
| `query_logging.operations.update` | bool | `true` | Update operations | Log update operations |
| `query_logging.operations.delete` | bool | `true` | Delete operations | Log delete operations |
| `query_logging.operations.count` | bool | `true` | Count operations | Log count operations |
| `query_logging.operations.bulk_create` | bool | `true` | Bulk create | Log bulk create operations |
| `query_logging.operations.bulk_update` | bool | `true` | Bulk update | Log bulk update operations |
| `query_logging.operations.bulk_delete` | bool | `true` | Bulk delete | Log bulk delete operations |

### 📈 Performance Configuration

| Option | Type | Default | Purpose | Description |
|--------|------|---------|---------|-------------|
| `performance.slow_query_log_level` | string | `'warning'` | Slow query log level | Log level for slow queries |

---

## Usage Examples

For comprehensive configuration examples and real-world usage patterns, see the example files:

### 📖 Configuration Examples
- **[SimpleSalesExample.php](Examples/SimpleSalesExample.php)** - Basic service configuration with common constants
- **[SalesInventoryExample.php](Examples/SalesInventoryExample.php)** - Advanced configuration with relations and performance settings
- **[ProductExample.php](Examples/ProductExample.php)** - Validation strategies and custom configurations
- **[ExportExample.php](Examples/ExportExample.php)** - Export functionality configuration
- **[ReportExample.php](Examples/ReportExample.php)** - Custom search strategies configuration

### 🔧 Configuration File Examples
See the main [README.md](README.md) for configuration file setup and environment variable examples.

---

## 🔧 Best Practices

### Column Security
- Always define `DIRECT_FILTERABLE_COLUMNS`, `DIRECT_SORTABLE_COLUMNS`, and `DIRECT_TEXT_SEARCH_COLUMNS` for security
- Use descriptive names for related columns (e.g., `'user_name'`, `'department_title'`)

### Performance Optimization
- Enable caching for frequently accessed data
- Use chunked processing for large datasets
- Set appropriate cache lifetimes based on data volatility

### Audit Trail
- Enable audit trail for important models
- Ensure your database has the required audit columns (`created_by`, `updated_by`, `deleted_by`)

### Query Logging
- Enable query logging in development
- Set appropriate slow query thresholds
- Use different log levels for different environments

---

## 📚 Related Documentation

- [Main README](README.md) - Package overview and quick start
- [Architecture Guide](ARCHITECTURE.md) - Package architecture and design patterns
- [Examples](Examples/) - Real-world usage examples
