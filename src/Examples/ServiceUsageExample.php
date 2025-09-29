<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\Examples\UserCrudService;
use SgFlores\Cruder\Examples\ProductReaderService;
use SgFlores\Cruder\Examples\CustomValidationStrategy;
use SgFlores\Cruder\Examples\CustomSearchStrategy;
use SgFlores\Cruder\Examples\CustomExportStrategy;

/**
 * Service Usage Example
 * 
 * Demonstrates how to use all the services and strategies together.
 * Shows practical implementation patterns and best practices.
 */
class ServiceUsageExample
{
    protected UserCrudService $userService;
    protected ProductReaderService $productService;
    protected CustomValidationStrategy $validationStrategy;
    protected CustomSearchStrategy $searchStrategy;
    protected CustomExportStrategy $exportStrategy;
    
    public function __construct()
    {
        $this->userService = new UserCrudService();
        $this->productService = new ProductReaderService();
        $this->validationStrategy = new CustomValidationStrategy();
        $this->searchStrategy = new CustomSearchStrategy();
        $this->exportStrategy = new CustomExportStrategy();
    }
    
    /**
     * Example: Complete user management workflow.
     */
    public function demonstrateUserManagement(): void
    {
        echo "=== User Management Workflow ===\n\n";
        
        // 1. Create a new user with validation
        echo "1. Creating a new user...\n";
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'securepassword123',
            'password_confirmation' => 'securepassword123',
            'status' => 'active',
            'terms_accepted' => true
        ];
        
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'status' => 'required|in:active,inactive,pending',
            'terms_accepted' => 'required|accepted'
        ];
        
        try {
            $user = $this->userService->create($userData, ['profile', 'roles'], $validationRules);
            echo "   ✓ User created successfully with ID: {$user->id}\n";
        } catch (\Exception $e) {
            echo "   ✗ User creation failed: " . $e->getMessage() . "\n";
        }
        
        // 2. Find users with filtering
        echo "\n2. Finding users with filters...\n";
        $users = $this->userService->findAll([
            'status' => 'active',
            'search' => 'john',
            'sort_by' => 'created_at',
            'sort_direction' => 'desc',
            'limit' => 10
        ]);
        echo "   ✓ Found " . $users->count() . " users\n";
        
        // 3. Update user
        echo "\n3. Updating user...\n";
        try {
            $updatedUser = $this->userService->update(1, [
                'name' => 'John Smith',
                'status' => 'active'
            ], ['profile', 'roles']);
            echo "   ✓ User updated successfully\n";
        } catch (\Exception $e) {
            echo "   ✗ User update failed: " . $e->getMessage() . "\n";
        }
        
        // 4. Get users by role
        echo "\n4. Getting users by role...\n";
        $adminUsers = $this->userService->getUsersByRole('admin');
        echo "   ✓ Found " . $adminUsers->count() . " admin users\n";
        
        // 5. Get user statistics
        echo "\n5. Getting user statistics...\n";
        $activeCount = $this->userService->getActiveUsersCount();
        echo "   ✓ Active users count: {$activeCount}\n";
        
        echo "\n=== User Management Workflow Completed ===\n\n";
    }
    
    /**
     * Example: Product search and export workflow.
     */
    public function demonstrateProductManagement(): void
    {
        echo "=== Product Management Workflow ===\n\n";
        
        // 1. Search products
        echo "1. Searching products...\n";
        $products = $this->productService->searchProducts('laptop', [
            'min_price' => 500,
            'max_price' => 2000,
            'category_id' => 1,
            'in_stock' => true
        ]);
        echo "   ✓ Found " . $products->count() . " products matching search criteria\n";
        
        // 2. Get featured products
        echo "\n2. Getting featured products...\n";
        $featuredProducts = $this->productService->getFeaturedProducts(5);
        echo "   ✓ Found " . $featuredProducts->count() . " featured products\n";
        
        // 3. Get products on sale
        echo "\n3. Getting products on sale...\n";
        $saleProducts = $this->productService->getProductsOnSale();
        echo "   ✓ Found " . $saleProducts->count() . " products on sale\n";
        
        // 4. Get product statistics
        echo "\n4. Getting product statistics...\n";
        $stats = $this->productService->getProductStats();
        echo "   ✓ Total products: {$stats['total_products']}\n";
        echo "   ✓ Published products: {$stats['published_products']}\n";
        echo "   ✓ Out of stock: {$stats['out_of_stock']}\n";
        
        // 5. Export products
        echo "\n5. Exporting products...\n";
        try {
            $csvData = $this->productService->exportProductsToCsv(['status' => 'published']);
            echo "   ✓ Products exported to CSV (" . strlen($csvData) . " bytes)\n";
            
            $jsonData = $this->productService->exportProductsToJson(['featured' => true]);
            echo "   ✓ Featured products exported to JSON (" . strlen($jsonData) . " bytes)\n";
        } catch (\Exception $e) {
            echo "   ✗ Export failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Product Management Workflow Completed ===\n\n";
    }
    
    /**
     * Example: Custom validation strategy usage.
     */
    public function demonstrateCustomValidation(): void
    {
        echo "=== Custom Validation Strategy ===\n\n";
        
        // 1. Set validation strategy
        echo "1. Setting custom validation strategy...\n";
        $this->userService->setValidationStrategy($this->validationStrategy);
        echo "   ✓ Custom validation strategy set\n";
        
        // 2. Test validation with business rules
        echo "\n2. Testing validation with business rules...\n";
        $userData = [
            'name' => 'Jane Doe',
            'email' => 'jane@allowed-domain.com',
            'password' => 'securepassword123',
            'password_confirmation' => 'securepassword123',
            'status' => 'active',
            'terms_accepted' => true,
            'company_id' => 1
        ];
        
        try {
            $validatedData = $this->validationStrategy->validate($userData, 'create', [
                'company_id' => 1
            ]);
            echo "   ✓ Validation passed with business rules\n";
            echo "   ✓ Validated data keys: " . implode(', ', array_keys($validatedData)) . "\n";
        } catch (\Exception $e) {
            echo "   ✗ Validation failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Custom Validation Strategy Completed ===\n\n";
    }
    
    /**
     * Example: Custom search strategy usage.
     */
    public function demonstrateCustomSearch(): void
    {
        echo "=== Custom Search Strategy ===\n\n";
        
        // 1. Add custom search strategy
        echo "1. Adding custom search strategy...\n";
        $this->productService->getSearchService()->addStrategy('advanced', $this->searchStrategy);
        echo "   ✓ Custom 'advanced' search strategy added\n";
        
        // 2. Test search with default strategy
        echo "\n2. Testing search with default strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000
            ]);
            echo "   ✓ Search executed with default strategy\n";
            echo "   ✓ Found " . $products->count() . " products with default search\n";
        } catch (\Exception $e) {
            echo "   ✗ Default search failed: " . $e->getMessage() . "\n";
        }
        
        // 3. Test search with custom strategy
        echo "\n3. Testing search with custom strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000,
                'search_strategy' => 'advanced'  // Specify custom strategy
            ]);
            echo "   ✓ Search executed with custom 'advanced' strategy\n";
            echo "   ✓ Found " . $products->count() . " products with custom search\n";
        } catch (\Exception $e) {
            echo "   ✗ Custom search failed: " . $e->getMessage() . "\n";
        }
        
        // 4. Show available search strategies
        echo "\n4. Available search strategies:\n";
        $strategies = $this->productService->getSearchService()->getAvailableStrategies();
        echo "   ✓ Available strategies: " . implode(', ', $strategies) . "\n";
        
        echo "\n=== Custom Search Strategy Completed ===\n\n";
    }
    
    /**
     * Example: Custom export strategy usage.
     */
    public function demonstrateCustomExport(): void
    {
        echo "=== Custom Export Strategy ===\n\n";
        
        // 1. Add custom export strategy
        echo "1. Adding custom export strategy...\n";
        $this->productService->getExportService()->addStrategy('xml', $this->exportStrategy);
        echo "   ✓ Custom XML export strategy added\n";
        
        // 2. Export data using custom strategy
        echo "\n2. Exporting data using custom XML strategy...\n";
        $products = $this->productService->findAll(['status' => 'published']);
        
        try {
            $xmlData = $this->exportStrategy->export($products, [
                'root_element' => 'products',
                'item_element' => 'product',
                'columns' => ['id', 'name', 'sku', 'price', 'status'],
                'include_headers' => true,
                'date_format' => 'Y-m-d H:i:s'
            ]);
            echo "   ✓ Products exported to XML (" . strlen($xmlData) . " bytes)\n";
        } catch (\Exception $e) {
            echo "   ✗ XML export failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Custom Export Strategy Completed ===\n\n";
    }
    
    /**
     * Example: Event system usage.
     */
    public function demonstrateEventSystem(): void
    {
        echo "=== Event System ===\n\n";
        
        // 1. Setup event listeners
        echo "1. Setting up event listeners...\n";
        $eventService = $this->userService->getEventService();
        
        $eventService->listen('user.created', function ($user) {
            echo "   → Event: User created with ID {$user->id}\n";
        });
        
        $eventService->listen('user.updated', function ($user) {
            echo "   → Event: User updated with ID {$user->id}\n";
        });
        
        echo "   ✓ Event listeners registered\n";
        
        // 2. Fire events
        echo "\n2. Firing events...\n";
        $eventService->fire('user.created', (object)['id' => 1, 'name' => 'John Doe']);
        $eventService->fire('user.updated', (object)['id' => 1, 'name' => 'John Smith']);
        
        echo "\n=== Event System Completed ===\n\n";
    }
    
    /**
     * Run all examples.
     */
    public function runAllExamples(): void
    {
        echo "=== CRUDER Package Examples ===\n\n";
        
        $this->demonstrateUserManagement();
        $this->demonstrateProductManagement();
        $this->demonstrateCustomValidation();
        $this->demonstrateCustomSearch();
        $this->demonstrateCustomExport();
        $this->demonstrateEventSystem();
        
        echo "=== All Examples Completed ===\n";
    }
}
