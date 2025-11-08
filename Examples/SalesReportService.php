<?php

namespace SgFlores\Cruder\Examples;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\QueryLogger;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Strategies\Export\CsvExportStrategy;
use SgFlores\Cruder\Strategies\Export\JsonExportStrategy;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;

/**
 * Sales Report Service Example
 *
 * This example demonstrates how to create a custom service extending BaseReaderService
 * with custom search strategies for sales reporting.
 *
 * Features:
 * - Custom TopOrdersStrategy for finding top performing orders
 * - Custom TopSalesStrategy for finding top sales by various criteria
 * - Proper dependency injection with concrete services
 * - Event-driven reporting with EventService
 * - Export capabilities for reports
 */
class SalesReportService extends BaseReaderService
{
    /**
     * SalesReportService constructor.
     *
     * @param  Model  $model  The Eloquent model (Order, Product, etc.)
     * @param  SearchService  $searchService  Search service with custom strategies
     * @param  ExportService  $exportService  Export service for report generation
     * @param  EventService  $eventService  Event service for reporting events
     * @param  QueryLogger  $queryLogger  Query logger for performance monitoring
     */
    public function __construct(
        Model $model,
        SearchService $searchService,
        ExportService $exportService,
        EventService $eventService,
        QueryLogger $queryLogger
    ) {
        parent::__construct($model, $searchService, $exportService, $eventService, $queryLogger);

        // Configure services after construction
        $this->configureServices();
    }

    /**
     * Configure custom search strategies for sales reporting.
     */
    protected function configureServices(): void
    {
        // Register custom search strategies
        $this->searchService->addStrategy(TopOrdersStrategy::key(), new TopOrdersStrategy);
        $this->searchService->addStrategy(TopSalesStrategy::key(), new TopSalesStrategy);

        // Configure export strategies using the new key() method
        $this->exportService->addStrategy(CsvExportStrategy::key(), new CsvExportStrategy);
        $this->exportService->addStrategy(JsonExportStrategy::key(), new JsonExportStrategy);
    }

    /**
     * Get top orders based on various criteria.
     *
     * @param  array  $options  Additional options for the query
     */
    public function getTopOrders(array $options = []): \Illuminate\Support\Collection
    {
        $result = $this->findAll([
            'strategies' => TopOrdersStrategy::key(),
            ...$options,
        ]);

        return $result;
    }

    /**
     * Get top sales based on various criteria.
     *
     * @param  array  $options  Additional options for the query
     */
    public function getTopSales(array $options = []): \Illuminate\Support\Collection
    {
        $result = $this->findAll([
            'strategies' => TopSalesStrategy::key(),
            ...$options,
        ]);

        return $result;
    }

    /**
     * Export sales report in specified format.
     *
     * @param  string  $format  Export format (csv, json)
     * @param  array  $options  Query options
     */
    public function exportSalesReport(string $format, array $options = []): mixed
    {
        return $this->export($format, $options);
    }

    // ========================================================================
    // --- Override Configuration Methods ---
    // ========================================================================

    /**
     * Define filterable columns for sales reports.
     */
    public function getDirectFilterableColumns(): array
    {
        return [
            'id', 'customer_id', 'order_number', 'status', 'total_amount',
            'created_at', 'updated_at', 'deleted_at',
        ];
    }

    /**
     * Define sortable columns for sales reports.
     */
    public function getDirectSortableColumns(): array
    {
        return [
            'id', 'order_number', 'total_amount', 'created_at', 'updated_at',
        ];
    }

    /**
     * Define text search columns for sales reports.
     */
    public function getDirectTextSearchColumns(): array
    {
        return [
            'order_number', 'notes', 'customer_notes',
        ];
    }

    /**
     * Define relations to load for collections.
     */
    public function getCollectionRelations(): array
    {
        return [
            'customer', 'items',
        ];
    }

    /**
     * Define relations to load for single records.
     */
    public function getSingleRecordRelations(): array
    {
        return [
            'customer', 'items', 'createdBy', 'updatedBy',
        ];
    }

    /**
     * Get search parameter name.
     */
    public function getSearchParam(): string
    {
        return 'search';
    }

    /**
     * Get strategies parameter name.
     */
    public function getStrategiesParam(): string
    {
        return 'strategies';
    }

    /**
     * Get sort by parameter name.
     */
    public function getSortByParam(): string
    {
        return 'sort_by';
    }

    /**
     * Get sort direction parameter name.
     */
    public function getSortDirectionParam(): string
    {
        return 'sort_direction';
    }

    /**
     * Get default sort column.
     */
    public function getDefaultSortColumn(): string
    {
        return 'total_amount';
    }

