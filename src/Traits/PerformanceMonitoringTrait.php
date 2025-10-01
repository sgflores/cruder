<?php

namespace SgFlores\Cruder\Traits;

use Illuminate\Database\Eloquent\Builder;

/**
 * Performance Monitoring Trait
 * 
 * This trait provides performance monitoring capabilities for CRUD operations.
 * It consolidates timing, logging, and debugging functionality to reduce code duplication.
 */
trait PerformanceMonitoringTrait
{
    /**
     * Executes a callback with performance monitoring.
     * 
     * Measures execution time and logs performance data for debugging.
     * Used to track how long operations take to complete.
     * 
     * @param string $operation The operation being performed
     * @param callable $callback The callback to execute
     * @param array $context Additional context for logging
     * @return mixed The result of the callback
     */
    protected function executeWithTiming(string $operation, callable $callback, array $context = [])
    {
        // Record start time before execution
        $startTime = microtime(true);
        $result = $callback();
        // Calculate execution time in milliseconds
        $executionTime = (microtime(true) - $startTime) * 1000;
        
        // Log performance data if query logger is available
        if (isset($this->queryLogger)) {
            $this->queryLogger->logQuery($operation, $this->model->newQuery(), $executionTime, $context);
        }
        
        return $result;
    }
    
    /**
     * Executes a callback with performance monitoring and cache clearing.
     * 
     * Combines timing measurement with automatic cache invalidation.
     * Used for write operations that need to clear cached data.
     * 
     * @param string $operation The operation being performed
     * @param callable $callback The callback to execute
     * @param array $context Additional context for logging
     * @return mixed The result of the callback
     */
    protected function executeWithTimingAndCache(string $operation, callable $callback, array $context = [])
    {
        // Execute with timing and clear cache after completion
        return $this->executeWithTiming($operation, function () use ($callback) {
            $result = $callback();
            // Clear cache to ensure fresh data on next read
            $this->clearCache();
            return $result;
        }, $context);
    }
    
    /**
     * Clears the cache for this model's table.
     * 
     * @return void
     */
    protected function clearCache(): void
    {
        // Check if query cache is enabled using the configuration method
        if (method_exists($this, 'isQueryCacheEnabled') && $this->isQueryCacheEnabled()) {
            // Clear cache using simple cache operations
            \Illuminate\Support\Facades\Cache::flush();
        }
    }
    
    /**
     * Binds query parameters to the SQL string for debugging purposes.
     * 
     * Converts parameterized SQL queries into readable format by replacing
     * placeholders (?) with actual parameter values for logging/debugging.
     * 
     * @param Builder $query The Eloquent query builder instance
     * @return string The SQL query with parameter values bound
     */
    public function getQueryWithBindings(Builder $query): string
    {
        // Get the parameter bindings and raw SQL
        $bindings = $query->getBindings();
        $sql = $query->toSql();

        // Replace each ? placeholder with the actual parameter value
        return preg_replace_callback('/\?/', function ($match) use (&$bindings, $query) {
            $value = array_shift($bindings);
            // Properly quote the value for SQL display
            return $query->getConnection()->getPdo()->quote($value);
        }, $sql);
    }
}
