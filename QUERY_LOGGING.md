# Query Logging Configuration

The Cruder package includes comprehensive query logging capabilities that can be configured to log all database queries performed by CRUD operations.

## Installation

1. Publish the configuration file:
```bash
php artisan vendor:publish --tag=cruder-config
```

2. Configure the settings in `config/cruder.php`

## Configuration

### Basic Settings

```php
'query_logging' => [
    'enabled' => env('CRUDER_QUERY_LOGGING_ENABLED', false),
    'log_all_operations' => env('CRUDER_LOG_ALL_OPERATIONS', true),
    'log_level' => env('CRUDER_QUERY_LOG_LEVEL', 'debug'),
    'include_bindings' => env('CRUDER_INCLUDE_QUERY_BINDINGS', true),
    'include_execution_time' => env('CRUDER_INCLUDE_EXECUTION_TIME', true),
],
```

### Operation-Specific Logging

You can control which operations are logged:

```php
'operations' => [
    'find' => env('CRUDER_LOG_FIND_OPERATIONS', true),
    'create' => env('CRUDER_LOG_CREATE_OPERATIONS', true),
    'update' => env('CRUDER_LOG_UPDATE_OPERATIONS', true),
    'delete' => env('CRUDER_LOG_DELETE_OPERATIONS', true),
    'count' => env('CRUDER_LOG_COUNT_OPERATIONS', true),
    'bulk_create' => env('CRUDER_LOG_BULK_CREATE_OPERATIONS', true),
    'bulk_update' => env('CRUDER_LOG_BULK_UPDATE_OPERATIONS', true),
    'bulk_delete' => env('CRUDER_LOG_BULK_DELETE_OPERATIONS', true),
],
```

### Slow Query Detection

```php
'slow_query_threshold' => env('CRUDER_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
'log_slow_queries_only' => env('CRUDER_LOG_SLOW_QUERIES_ONLY', false),
```

### Log Channels

```php
'channels' => [
    'default' => env('CRUDER_QUERY_LOG_CHANNEL', 'single'),
    'slow_queries' => env('CRUDER_SLOW_QUERY_LOG_CHANNEL', 'single'),
],
```

## Environment Variables

Add these to your `.env` file:

```env
# Enable/disable query logging
CRUDER_QUERY_LOGGING_ENABLED=true

# Log all operations or specific ones
CRUDER_LOG_ALL_OPERATIONS=true
CRUDER_LOG_FIND_OPERATIONS=true
CRUDER_LOG_CREATE_OPERATIONS=true
CRUDER_LOG_UPDATE_OPERATIONS=true
CRUDER_LOG_DELETE_OPERATIONS=true

# Log level and details
CRUDER_QUERY_LOG_LEVEL=debug
CRUDER_INCLUDE_QUERY_BINDINGS=true
CRUDER_INCLUDE_EXECUTION_TIME=true

# Slow query detection
CRUDER_SLOW_QUERY_THRESHOLD=1000
CRUDER_LOG_SLOW_QUERIES_ONLY=false

# Log channels
CRUDER_QUERY_LOG_CHANNEL=single
CRUDER_SLOW_QUERY_LOG_CHANNEL=single
```

## Usage

### Automatic Logging

Once enabled, all CRUD operations will automatically log their queries:

```php
$userService = new UserService();

// This will log the query if enabled
$users = $userService->findAll();

// This will also log the query
$user = $userService->create(['name' => 'John Doe']);
```

### Manual Query Logging

You can also access the query logger directly:

```php
$userService = new UserService();
$queryLogger = $userService->getQueryLogger();

// Log a custom query
$queryLogger->logQuery('custom', $query, $executionTime, ['context' => 'data']);
```

## Log Output

The query logs include the following information:

```json
{
    "operation": "find",
    "sql": "select * from `users` where `deleted_at` is null",
    "bindings": [],
    "table": "users",
    "execution_time_ms": 15.5,
    "context": {
        "filters": [],
        "is_paginated": false,
        "result_count": 10
    },
    "slow_query": false
}
```

## Performance Considerations

- Query logging adds minimal overhead to your application
- Consider using `log_slow_queries_only` in production
- Use appropriate log channels to separate query logs from application logs
- Monitor log file sizes when logging all operations

## Troubleshooting

### Logs Not Appearing

1. Check that `CRUDER_QUERY_LOGGING_ENABLED=true` in your `.env`
2. Verify the log channel exists in `config/logging.php`
3. Check file permissions for the log directory

### Too Many Logs

1. Use `CRUDER_LOG_SLOW_QUERIES_ONLY=true` to log only slow queries
2. Disable specific operations you don't need to log
3. Use different log channels for different environments

### Performance Impact

1. Increase `CRUDER_SLOW_QUERY_THRESHOLD` to reduce log volume
2. Use `CRUDER_LOG_ALL_OPERATIONS=false` and enable only specific operations
3. Consider using a different log driver for query logs

---

## 📚 Related Documentation

- **[README.md](README.md)** - Package overview, installation, and quick start guide
- **[TECHNICAL_README.md](TECHNICAL_README.md)** - Architecture, patterns, and implementation details
- **[OPTIONS_README.md](OPTIONS_README.md)** - Complete configuration options reference

**Made with ❤️ for the Laravel community**
