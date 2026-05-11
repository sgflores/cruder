<?php

namespace SgFlores\Cruder\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SgFlores\Cruder\Traits\PerformanceMonitoringTrait;

class QueryLogger
{
    use PerformanceMonitoringTrait;

    /**
     * Log a query for a specific operation.
     *
     * Logs database queries based on configuration settings.
     * Can filter by operation type, execution time, and other criteria.
     *
     * @param  string  $operation  The operation type (find, create, update, delete, etc.)
     * @param  Builder  $query  The query builder instance
     * @param  float  $executionTime  The execution time in milliseconds
     * @param  array  $context  Additional context data
     */
    public function logQuery(string $operation, Builder $query, float $executionTime = 0, array $context = []): void
    {
        // Skip if query logging is disabled globally
        if (! $this->isQueryLoggingEnabled()) {
            return;
        }

        // Skip if this specific operation shouldn't be logged
        if (! $this->shouldLogOperation($operation)) {
            return;
        }

        // Skip if only slow queries should be logged and this isn't slow
        if ($this->shouldOnlyLogSlowQueries() && ! $this->isSlowQuery($executionTime)) {
            return;
        }

        // Build the log data structure
        $logData = $this->prepareLogData($operation, $query, $executionTime, $context);

        // Determine appropriate log level (debug, warning, etc.)
        $logLevel = $this->getLogLevel($executionTime);

        // Write the log entry
        $this->writeLog($logLevel, $logData);
    }

    /**
     * Log all queries from the query log.
     *
     * @param  string  $operation  The operation type
     * @param  array  $context  Additional context data
     */
    public function logAllQueries(string $operation, array $context = []): void
    {
        if (! $this->isQueryLoggingEnabled() || ! $this->shouldLogOperation($operation)) {
            return;
        }

        $queries = DB::getQueryLog();

        foreach ($queries as $queryData) {
            $this->logQueryData($operation, $queryData, $context);
        }
    }

    /**
     * Check if query logging is enabled.
     */
    protected function isQueryLoggingEnabled(): bool
    {
        return config('cruder.query_logging.enabled', false);
    }

    /**
     * Check if a specific operation should be logged.
     *
     * @param  string  $operation  The operation type
     */
    protected function shouldLogOperation(string $operation): bool
    {
        if (config('cruder.query_logging.log_all_operations', true)) {
            return true;
        }

        return config("cruder.query_logging.operations.{$operation}", false);
    }

    /**
     * Check if we should only log slow queries.
     */
    protected function shouldOnlyLogSlowQueries(): bool
    {
        return config('cruder.query_logging.log_slow_queries_only', false);
    }

    /**
     * Check if a query is considered slow.
     *
     * @param  float  $executionTime  The execution time in milliseconds
     */
    protected function isSlowQuery(float $executionTime): bool
    {
        $threshold = config('cruder.query_logging.slow_query_threshold', 1000);

        return $executionTime >= $threshold;
    }

    /**
     * Get the appropriate log level based on execution time.
     *
     * @param  float  $executionTime  The execution time in milliseconds
     */
    protected function getLogLevel(float $executionTime): string
    {
        if ($this->isSlowQuery($executionTime)) {
            return config('cruder.performance.slow_query_log_level', 'warning');
        }

        return config('cruder.query_logging.log_level', 'debug');
    }

    /**
     * Prepare log data for a query.
     *
     * @param  string  $operation  The operation type
     * @param  Builder  $query  The query builder instance
     * @param  float  $executionTime  The execution time in milliseconds
     * @param  array  $context  Additional context data
     */
    protected function prepareLogData(string $operation, Builder $query, float $executionTime, array $context): array
    {
        $logData = [
            'operation' => $operation,
            'sql' => $this->getQueryWithBindings($query),
            'table' => $query->getModel()->getTable(),
        ];

        // Add bindings if configured
        if (config('cruder.query_logging.include_bindings', true)) {
            $logData['bindings'] = $query->getBindings();
        }

        // Add execution time if configured
        if (config('cruder.query_logging.include_execution_time', true)) {
            $logData['execution_time_ms'] = $executionTime;
        }

        // Add context data
        if (! empty($context)) {
            $logData['context'] = $context;
        }

        // Add slow query information if applicable
        if ($this->isSlowQuery($executionTime)) {
            $logData['slow_query'] = true;
            $logData['threshold_ms'] = config('cruder.query_logging.slow_query_threshold', 1000);
        }

        $logData['raw_query'] = $this->bindQueryStrings($query);

        return $logData;
    }

    /**
     * Log query data from the query log.
     *
     * @param  string  $operation  The operation type
     * @param  array  $queryData  The query data from DB::getQueryLog()
     * @param  array  $context  Additional context data
     */
    protected function logQueryData(string $operation, array $queryData, array $context = []): void
    {
        $logData = [
            'operation' => $operation,
            'sql' => $queryData['query'],
            'bindings' => $queryData['bindings'],
            'time' => $queryData['time'],
        ];

        if (! empty($context)) {
            $logData['context'] = $context;
        }

        $logLevel = config('cruder.query_logging.log_level', 'debug');
        $this->writeLog($logLevel, $logData);
    }

    /**
     * Write the log entry.
     *
     * @param  string  $level  The log level
     * @param  array  $data  The log data
     */
    protected function writeLog(string $level, array $data): void
    {
        $channel = $this->isSlowQuery($data['execution_time_ms'] ?? 0)
            ? config('cruder.query_logging.channels.slow_queries', 'single')
            : config('cruder.query_logging.channels.default', 'single');

        // Combine message and data into a single structured log entry
        $logData = array_merge(['message' => 'Query'], $data);
        Log::channel($channel)->{$level}($logData);
    }

    /**
     * Parse eloquent query builder to string with bindings
     *
     * @param  Builder|\Illuminate\Database\Query\Builder  $query
     */
    protected function bindQueryStrings($query): string
    {
        $bindings = $query->getBindings();

        return preg_replace_callback('/\?/', function ($match) use (&$bindings, $query) {
            return $query->getConnection()->getPdo()->quote(array_shift($bindings));
        }, $query->toSql());

        // $addSlashes = str_replace('?', "'?'", $query->toSql());
        // return vsprintf(str_replace('?', '%s', $addSlashes), $query->getBindings());
    }
}
