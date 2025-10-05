<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;
use SgFlores\Cruder\Services\SearchService;
use SgFlores\Cruder\Services\ExportService;
use SgFlores\Cruder\Services\QueryLogger;
use App\Models\Product;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Real-world example demonstrating BaseCrudService
 * using a Sales and Inventory Management System.
 * 
 * This example shows:
 * - Product management with categories and inventory
 * - Order processing with order items
 * - Customer management
 * - Inventory tracking and updates
 * - Sales reporting and analytics
 * - Event-driven relationship handling
 */
class SalesInventoryExample
{
    protected ProductCrudService $productService;
    protected OrderCrudService $orderService;
    protected CustomerCrudService $customerService;
    protected InventoryCrudService $inventoryService;

    public function __construct(
        ProductCrudService $productService,
        OrderCrudService $orderService,
        CustomerCrudService $customerService,
        InventoryCrudService $inventoryService
    ) {
        $this->productService = $productService;
        $this->orderService = $orderService;
        $this->customerService = $customerService;
        $this->inventoryService = $inventoryService;
    }

    /**
     * Example 1: Product Management with Categories and Inventory
     * 
     * Demonstrates CRUD operations for products with automatic
     * category assignment and inventory creation.
     */
    public function manageProducts()
    {
        echo "=== PRODUCT MANAGEMENT EXAMPLE ===\n\n";

        // 1. Create a new product with category and inventory
        $productData = [
            'name' => 'Wireless Bluetooth Headphones',
            'description' => 'High-quality wireless headphones with noise cancellation',
            'sku' => 'WBH-001',
            'price' => 199.99,
            'cost' => 120.00,
            'category_id' => 1,
            'supplier_id' => 1,
            'status' => 'active'
        ];

        $inventoryData = [
            'quantity' => 100,
            'min_stock_level' => 10,
            'max_stock_level' => 500,
            'location' => 'Warehouse A, Shelf 1'
        ];

        // Set up event listener for automatic inventory creation
        $this->productService->getEventService()->listen(EventService::AFTER_CREATE, function ($product) use ($inventoryData) {
            // Create inventory record
            $inventoryData['product_id'] = $product->id;
            $this->inventoryService->create($inventoryData);
            
            Log::info('Product created with inventory', [
                'product_id' => $product->id,
                'sku' => $product->sku,
                'initial_stock' => $inventoryData['quantity']
            ]);
        });

        $product = $this->productService->create($productData, ['category', 'supplier', 'inventory']);
        echo "Created product: {$product->name} (SKU: {$product->sku})\n";

        // 2. Update product information
        $updateData = [
            'price' => 179.99, // Price reduction
            'description' => 'High-quality wireless headphones with active noise cancellation and 30-hour battery life'
        ];

        $updatedProduct = $this->productService->update($product->id, $updateData, ['category', 'inventory']);
        echo "Updated product price to: \${$updatedProduct->price}\n";

        // 3. Bulk create products
        $bulkProducts = [
            [
                'name' => 'Gaming Mouse',
                'sku' => 'GM-001',
                'price' => 79.99,
                'cost' => 45.00,
                'category_id' => 2,
                'supplier_id' => 1,
                'status' => 'active'
            ],
            [
                'name' => 'Mechanical Keyboard',
                'sku' => 'MK-001',
                'price' => 149.99,
                'cost' => 85.00,
                'category_id' => 2,
                'supplier_id' => 2,
                'status' => 'active'
            ]
        ];

        $this->productService->getEventService()->listen(EventService::AFTER_BULK_CREATE, function ($data) {
            foreach ($data as $productData) {
                $product = Product::where('sku', $productData['sku'])->first();
                if ($product) {
                    $this->inventoryService->create([
                        'product_id' => $product->id,
                        'quantity' => 50,
                        'min_stock_level' => 5,
                        'max_stock_level' => 200,
                        'location' => 'Warehouse B, Shelf 2'
                    ]);
                }
            }
        });

        $this->productService->bulkCreate($bulkProducts);
        echo "Bulk created 2 products with inventory\n\n";
    }

