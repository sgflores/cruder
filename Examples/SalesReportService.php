<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\BaseReaderService;
use SgFlores\Cruder\Strategies\Search\SearchStrategyInterface;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Sales Report Service demonstrating strategy enforcement with custom search strategies
 * 
 * This example shows how to create a reporting service that enforces specific
 * search strategies for analytics and business intelligence using DB::table().
 */
class SalesReportService extends BaseReaderService
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
        $this->setupCustomSearchStrategies();
    }

    // ========================================================================
    // --- Strategy Enforcement Configuration ---
    // ========================================================================

    /**
     * Enable strategy enforcement for this reporting service
     */
    public function shouldEnforceSearchStrategies(): bool
    {
        return true;
    }

    /**
     * Set default search strategy
     */
    public function getDefaultSearchStrategy(): ?string
    {
        return TopCustomersStrategy::key();
    }

    /**
     * Configure searchable columns for the service
     */
    public function getSearchableColumns(): array
    {
        return ['order_number', 'customer_name', 'product_name'];
    }

    /**
     * Configure filterable columns
     */
    public function getFilterableColumns(): array
    {
        return ['customer_id', 'product_id', 'status', 'total_amount', 'created_at'];
    }

    /**
     * Configure sortable columns
     */
    public function getSortableColumns(): array
    {
        return ['total_amount', 'created_at', 'order_number'];
    }

    /**
     * Set up custom search strategies for reporting
     */
    protected function setupCustomSearchStrategies(): void
    {
        $this->getSearchService()->addStrategy(TopCustomersStrategy::key(), new TopCustomersStrategy());
        $this->getSearchService()->addStrategy(TopProductsStrategy::key(), new TopProductsStrategy());
        $this->getSearchService()->addStrategy(SalesByDateStrategy::key(), new SalesByDateStrategy());
    }

    // ========================================================================
    // --- Public Report Methods ---
    // ========================================================================

    /**
     * Get top customers by total sales amount
     * 
     * @param int $limit Number of customers to return
     * @return Collection
     */
    public function getTopCustomers(int $limit = 10): Collection
    {
        $result = $this->findAll([
            'searchStrategy' => TopCustomersStrategy::key(),
            'limit' => $limit
        ]);
        
        return $result instanceof Collection ? $result : collect($result->items());
    }

    /**
     * Get top products by sales quantity
     * 
     * @param int $limit Number of products to return
     * @return Collection
     */
    public function getTopProducts(int $limit = 10): Collection
    {
        $result = $this->findAll([
            'searchStrategy' => TopProductsStrategy::key(),
            'limit' => $limit
        ]);
        
        return $result instanceof Collection ? $result : collect($result->items());
    }

    /**
     * Get sales data by date range
     * 
     * @param string $startDate Start date (Y-m-d format)
     * @param string $endDate End date (Y-m-d format)
     * @return Collection
     */
    public function getSalesByDate(string $startDate, string $endDate): Collection
    {
        $result = $this->findAll([
            'searchStrategy' => SalesByDateStrategy::key(),
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);
        
        return $result instanceof Collection ? $result : collect($result->items());
    }

    /**
     * Get sales summary with pagination
     * 
     * @param array $filters Additional filters
     * @param int $perPage Items per page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getSalesSummary(array $filters = [], int $perPage = 15)
    {
        return $this->findAll(array_merge($filters, [
            'searchStrategy' => 'salesByDate',
            'paginate' => $perPage
        ]));
    }

    /**
     * Export sales data to CSV
     * 
     * @param string $strategy Strategy to use for export
     * @param array $filters Filters to apply
     * @return string CSV data
     */
    public function exportSalesData(string $strategy, array $filters = []): string
    {
        return $this->export('csv', array_merge($filters, [
            'searchStrategy' => $strategy
        ]), ['customer_name', 'product_name', 'total_amount', 'order_date']);
    }
}

/**
 * Top Customers Search Strategy
 * 
 * Uses DB::table() to create complex reporting queries
 */
class TopCustomersStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'topCustomers';
    }

    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        // Use DB::table() for complex reporting queries
        return DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select([
                'customers.id',
                'customers.name as customer_name',
                'customers.email',
                DB::raw('SUM(orders.total_amount) as total_spent'),
                DB::raw('COUNT(orders.id) as total_orders'),
                DB::raw('AVG(orders.total_amount) as average_order_value'),
                DB::raw('MAX(orders.created_at) as last_order_date')
            ])
            ->where('orders.status', 'completed')
            ->where('orders.deleted_at', null)
            ->groupBy('customers.id', 'customers.name', 'customers.email')
            ->orderBy('total_spent', 'desc');
    }
}

/**
 * Top Products Search Strategy
 * 
 * Analyzes product performance using DB::table()
 */
class TopProductsStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'topProducts';
    }

    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select([
                'products.id',
                'products.name as product_name',
                'products.sku',
                'products.price as unit_price',
                DB::raw('SUM(order_items.quantity) as total_quantity_sold'),
                DB::raw('SUM(order_items.quantity * order_items.price) as total_revenue'),
                DB::raw('COUNT(DISTINCT orders.id) as order_count'),
                DB::raw('AVG(order_items.quantity) as average_quantity_per_order')
            ])
            ->where('orders.status', 'completed')
            ->where('orders.deleted_at', null)
            ->where('order_items.deleted_at', null)
            ->groupBy('products.id', 'products.name', 'products.sku', 'products.price')
            ->orderBy('total_quantity_sold', 'desc');
    }
}

/**
 * Sales By Date Search Strategy
 * 
 * Provides sales analytics by date range using DB::table()
 */
class SalesByDateStrategy implements SearchStrategyInterface
{
    public static function key(): string
    {
        return 'salesByDate';
    }

    public function search(Builder|QueryBuilder|null $query, array $filters, array $config = []): Builder|QueryBuilder
    {
        $startDate = $filters['start_date'] ?? now()->subMonth()->format('Y-m-d');
        $endDate = $filters['end_date'] ?? now()->format('Y-m-d');

        return DB::table('orders')
            ->join('customers', 'orders.customer_id', '=', 'customers.id')
            ->select([
                'orders.id as order_id',
                'orders.order_number',
                'customers.name as customer_name',
                'customers.email as customer_email',
                'orders.total_amount',
                'orders.status',
                'orders.created_at as order_date',
                DB::raw('DATE(orders.created_at) as order_day'),
                DB::raw('MONTH(orders.created_at) as order_month'),
                DB::raw('YEAR(orders.created_at) as order_year')
            ])
            ->where('orders.status', 'completed')
            ->where('orders.deleted_at', null)
            ->whereBetween(DB::raw('DATE(orders.created_at)'), [$startDate, $endDate])
            ->orderBy('orders.created_at', 'desc');
    }
}

/**
 * Usage Example
 * 
 * This demonstrates how to use the SalesReportService with strategy enforcement
 */
class SalesReportUsageExample
{
    public function demonstrateUsage()
    {
        // Initialize the service
        $salesReport = new SalesReportService(new Order());

        // Get top 10 customers by sales
        $topCustomers = $salesReport->getTopCustomers(10);
        echo "Top 10 Customers:\n";
        foreach ($topCustomers as $customer) {
            echo "{$customer->customer_name}: \${$customer->total_spent} ({$customer->total_orders} orders)\n";
        }

        // Get top 5 products by quantity sold
        $topProducts = $salesReport->getTopProducts(5);
        echo "\nTop 5 Products:\n";
        foreach ($topProducts as $product) {
            echo "{$product->product_name}: {$product->total_quantity_sold} units sold\n";
        }

        // Get sales data for last 30 days
        $lastMonth = now()->subMonth()->format('Y-m-d');
        $today = now()->format('Y-m-d');
        $salesData = $salesReport->getSalesByDate($lastMonth, $today);
        echo "\nSales Data (Last 30 days):\n";
        foreach ($salesData as $sale) {
            echo "Order {$sale->order_number}: {$sale->customer_name} - \${$sale->total_amount}\n";
        }

        // Export top customers to CSV
        $csvData = $salesReport->exportSalesData(TopCustomersStrategy::key(), ['limit' => 20]);
        file_put_contents('top_customers.csv', $csvData);

        // Get paginated sales summary
        $salesSummary = $salesReport->getSalesSummary(['start_date' => '2024-01-01', 'end_date' => '2024-12-31'], 25);
        echo "\nTotal sales records: {$salesSummary->total()}\n";
        echo "Current page: {$salesSummary->currentPage()}\n";
        echo "Per page: {$salesSummary->perPage()}\n";
    }
}

/**
 * Advanced Usage with Custom Filters
 */
class AdvancedSalesReportExample
{
    public function demonstrateAdvancedUsage()
    {
        $salesReport = new SalesReportService(new Order());

        // Get top customers with custom filters
        $topCustomers = $salesReport->findAll([
            'searchStrategy' => TopCustomersStrategy::key(),
            'limit' => 15,
            'total_spent' => ['operator' => 'gte', 'value' => 1000] // Only customers with $1000+ spent
        ]);

        // Get products with specific criteria
        $topProducts = $salesReport->findAll([
            'searchStrategy' => TopProductsStrategy::key(),
            'limit' => 20,
            'total_quantity_sold' => ['operator' => 'gte', 'value' => 50] // Only products with 50+ units sold
        ]);

        // Get sales data with sorting
        $salesData = $salesReport->findAll([
            'searchStrategy' => SalesByDateStrategy::key(),
            'start_date' => '2024-01-01',
            'end_date' => '2024-12-31',
            'sort_by' => 'total_amount',
            'sort_direction' => 'desc',
            'limit' => 100
        ]);

        // Export with custom filters
        $csvData = $salesReport->export('csv', [
            'searchStrategy' => TopCustomersStrategy::key(),
            'total_spent' => ['operator' => 'gte', 'value' => 5000]
        ], ['customer_name', 'total_spent', 'total_orders']);

        return [
            'top_customers' => $topCustomers,
            'top_products' => $topProducts,
            'sales_data' => $salesData,
            'csv_export' => $csvData
        ];
    }
}
