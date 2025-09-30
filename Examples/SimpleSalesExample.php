<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\BaseReaderService;
use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Simple Sales Example demonstrating BaseCrudService and BaseReaderService
 * 
 * This example shows practical usage patterns for:
 * - Product management
 * - Order processing
 * - Customer management
 * - Search and filtering
 * - Event-driven relationships
 */
class SimpleSalesExample
{
    protected ProductService $productService;
    protected OrderService $orderService;
    protected CustomerService $customerService;

    public function __construct(
        ProductService $productService,
        OrderService $orderService,
        CustomerService $customerService
    ) {
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->customerService = $customerService;
    }

    /**
     * Example 1: Basic CRUD Operations
     */
    public function basicCrudOperations()
    {
        echo "=== BASIC CRUD OPERATIONS ===\n\n";

        // 1. Create a product
        $product = $this->productService->create([
            'name' => 'Laptop Computer',
            'sku' => 'LAP-001',
            'price' => 999.99,
            'description' => 'High-performance laptop for business use',
            'status' => 'active'
        ]);

        echo "Created product: {$product->name} (SKU: {$product->sku})\n";

        // 2. Read/Find products
        $products = $this->productService->findAll([
            'status' => 'active',
            'sort_by' => 'name',
            'sort_direction' => 'asc'
        ]);

        echo "Found {$products->count()} active products\n";

        // 3. Update a product
        $updatedProduct = $this->productService->update($product->id, [
            'price' => 899.99
        ]);

        echo "Updated product price to: \${$updatedProduct->price}\n";

        // 4. Find by ID
        $foundProduct = $this->productService->findById($product->id);
        echo "Found product by ID: {$foundProduct->name}\n\n";
    }

    /**
     * Example 2: Search and Filtering
     */
    public function searchAndFiltering()
    {
        echo "=== SEARCH AND FILTERING ===\n\n";

        // 1. Text search
        $searchResults = $this->productService->findAll([
            'search' => 'laptop',
            'status' => 'active'
        ]);

        echo "Search results for 'laptop': {$searchResults->count()} products\n";

        // 2. Price range filtering
        $priceFiltered = $this->productService->findAll([
            'price' => [
                'operator' => 'between',
                'value' => [500, 1000]
            ],
            'status' => 'active'
        ]);

        echo "Products between \$500-\$1000: {$priceFiltered->count()}\n";

        // 3. Pagination
        $paginatedResults = $this->productService->findAll([
            'page' => 5, // 5 items per page
            'status' => 'active'
        ]);

        echo "Paginated results: Page {$paginatedResults->currentPage()} of {$paginatedResults->lastPage()}\n";
        echo "Total products: {$paginatedResults->total()}\n\n";
    }