    /**
     * Example 2: Order Processing with Order Items
     * 
     * Demonstrates creating orders with multiple items and
     * automatic inventory updates.
     */
    public function processOrders()
    {
        echo "=== ORDER PROCESSING EXAMPLE ===\n\n";

        // 1. Create a customer first
        $customerData = [
            'name' => 'John Smith',
            'email' => 'john.smith@example.com',
            'phone' => '+1-555-0123',
            'address' => '123 Main St, New York, NY 10001',
            'status' => 'active'
        ];

        $customer = $this->customerService->create($customerData);
        echo "Created customer: {$customer->name}\n";

        // 2. Create an order with multiple items
        $orderData = [
            'customer_id' => $customer->id,
            'order_number' => 'ORD-' . time(),
            'status' => 'pending',
            'subtotal' => 0, // Will be calculated
            'tax_amount' => 0,
            'shipping_amount' => 9.99,
            'total_amount' => 0, // Will be calculated
            'shipping_address' => $customer->address,
            'billing_address' => $customer->address
        ];

        $orderItems = [
            [
                'product_id' => 1, // Wireless Headphones
                'quantity' => 2,
                'unit_price' => 179.99,
                'total_price' => 359.98
            ],
            [
                'product_id' => 2, // Gaming Mouse
                'quantity' => 1,
                'unit_price' => 79.99,
                'total_price' => 79.99
            ]
        ];

        // Set up event listener for order items and inventory updates
        $this->orderService->getEventService()->listen(EventService::AFTER_CREATE, function ($order) use ($orderItems) {
            $subtotal = 0;
            
            foreach ($orderItems as $itemData) {
                $itemData['order_id'] = $order->id;
                $order->items()->create($itemData);
                $subtotal += $itemData['total_price'];
                
                // Update inventory
                $this->updateInventory($itemData['product_id'], $itemData['quantity']);
            }
            
            // Update order totals
            $taxAmount = $subtotal * 0.08; // 8% tax
            $totalAmount = $subtotal + $taxAmount + $order->shipping_amount;
            
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount
            ]);
            
            // Send order confirmation email
            $this->sendOrderConfirmation($order);
            
            Log::info('Order processed with items and inventory updated', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'total_amount' => $totalAmount,
                'items_count' => count($orderItems)
            ]);
        });

        $order = $this->orderService->create($orderData, ['customer', 'items']);
        echo "Created order: {$order->order_number} with total: \${$order->total_amount}\n";

        // 3. Update order status
        $this->orderService->getEventService()->listen(EventService::AFTER_UPDATE, function ($order) {
            if ($order->status === 'shipped') {
                $this->sendShippingNotification($order);
            }
        });

        $this->orderService->update($order->id, ['status' => 'shipped'], ['customer', 'items']);
        echo "Updated order status to: shipped\n\n";
    }

    /**
     * Example 3: Advanced Product Search and Filtering
     * 
     * Demonstrates the power of BaseCrudService for complex queries.
     */
    public function searchAndFilterProducts()
    {
        echo "=== PRODUCT SEARCH AND FILTERING EXAMPLE ===\n\n";

        // 1. Search products by name
        $searchResults = $this->productService->findAll([
            'search' => 'wireless',
            'status' => 'active',
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ], ['category', 'inventory']);

        echo "Found {$searchResults->count()} wireless products\n";

        // 2. Filter products by price range
        $priceFiltered = $this->productService->findAll([
            'price' => [
                'operator' => 'between',
                'value' => [50, 200]
            ],
            'status' => 'active',
            'sort_by' => 'price',
            'sort_direction' => 'asc'
        ], ['category', 'inventory']);

        echo "Found {$priceFiltered->count()} products between \$50-\$200\n";

        // 3. Filter by category with low stock
        $lowStockProducts = $this->productService->findAll([
            'category_id' => 1,
            'status' => 'active'
        ], ['category', 'inventory']);

        $lowStock = $lowStockProducts->filter(function ($product) {
            return $product->inventory && $product->inventory->quantity <= $product->inventory->min_stock_level;
        });

        echo "Found {$lowStock->count()} products with low stock in category 1\n";

        // 4. Paginated results
        $paginatedResults = $this->productService->findAll([
            'paginate' => 10, // 10 items per page
            'status' => 'active',
            'sort_by' => 'created_at',
            'sort_direction' => 'desc'
        ], ['category']);

        echo "Paginated results: Page {$paginatedResults->currentPage()} of {$paginatedResults->lastPage()}\n";
        echo "Total products: {$paginatedResults->total()}\n\n";
    }

    /**
     * Example 4: Sales Analytics and Reporting
     * 
     * Demonstrates complex queries for business intelligence.
     */
    public function salesAnalytics()
    {
        echo "=== SALES ANALYTICS EXAMPLE ===\n\n";

        // 1. Get recent orders with customer and item details
        $recentOrders = $this->orderService->findAll([
            'created_at' => [
                'operator' => 'gte',
                'value' => now()->subDays(30)->toDateString()
            ],
            'status' => ['shipped', 'delivered'],
            'sort_by' => 'created_at',
            'sort_direction' => 'desc'
        ], ['customer', 'items.product']);

        echo "Recent orders (last 30 days): {$recentOrders->count()}\n";

        // 2. Calculate total sales
        $totalSales = $recentOrders->sum('total_amount');
        echo "Total sales: \${$totalSales}\n";

        // 3. Get top customers by order count
        $topCustomers = $this->customerService->findAll([
            'status' => 'active'
        ], ['orders']);

        $topCustomersByOrders = $topCustomers->sortByDesc(function ($customer) {
            return $customer->orders->count();
        })->take(5);

        echo "Top 5 customers by order count:\n";
        foreach ($topCustomersByOrders as $customer) {
            echo "- {$customer->name}: {$customer->orders->count()} orders\n";
        }

        // 4. Get low stock products
        $lowStockProducts = $this->productService->findAll([
            'status' => 'active'
        ], ['inventory']);

        $lowStock = $lowStockProducts->filter(function ($product) {
            return $product->inventory && $product->inventory->quantity <= $product->inventory->min_stock_level;
        });

        echo "\nLow stock products:\n";
        foreach ($lowStock as $product) {
            echo "- {$product->name}: {$product->inventory->quantity} units (min: {$product->inventory->min_stock_level})\n";
        }

        echo "\n";
    }

    /**
     * Example 5: Inventory Management
     * 
     * Demonstrates inventory operations and stock management.
     */
    public function inventoryManagement()
    {
        echo "=== INVENTORY MANAGEMENT EXAMPLE ===\n\n";

        // 1. Get all inventory with low stock
        $lowStockInventory = $this->inventoryService->findAll([
            'quantity' => [
                'operator' => 'lte',
                'value' => 10
            ]
        ], ['product']);

        echo "Low stock items: {$lowStockInventory->count()}\n";

        // 2. Update inventory levels
        $inventoryUpdates = [
            ['product_id' => 1, 'quantity' => 150], // Restock headphones
            ['product_id' => 2, 'quantity' => 75]   // Restock mouse
        ];

        foreach ($inventoryUpdates as $update) {
            $inventory = $this->inventoryService->findById($update['product_id']);
            if ($inventory) {
                $this->inventoryService->update($inventory->id, [
                    'quantity' => $update['quantity']
                ]);
                echo "Updated inventory for product {$update['product_id']}: {$update['quantity']} units\n";
            }
        }

        // 3. Bulk update inventory for multiple products
        $bulkInventoryData = [
            ['product_id' => 3, 'quantity' => 200, 'location' => 'Warehouse C, Shelf 3'],
            ['product_id' => 4, 'quantity' => 100, 'location' => 'Warehouse C, Shelf 4']
        ];

        $this->inventoryService->bulkCreate($bulkInventoryData);
        echo "Bulk created inventory records for 2 products\n\n";
    }

    /**
     * Example 6: Event-Driven Business Logic
     * 
     * Demonstrates complex business rules using event listeners.
     */
    public function eventDrivenBusinessLogic()
    {
        echo "=== EVENT-DRIVEN BUSINESS LOGIC EXAMPLE ===\n\n";

        // Set up comprehensive event listeners
        $this->setupBusinessEventListeners();

        // 1. Create a product (triggers inventory creation)
        $product = $this->productService->create([
            'name' => 'Smart Watch',
            'sku' => 'SW-001',
            'price' => 299.99,
            'cost' => 180.00,
            'category_id' => 3,
            'supplier_id' => 1,
            'status' => 'active'
        ], ['category', 'inventory']);

        // 2. Create an order (triggers inventory updates and notifications)
        $order = $this->orderService->create([
            'customer_id' => 1,
            'order_number' => 'ORD-EVENT-' . time(),
            'status' => 'pending',
            'subtotal' => 299.99,
            'tax_amount' => 24.00,
            'shipping_amount' => 9.99,
            'total_amount' => 333.98
        ], ['customer']);

        // 3. Update order status (triggers shipping notification)
        $this->orderService->update($order->id, ['status' => 'shipped']);

        echo "Event-driven operations completed\n\n";
    }

    /**
     * Example 7: Performance Monitoring and Caching
     * 
     * Demonstrates performance features and caching.
     */
    public function performanceExample()
    {
        echo "=== PERFORMANCE MONITORING EXAMPLE ===\n\n";

        // 1. Enable caching for product queries
        $startTime = microtime(true);
        
        $products = $this->productService->findAll([
            'status' => 'active',
            'sort_by' => 'name'
        ], ['category']);

        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000;

        echo "Query execution time: {$executionTime}ms\n";
        echo "Products found: {$products->count()}\n";

        // 2. Use chunked processing for large datasets
        $this->productService->findAllChunked([
            'status' => 'active'
        ], function ($products) {
            echo "Processing chunk of {$products->count()} products\n";
            // Process each chunk (e.g., send emails, update records, etc.)
        });

        echo "Chunked processing completed\n\n";
    }

    // Helper methods

    private function updateInventory(int $productId, int $quantity): void
    {
        $inventory = $this->inventoryService->findById($productId);
        if ($inventory) {
            $newQuantity = $inventory->quantity - $quantity;
            $this->inventoryService->update($inventory->id, [
                'quantity' => $newQuantity
            ]);

            // Check for low stock
            if ($newQuantity <= $inventory->min_stock_level) {
                $this->sendLowStockAlert($inventory);
            }
        }
    }

    private function sendOrderConfirmation($order): void
    {
        // Email sending logic
        Log::info('Order confirmation sent', [
            'order_id' => $order->id,
            'customer_email' => $order->customer->email
        ]);
    }

    private function sendShippingNotification($order): void
    {
        // Shipping notification logic
        Log::info('Shipping notification sent', [
            'order_id' => $order->id,
            'tracking_number' => 'TRK-' . time()
        ]);
    }

    private function sendLowStockAlert($inventory): void
    {
        // Low stock alert logic
        Log::warning('Low stock alert', [
            'product_id' => $inventory->product_id,
            'current_quantity' => $inventory->quantity,
            'min_stock_level' => $inventory->min_stock_level
        ]);
    }

    private function setupBusinessEventListeners(): void
    {
        // Product creation events
        $this->productService->getEventService()->listen(EventService::AFTER_CREATE, function ($product) {
            // Create inventory
            $this->inventoryService->create([
                'product_id' => $product->id,
                'quantity' => 100,
                'min_stock_level' => 10,
                'max_stock_level' => 500,
                'location' => 'Main Warehouse'
            ]);

            // Notify suppliers
            Log::info('New product added, supplier notified', [
                'product_id' => $product->id,
                'supplier_id' => $product->supplier_id
            ]);
        });

        // Order creation events
        $this->orderService->getEventService()->listen(EventService::AFTER_CREATE, function ($order) {
            // Send order confirmation
            $this->sendOrderConfirmation($order);

            // Reserve inventory
            Log::info('Inventory reserved for order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number
            ]);
        });

        // Order update events
        $this->orderService->getEventService()->listen(EventService::AFTER_UPDATE, function ($order) {
            if ($order->status === 'shipped') {
                $this->sendShippingNotification($order);
            } elseif ($order->status === 'cancelled') {
                // Restore inventory
                Log::info('Order cancelled, inventory restored', [
                    'order_id' => $order->id
                ]);
            }
        });
    }
}

