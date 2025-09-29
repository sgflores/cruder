<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\Services\QueryLogger;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

/**
 * Query Logger Example
 * 
 * Demonstrates how to use the QueryLogger for database query monitoring.
 * Shows different logging scenarios and configuration options.
 */
class QueryLoggerExample
{
    protected QueryLogger $queryLogger;
    protected Builder $query;
    
    public function __construct()
    {
        $this->queryLogger = new QueryLogger();
        $this->query = User::query();
    }
    
    /**
     * Example: Log a simple find query.
     */
    public function logFindQuery(): void
    {
        $query = User::where('status', 'active');
        $executionTime = 15.5; // milliseconds
        $context = [
            'filters' => ['status' => 'active'],
            'result_count' => 25
        ];
        
        // Log the query
        $this->queryLogger->logQuery('find', $query, $executionTime, $context);
        
        echo "Find query logged successfully.\n";
    }
    
    /**
     * Example: Log a create query.
     */
    public function logCreateQuery(): void
    {
        $query = User::query();
        $executionTime = 8.2; // milliseconds
        $context = [
            'data' => ['name' => 'John Doe', 'email' => 'john@example.com'],
            'operation' => 'create'
        ];
        
        // Log the query
        $this->queryLogger->logQuery('create', $query, $executionTime, $context);
        
        echo "Create query logged successfully.\n";
    }
    
    /**
     * Example: Log an update query.
     */
    public function logUpdateQuery(): void
    {
        $query = User::where('id', 1);
        $executionTime = 12.8; // milliseconds
        $context = [
            'id' => 1,
            'data' => ['name' => 'Jane Doe'],
            'operation' => 'update'
        ];
        
        // Log the query
        $this->queryLogger->logQuery('update', $query, $executionTime, $context);
        
        echo "Update query logged successfully.\n";
    }
    
    /**
     * Example: Log a delete query.
     */
    public function logDeleteQuery(): void
    {
        $query = User::where('id', 1);
        $executionTime = 5.1; // milliseconds
        $context = [
            'id' => 1,
            'operation' => 'delete',
            'soft_delete' => true
        ];
        
        // Log the query
        $this->queryLogger->logQuery('delete', $query, $executionTime, $context);
        
        echo "Delete query logged successfully.\n";
    }
    
    /**
     * Example: Log a slow query.
     */
    public function logSlowQuery(): void
    {
        $query = User::with(['profile', 'orders', 'roles'])
                    ->where('created_at', '>=', now()->subDays(30))
                    ->orderBy('created_at', 'desc');
        $executionTime = 2500.0; // 2.5 seconds - slow query
        $context = [
            'filters' => ['date_range' => 'last_30_days'],
            'with_relations' => ['profile', 'orders', 'roles'],
            'result_count' => 150
        ];
        
        // Log the slow query
        $this->queryLogger->logQuery('find', $query, $executionTime, $context);
        
        echo "Slow query logged successfully.\n";
    }
    
    /**
     * Example: Log a bulk operation query.
     */
    public function logBulkOperationQuery(): void
    {
        $query = User::whereIn('status', ['inactive', 'suspended']);
        $executionTime = 45.3; // milliseconds
        $context = [
            'operation' => 'bulk_update',
            'filters' => ['status' => ['inactive', 'suspended']],
            'data' => ['last_login' => null],
            'affected_rows' => 15
        ];
        
        // Log the bulk operation
        $this->queryLogger->logQuery('bulk_update', $query, $executionTime, $context);
        
        echo "Bulk operation query logged successfully.\n";
    }
    
    /**
     * Example: Log a complex search query.
     */
    public function logSearchQuery(): void
    {
        $query = User::where('name', 'LIKE', '%john%')
                    ->orWhere('email', 'LIKE', '%john%')
                    ->where('status', 'active')
                    ->orderBy('name');
        $executionTime = 28.7; // milliseconds
        $context = [
            'search_term' => 'john',
            'search_columns' => ['name', 'email'],
            'filters' => ['status' => 'active'],
            'result_count' => 8
        ];
        
        // Log the search query
        $this->queryLogger->logQuery('search', $query, $executionTime, $context);
        
        echo "Search query logged successfully.\n";
    }
    
    /**
     * Example: Log a count query.
     */
    public function logCountQuery(): void
    {
        $query = User::where('status', 'active')
                    ->where('created_at', '>=', now()->subMonth());
        $executionTime = 3.2; // milliseconds
        $context = [
            'filters' => [
                'status' => 'active',
                'created_at' => 'last_month'
            ],
            'count' => 42
        ];
        
        // Log the count query
        $this->queryLogger->logQuery('count', $query, $executionTime, $context);
        
        echo "Count query logged successfully.\n";
    }
    
    /**
     * Example: Log all queries from query log.
     */
    public function logAllQueriesFromLog(): void
    {
        $context = [
            'operation' => 'batch_processing',
            'batch_id' => 'batch_123',
            'total_queries' => 5
        ];
        
        // Log all queries from Laravel's query log
        $this->queryLogger->logAllQueries('batch_processing', $context);
        
        echo "All queries from query log logged successfully.\n";
    }
    
    /**
     * Example: Simulate different query scenarios.
     */
    public function simulateQueryScenarios(): void
    {
        echo "=== Query Logger Examples ===\n\n";
        
        // Log different types of queries
        $this->logFindQuery();
        $this->logCreateQuery();
        $this->logUpdateQuery();
        $this->logDeleteQuery();
        $this->logSlowQuery();
        $this->logBulkOperationQuery();
        $this->logSearchQuery();
        $this->logCountQuery();
        $this->logAllQueriesFromLog();
        
        echo "\n=== All query scenarios completed ===\n";
    }
    
    /**
     * Example: Demonstrate query logging with different configurations.
     */
    public function demonstrateConfigurationScenarios(): void
    {
        echo "=== Configuration Scenarios ===\n\n";
        
        // Scenario 1: Log only slow queries
        echo "1. Logging only slow queries (threshold: 1000ms)\n";
        // This would be controlled by config('cruder.query_logging.log_slow_queries_only', true)
        
        // Scenario 2: Log specific operations only
        echo "2. Logging specific operations only (create, update, delete)\n";
        // This would be controlled by config('cruder.query_logging.operations')
        
        // Scenario 3: Include query bindings
        echo "3. Including query bindings in logs\n";
        // This would be controlled by config('cruder.query_logging.include_bindings', true)
        
        // Scenario 4: Include execution time
        echo "4. Including execution time in logs\n";
        // This would be controlled by config('cruder.query_logging.include_execution_time', true)
        
        // Scenario 5: Use different log channels
        echo "5. Using different log channels for slow queries\n";
        // This would be controlled by config('cruder.query_logging.channels')
        
        echo "\n=== Configuration scenarios demonstrated ===\n";
    }
}