    /**
     * Example 3: Order Processing with Relationships
     */
    public function orderProcessing()
    {
        echo "=== ORDER PROCESSING ===\n\n";

        // 1. Create a customer
        $customer = $this->customerService->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1-555-0123'
        ]);

        echo "Created customer: {$customer->name}\n";

        // 2. Create an order with automatic item handling
        $this->orderService->getEventService()->listen('after_create', function ($order) {
            // Add order items
            $order->items()->create([
                'product_id' => 1,
                'quantity' => 2,
                'unit_price' => 899.99,
                'total_price' => 1799.98
            ]);

            // Update inventory
            $this->updateInventory(1, 2);

            // Send confirmation email
            Log::info('Order confirmation sent', [
                'order_id' => $order->id,
                'customer_email' => $order->customer->email
            ]);
        });

        $order = $this->orderService->create([
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . time(),
            'status' => 'pending',
            'total_amount' => 1799.98
        ], ['customer', 'items']);

        echo "Created order: {$order->order_number} for \${$order->total_amount}\n";

        // 3. Update order status
        $this->orderService->update($order->id, [
            'status' => 'shipped'
        ]);

        echo "Updated order status to: shipped\n\n";
    }

    /**
     * Example 4: Bulk Operations
     */
    public function bulkOperations()
    {
        echo "=== BULK OPERATIONS ===\n\n";

        // 1. Bulk create products
        $products = [
            [
                'name' => 'Wireless Mouse',
                'sku' => 'WM-001',
                'price' => 29.99,
                'status' => 'active'
            ],
            [
                'name' => 'Keyboard',
                'sku' => 'KB-001',
                'price' => 79.99,
                'status' => 'active'
            ],
            [
                'name' => 'Monitor',
                'sku' => 'MON-001',
                'price' => 299.99,
                'status' => 'active'
            ]
        ];

        $this->productService->bulkCreate($products);
        echo "Bulk created 3 products\n";

        // 2. Bulk update products
        $this->productService->bulkUpdate(
            ['status' => 'active'], // Filter: all active products
            ['price' => DB::raw('price * 0.9')] // 10% discount
        );

        echo "Applied 10% discount to all active products\n\n";
    }

    /**
     * Example 5: Advanced Queries
     */
    public function advancedQueries()
    {
        echo "=== ADVANCED QUERIES ===\n\n";

        // 1. Complex filtering
        $expensiveProducts = $this->productService->findAll([
            'price' => [
                'operator' => 'gte',
                'value' => 500
            ],
            'status' => 'active',
            'sort_by' => 'price',
            'sort_direction' => 'desc'
        ]);

        echo "Expensive products (\$500+): {$expensiveProducts->count()}\n";

        // 2. Date range filtering
        $recentOrders = $this->orderService->findAll([
            'created_at' => [
                'operator' => 'gte',
                'value' => now()->subDays(7)->toDateString()
            ],
            'status' => 'shipped'
        ], ['customer']);

        echo "Recent orders (last 7 days): {$recentOrders->count()}\n";

        // 3. Count records
        $totalProducts = $this->productService->count(['status' => 'active']);
        echo "Total active products: {$totalProducts}\n\n";
    }

    /**
     * Example 6: Event-Driven Business Logic
     */
    public function eventDrivenLogic()
    {
        echo "=== EVENT-DRIVEN LOGIC ===\n\n";

        // Set up event listeners
        $this->productService->getEventService()->listen('after_create', function ($product) {
            Log::info('New product created', [
                'product_id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku
            ]);
        });

        $this->orderService->getEventService()->listen('after_update', function ($order) {
            if ($order->status === 'cancelled') {
                Log::info('Order cancelled, inventory restored', [
                    'order_id' => $order->id
                ]);
            }
        });

        // Create a product (triggers event)
        $product = $this->productService->create([
            'name' => 'Tablet',
            'sku' => 'TAB-001',
            'price' => 399.99,
            'status' => 'active'
        ]);

        echo "Created product with event logging\n\n";
    }

    // Helper method
    private function updateInventory(int $productId, int $quantity): void
    {
        // Inventory update logic
        Log::info('Inventory updated', [
            'product_id' => $productId,
            'quantity_reduced' => $quantity
        ]);
    }
}

/**
 * Product Service extending BaseCrudService
 */
class ProductService extends BaseCrudService
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'status', 'created_at'
        ];
    }

    public function getDirectSortableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'created_at', 'updated_at'
        ];
    }

    public function getDirectTextSearchColumns(): array
    {
        return [
            'name', 'description', 'sku'
        ];
    }

    public function getCollectionRelations(): array
    {
        return [];
    }

    public function getSingleRecordRelations(): array
    {
        return [];
    }
}

/**
 * Order Service extending BaseCrudService
 */
class OrderService extends BaseCrudService
{
    public function __construct(Order $model)
    {
        parent::__construct($model);
    }

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
        return ['customer'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['customer', 'items'];
    }
}

/**
 * Customer Service extending BaseCrudService
 */
class CustomerService extends BaseCrudService
{
    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'name', 'email', 'phone', 'status'
        ];
    }

    public function getDirectSortableColumns(): array
    {
        return [
            'name', 'email', 'created_at', 'updated_at'
        ];
    }

    public function getDirectTextSearchColumns(): array
    {
        return [
            'name', 'email', 'phone'
        ];
    }

    public function getCollectionRelations(): array
    {
        return ['orders'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['orders'];
    }
}

