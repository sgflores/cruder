<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Query Logging Configuration
    |--------------------------------------------------------------------------
    |
    | This section controls query logging behavior for all CRUD operations.
    | You can enable/disable logging globally or per operation type.
    |
    */
    'query_logging' => [
        'enabled' => env('CRUDER_QUERY_LOGGING_ENABLED', false),
        
        'log_all_operations' => env('CRUDER_LOG_ALL_OPERATIONS', true),
        
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
        
        'log_level' => env('CRUDER_QUERY_LOG_LEVEL', 'debug'), // debug, info, warning, error
        
        'include_bindings' => env('CRUDER_INCLUDE_QUERY_BINDINGS', true),
        
        'include_execution_time' => env('CRUDER_INCLUDE_EXECUTION_TIME', true),
        
        'slow_query_threshold' => env('CRUDER_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
        
        'log_slow_queries_only' => env('CRUDER_LOG_SLOW_QUERIES_ONLY', false),
        
        'channels' => [
            'default' => env('CRUDER_QUERY_LOG_CHANNEL', 'single'),
            'slow_queries' => env('CRUDER_SLOW_QUERY_LOG_CHANNEL', 'single'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Monitoring
    |--------------------------------------------------------------------------
    |
    | Configuration for performance monitoring and slow query detection.
    |
    */
    'performance' => [
        'monitor_slow_queries' => env('CRUDER_MONITOR_SLOW_QUERIES', true),
        'slow_query_threshold' => env('CRUDER_SLOW_QUERY_THRESHOLD', 1000),
        'log_slow_queries' => env('CRUDER_LOG_SLOW_QUERIES', true),
        'slow_query_log_level' => env('CRUDER_SLOW_QUERY_LOG_LEVEL', 'warning'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Default cache settings for CRUD operations.
    |
    */
    'cache' => [
        'default_enabled' => env('CRUDER_CACHE_ENABLED', false),
        'default_lifetime' => env('CRUDER_CACHE_LIFETIME', 3600), // seconds
        'use_tags' => env('CRUDER_CACHE_USE_TAGS', true),
        'fallback_to_flush' => env('CRUDER_CACHE_FALLBACK_TO_FLUSH', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Default validation settings for CRUD operations.
    |
    */
    'validation' => [
        'strict_column_validation' => env('CRUDER_STRICT_COLUMN_VALIDATION', true),
        'throw_on_invalid_columns' => env('CRUDER_THROW_ON_INVALID_COLUMNS', true),
    ],
];
