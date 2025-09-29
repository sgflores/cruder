<?php

namespace SgFlores\Cruder\Examples;

// use App\Models\Product; // Replace with your actual Product model
use SgFlores\Cruder\BaseReaderService;

/**
 * Product Reader Service Example
 * 
 * Demonstrates how to create a read-only service using BaseReaderService.
 * Shows advanced filtering, search, and export capabilities.
 */
class ProductReaderService extends BaseReaderService
{
    // ========================================================================
    // --- Column Validation Constants ---
    // ========================================================================
    
    /**
     * Direct database columns available for exact matching filters.
     */
    protected const DIRECT_FILTERABLE_COLUMNS = [
        'id', 'name', 'sku', 'price', 'status', 'category_id', 'created_at'
    ];
    
    /**
     * Direct database columns available for fuzzy text search.
     */
    protected const DIRECT_TEXT_SEARCH_COLUMNS = [
        'name', 'description', 'sku'
    ];
    
    /**
     * Direct database columns available for result sorting.
     */
    protected const DIRECT_SORTABLE_COLUMNS = [
        'id', 'name', 'sku', 'price', 'status', 'created_at', 'updated_at'
    ];
    
    /**
     * Related model columns available for exact matching filters.
     */
    protected const RELATED_FILTERABLE_COLUMNS = [
        'category.name', 'brand.name', 'tags.name'
    ];
    
    /**
     * Related model columns available for fuzzy text search.
     */
    protected const RELATED_TEXT_SEARCH_COLUMNS = [
        'category.name', 'brand.name', 'tags.name'
    ];
    
    /**
     * Related model columns available for result sorting.
     */
    protected const RELATED_SORTABLE_COLUMNS = [
        'category.name', 'brand.name'
    ];
    
    // ========================================================================
    // --- Relations Configuration ---
    // ========================================================================
    
    /**
     * Default relations to eager load for collection queries.
     */
    protected const COLLECTION_RELATIONS = ['category', 'brand', 'images'];
    
    /**
     * Default relations to eager load for single record queries.
     */
    protected const SINGLE_RECORD_RELATIONS = ['category', 'brand', 'images', 'tags', 'reviews'];
    
    // ========================================================================
    // --- Feature Configuration ---
    // ========================================================================
    
    /**
     * Enable query result caching for performance optimization.
     */
    protected const QUERY_CACHE_ENABLED = true;
    
    /**
     * Cache lifetime in seconds.
     */
    protected const CACHE_LIFETIME_SECONDS = 3600; // 1 hour
    
    
    /**
     * Constructor
     */
    public function __construct()
    {
        // parent::__construct(new Product()); // Replace with your actual Product model
        parent::__construct(new \stdClass()); // Placeholder for example
        $this->setupEventListeners();
    }
    
    /**
     * Configure custom services and strategies.
     * 
     * Override this method in child classes to add custom strategies,
     * services, or modify existing behavior.
     * 
     * @return void
     */
    protected function configureServices(): void
    {
        // Add custom search strategies
        // $this->searchService->addStrategy('elasticsearch', new ElasticsearchStrategy());
        
        // Add export strategies (optional - not included by default)
        $this->exportService->addStrategy('csv', new \SgFlores\Cruder\Strategies\Export\CsvExportStrategy());
        $this->exportService->addStrategy('json', new \SgFlores\Cruder\Strategies\Export\JsonExportStrategy());
        
        // Add custom export strategies
        // $this->exportService->addStrategy('excel', new ExcelExportStrategy());
    }
    
    /**
     * Setup event listeners for read operations.
     */
    protected function setupEventListeners(): void
    {
        // Before find: add security checks
        $this->getEventService()->listen('before_find', function ($filters) {
            // Check user permissions
            // Add tenant filtering
            // Log search attempts
        });
        
        // After find: post-processing
        $this->getEventService()->listen('after_find', function ($products) {
            // Add computed fields
            // Apply business rules
            // Log successful search
        });
    }
    
    /**
     * Override to add custom query constraints.
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    protected function applyCustomQueryConstraints($query, array $filters): void
    {
        // Only show published products by default
        if (!isset($filters['include_draft'])) {
            $query->where('status', 'published');
        }
        
        // Filter by price range
        if (isset($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }
        
        if (isset($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }
        
        // Filter by category
        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        
        // Filter by brand
        if (isset($filters['brand_id'])) {
            $query->where('brand_id', $filters['brand_id']);
        }
        
        // Filter by availability
        if (isset($filters['in_stock'])) {
            $query->where('stock_quantity', '>', 0);
        }
    }
    
    /**
     * Get products by category.
     * 
     * @param int $categoryId
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getProductsByCategory(int $categoryId, array $filters = []): \Illuminate\Support\Collection
    {
        $filters['category_id'] = $categoryId;
        return $this->findAll($filters);
    }
    
    /**
     * Get featured products.
     * 
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function getFeaturedProducts(int $limit = 10): \Illuminate\Support\Collection
    {
        return $this->findAll([
            'featured' => true,
            'limit' => $limit,
            'sort_by' => 'created_at',
            'sort_direction' => 'desc'
        ]);
    }
    
    /**
     * Get products on sale.
     * 
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function getProductsOnSale(array $filters = []): \Illuminate\Support\Collection
    {
        $filters['sale_price'] = ['operator' => 'is_not_null'];
        return $this->findAll($filters);
    }
    
    /**
     * Search products with advanced filters.
     * 
     * @param string $searchTerm
     * @param array $filters
     * @return \Illuminate\Support\Collection
     */
    public function searchProducts(string $searchTerm, array $filters = []): \Illuminate\Support\Collection
    {
        $filters['search'] = $searchTerm;
        return $this->findAll($filters);
    }
    
    /**
     * Get product statistics.
     * 
     * @return array
     */
    public function getProductStats(): array
    {
        return [
            'total_products' => $this->count(),
            'published_products' => $this->count(['status' => 'published']),
            'draft_products' => $this->count(['status' => 'draft']),
            'out_of_stock' => $this->count(['stock_quantity' => ['operator' => 'lte', 'value' => 0]]),
            'on_sale' => $this->count(['sale_price' => ['operator' => 'is_not_null']])
        ];
    }
    
    /**
     * Export products to CSV.
     * 
     * @param array $filters
     * @return string
     */
    public function exportProductsToCsv(array $filters = []): string
    {
        return $this->export('csv', $filters, [
            'id', 'name', 'sku', 'price', 'status', 'category.name'
        ]);
    }
    
    /**
     * Export products to JSON.
     * 
     * @param array $filters
     * @return string
     */
    public function exportProductsToJson(array $filters = []): string
    {
        return $this->export('json', $filters);
    }
}