    /**
     * Get default sort direction.
     */
    public function getDefaultSortDirection(): string
    {
        return 'desc';
    }

    /**
     * Enable query cache for better performance.
     */
    public function isQueryCacheEnabled(): bool
    {
        return true;
    }

    /**
     * Get cache lifetime for sales reports.
     */
    public function getCacheLifetimeSeconds(): int
    {
        return 300; // 5 minutes cache for reports
    }
}

/**
 * Top Orders Search Strategy
 *
 * This strategy finds the top performing orders based on total amount
 * and applies additional business logic for sales reporting.
 */
class TopOrdersStrategy implements SearchStrategyInterface
{
    /**
     * Get the strategy key.
     */
    public static function key(): string
    {
        return 'top_orders';
    }

    /**
     * Apply the top orders search strategy.
     *
     * @param  Builder|QueryBuilder|null  $query  The query builder instance (optional)
     * @param  array  $filters  Applied filters
     * @param  array  $config  Strategy configuration
     * @return Builder|QueryBuilder The modified query builder
     */
    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        // Apply base filters
        $query = $this->applyBaseFilters($query, $filters);

        // Order by total amount descending (top orders)
        $query->orderBy('total_amount', 'desc');

        // Apply date range if provided
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply minimum amount filter
        if (isset($filters['min_amount'])) {
            $query->where('total_amount', '>=', $filters['min_amount']);
        }

        // Apply status filter
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Limit results if specified
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        } else {
            $query->limit(100); // Default limit for top orders
        }

        return $query;
    }

    /**
     * Apply base filters to the query.
     */
    private function applyBaseFilters(Builder $query, array $filters): Builder
    {
        // Only show completed orders by default
        if (! isset($filters['status'])) {
            $query->whereIn('status', ['completed', 'shipped', 'delivered']);
        }

        // Exclude cancelled orders unless specifically requested
        if (! isset($filters['include_cancelled'])) {
            $query->where('status', '!=', 'cancelled');
        }

        return $query;
    }
}

/**
 * Top Sales Search Strategy
 *
 * This strategy finds the top sales based on various criteria
 * including customer, product, and time-based analysis.
 */
class TopSalesStrategy implements SearchStrategyInterface
{
    /**
     * Get the strategy key.
     */
    public static function key(): string
    {
        return 'top_sales';
    }

    /**
     * Applies search logic to the query builder.
     *
     * @param  Builder|QueryBuilder|null  $query  The query builder instance (optional)
     * @param  array  $filters  Array of query options
     * @param  array  $config  Optional search configuration
     * @return Builder|QueryBuilder The modified query builder
     */
    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        // Apply base filters
        $query = $this->applyBaseFilters($query, $filters);

        // Apply grouping and aggregation based on criteria
        if (isset($filters['group_by'])) {
            $query = $this->applyGrouping($query, $filters['group_by']);
        }

        // Order by total amount descending
        $query->orderBy('total_amount', 'desc');

        // Apply date range filters
        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply customer filter
        if (isset($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Apply product filter (if order has items relationship)
        if (isset($filters['product_id'])) {
            $query->whereHas('items', function ($q) use ($filters) {
                $q->where('product_id', $filters['product_id']);
            });
        }

        // Limit results
        if (isset($filters['limit'])) {
            $query->limit($filters['limit']);
        } else {
            $query->limit(50); // Default limit for top sales
        }

        return $query;
    }

    /**
     * Apply base filters to the query.
     */
    private function applyBaseFilters(Builder $query, array $filters): Builder
    {
        // Only show completed sales by default
        if (! isset($filters['status'])) {
            $query->whereIn('status', ['completed', 'shipped', 'delivered']);
        }

        // Exclude refunded orders unless specifically requested
        if (! isset($filters['include_refunded'])) {
            $query->where('status', '!=', 'refunded');
        }

        return $query;
    }

    /**
     * Apply grouping to the query for aggregated results.
     */
    private function applyGrouping(Builder $query, string $groupBy): Builder
    {
        switch ($groupBy) {
            case 'customer':
                $query->selectRaw('customer_id, SUM(total_amount) as total_amount, COUNT(*) as order_count')
                    ->groupBy('customer_id');
                break;

            case 'month':
                $query->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(total_amount) as total_amount, COUNT(*) as order_count')
                    ->groupBy('month');
                break;

            case 'day':
                $query->selectRaw('DATE(created_at) as day, SUM(total_amount) as total_amount, COUNT(*) as order_count')
                    ->groupBy('day');
                break;
        }

        return $query;
    }
}
