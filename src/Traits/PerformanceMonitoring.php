<?php

namespace SgFlores\Cruder\Traits;

/**
 * Performance Monitoring Trait
 * 
 * This trait provides performance monitoring capabilities for CRUD operations.
 * It consolidates timing and logging functionality to reduce code duplication.
 */
trait PerformanceMonitoring
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
        // Check if the class has the QUERY_CACHE_ENABLED constant
        $reflection = new \ReflectionClass($this);
        if ($reflection->hasConstant('QUERY_CACHE_ENABLED') && $reflection->getConstant('QUERY_CACHE_ENABLED')) {
            try {
                // Use granular cache tags for more precise invalidation
                \Illuminate\Support\Facades\Cache::tags($this->getCacheTags())->flush();
            } catch (\Exception $e) {
                // Fallback to clearing all cache if tags don't work (e.g., in testing)
                \Illuminate\Support\Facades\Cache::flush();
            }
        }
    }
}
