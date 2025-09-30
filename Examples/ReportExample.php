<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Report Example demonstrating custom search strategy with BaseReaderService
 * 
 * This example shows how to create custom search strategies for complex
 * reporting queries and business intelligence.
 */
class ReportExample extends BaseReaderService
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
        $this->setupCustomSearchStrategies();
    }

    // ========================================================================
    // --- Override Configuration Methods ---
    // ========================================================================

    public function getDirectFilterableColumns(): array
    {
        return [
            'customer_id', 'order_number', 'status', 'total_amount', 'created_at'
        ];
    }

    public function getDirectSortableColumns(): array
    {
        return [
            'order_number', 'status', 'total_amount', 'created_at', 'updated_at'
        ];
    }

    public function getDirectTextSearchColumns(): array
    {
        return [
            'order_number'
        ];
    }

    public function getCollectionRelations(): array
    {
        return ['customer', 'items'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['customer', 'items.product'];
    }

    /**
     * Set up custom search strategies for reporting
     */
    protected function setupCustomSearchStrategies(): void
    {
        // Add custom search strategies
        $this->getSearchService()->addStrategy('sales_report', new SalesReportSearchStrategy());
        $this->getSearchService()->addStrategy('customer_analytics', new CustomerAnalyticsSearchStrategy());
        $this->getSearchService()->addStrategy('product_performance', new ProductPerformanceSearchStrategy());
    }

    /**
     * Generate sales report with custom search strategy
     */
    public function generateSalesReport(array $filters = []): Collection
    {
        return $this->findAll($filters, ['customer', 'items.product']);
    }

    /**
     * Generate customer analytics report
     */
    public function generateCustomerAnalytics(array $filters = []): Collection
    {
        return $this->findAll($filters, ['customer', 'items']);
    }

    /**
     * Generate product performance report
     */
    public function generateProductPerformanceReport(array $filters = []): Collection
    {
        return $this->findAll($filters, ['items.product']);
    }

    /**
     * Get top customers by order value
     */
    public function getTopCustomersByValue(int $limit = 10): Collection
    {
        return $this->findAll([
            'search' => 'top_customers',
            'limit' => $limit,
            'sort_by' => 'total_amount',
            'sort_direction' => 'desc'
        ], ['customer']);
    }

    /**
     * Get low performing products
     */
    public function getLowPerformingProducts(int $limit = 10): Collection
    {
        return $this->findAll([
            'search' => 'low_performing_products',
            'limit' => $limit,
            'sort_by' => 'total_amount',
            'sort_direction' => 'asc'
        ], ['items.product']);
    }

    /**
     * Get monthly sales trend
     */
    public function getMonthlySalesTrend(string $year = null): Collection
    {
        $year = $year ?? date('Y');
        
        return $this->findAll([
            'search' => 'monthly_trend',
            'year' => $year,
            'sort_by' => 'created_at',
            'sort_direction' => 'asc'
        ], ['customer']);
    }
}

/**
 * Custom Search Strategy for Sales Reports
 */
class SalesReportSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        // Add custom sales report logic
        return $query->where('status', '!=', 'cancelled')
                    ->where('total_amount', '>', 0)
                    ->with(['customer', 'items.product']);
    }
}

/**
 * Custom Search Strategy for Customer Analytics
 */
class CustomerAnalyticsSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        // Add customer analytics logic
        return $query->whereHas('customer', function ($q) {
            $q->where('status', 'active');
        })
        ->with(['customer' => function ($query) {
            $query->select('id', 'name', 'email', 'created_at');
        }]);
    }
}

/**
 * Custom Search Strategy for Product Performance
 */
class ProductPerformanceSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        // Add product performance logic
        return $query->whereHas('items', function ($q) {
            $q->where('quantity', '>', 0);
        })
        ->with(['items.product' => function ($query) {
            $query->select('id', 'name', 'sku', 'price');
        }]);
    }
}

/**
 * Custom Search Strategy for Top Customers
 */
class TopCustomersSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        // Group by customer and sum total amounts
        return $query->selectRaw('customer_id, SUM(total_amount) as total_spent, COUNT(*) as order_count')
                    ->groupBy('customer_id')
                    ->having('total_spent', '>', 0)
                    ->orderBy('total_spent', 'desc');
    }
}

/**
 * Custom Search Strategy for Low Performing Products
 */
class LowPerformingProductsSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        // Find products with low sales
        return $query->whereHas('items', function ($q) {
            $q->where('quantity', '<', 5); // Less than 5 units sold
        })
        ->with(['items.product']);
    }
}

/**
 * Custom Search Strategy for Monthly Sales Trend
 */
class MonthlySalesTrendSearchStrategy implements SearchStrategyInterface
{
    public function search(Builder $query, array $filters, array $config = []): Builder
    {
        $year = $filters['year'] ?? date('Y');
        
        // Group by month and sum sales
        return $query->selectRaw('MONTH(created_at) as month, SUM(total_amount) as monthly_total, COUNT(*) as order_count')
                    ->whereYear('created_at', $year)
                    ->where('status', '!=', 'cancelled')
                    ->groupBy('month')
                    ->orderBy('month');
    }
}