/**
 * Product CRUD Service
 */
class ProductCrudService extends BaseCrudService
{
    public function __construct(
        Product $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'cost', 'category_id', 'supplier_id', 'status'
        ];
    }

    public function getDirectSortableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'cost', 'created_at', 'updated_at'
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
        return ['category', 'supplier'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['category', 'supplier', 'inventory'];
    }
}

/**
 * Order CRUD Service
 */
class OrderCrudService extends BaseCrudService
{
    public function __construct(
        Order $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'customer_id', 'order_number', 'status', 'total_amount'
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
        return ['customer', 'items', 'items.product'];
    }
}

/**
 * Customer CRUD Service
 */
class CustomerCrudService extends BaseCrudService
{
    public function __construct(
        Customer $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
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
            'name', 'email', 'status', 'created_at', 'updated_at'
        ];
    }

    public function getDirectTextSearchColumns(): array
    {
        return [
            'name', 'email'
        ];
    }

    public function getCollectionRelations(): array
    {
        return ['orders'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['orders', 'orders.items'];
    }
}

/**
 * Inventory CRUD Service
 */
class InventoryCrudService extends BaseCrudService
{
    public function __construct(
        Inventory $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    public function getDirectFilterableColumns(): array
    {
        return [
            'product_id', 'quantity', 'min_stock_level', 'max_stock_level', 'location'
        ];
    }

    public function getDirectSortableColumns(): array
    {
        return [
            'product_id', 'quantity', 'min_stock_level', 'max_stock_level', 'created_at', 'updated_at'
        ];
    }

    public function getDirectTextSearchColumns(): array
    {
        return [
            'location'
        ];
    }

    public function getCollectionRelations(): array
    {
        return ['product'];
    }

    public function getSingleRecordRelations(): array
    {
        return ['product'];
    }
}