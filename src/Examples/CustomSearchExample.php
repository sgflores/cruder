<?php

namespace SgFlores\Cruder\Examples;

use SgFlores\Cruder\Examples\ProductReaderService;
use SgFlores\Cruder\Examples\CustomSearchStrategy;

/**
 * Custom Search Example
 * 
 * Demonstrates how to use custom search strategies with BaseReaderService.
 * Shows the complete workflow from strategy registration to execution.
 */
class CustomSearchExample
{
    protected ProductReaderService $productService;
    protected CustomSearchStrategy $customSearchStrategy;
    
    public function __construct()
    {
        $this->productService = new ProductReaderService();
        $this->customSearchStrategy = new CustomSearchStrategy();
    }
    
    /**
     * Demonstrates the complete custom search workflow.
     */
    public function demonstrateCustomSearchWorkflow(): void
    {
        echo "=== Custom Search Workflow Demo ===\n\n";
        
        // 1. Show initial available strategies
        echo "1. Initial available search strategies:\n";
        $initialStrategies = $this->productService->getSearchService()->getAvailableStrategies();
        echo "   ✓ Available: " . implode(', ', $initialStrategies) . "\n";
        
        // 2. Add custom search strategy
        echo "\n2. Adding custom search strategy...\n";
        $this->productService->getSearchService()->addStrategy('advanced', $this->customSearchStrategy);
        echo "   ✓ Custom 'advanced' search strategy added\n";
        
        // 3. Show updated available strategies
        echo "\n3. Updated available search strategies:\n";
        $updatedStrategies = $this->productService->getSearchService()->getAvailableStrategies();
        echo "   ✓ Available: " . implode(', ', $updatedStrategies) . "\n";
        
        // 4. Test search with default strategy
        echo "\n4. Testing search with default strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000,
                'category_id' => 1
            ]);
            echo "   ✓ Search executed with default strategy\n";
            echo "   ✓ Found " . $products->count() . " products\n";
        } catch (\Exception $e) {
            echo "   ✗ Default search failed: " . $e->getMessage() . "\n";
        }
        
        // 5. Test search with custom strategy
        echo "\n5. Testing search with custom strategy...\n";
        try {
            $products = $this->productService->searchProducts('laptop', [
                'min_price' => 500,
                'max_price' => 2000,
                'category_id' => 1,
                'search_strategy' => 'advanced'  // Specify custom strategy
            ]);
            echo "   ✓ Search executed with custom 'advanced' strategy\n";
            echo "   ✓ Found " . $products->count() . " products\n";
        } catch (\Exception $e) {
            echo "   ✗ Custom search failed: " . $e->getMessage() . "\n";
        }
        
        // 6. Test with different search terms using custom strategy
        echo "\n6. Testing with different search terms using custom strategy...\n";
        $searchTerms = ['gaming', 'office', 'portable'];
        
        foreach ($searchTerms as $term) {
            try {
                $results = $this->productService->searchProducts($term, [
                    'search_strategy' => 'advanced'
                ]);
                echo "   ✓ Custom search for '{$term}': " . $results->count() . " results\n";
            } catch (\Exception $e) {
                echo "   ✗ Custom search for '{$term}' failed: " . $e->getMessage() . "\n";
            }
        }
        
        // 7. Test with different search terms using default strategy
        echo "\n7. Testing with different search terms using default strategy...\n";
        foreach ($searchTerms as $term) {
            try {
                $results = $this->productService->searchProducts($term, []);
                echo "   ✓ Default search for '{$term}': " . $results->count() . " results\n";
            } catch (\Exception $e) {
                echo "   ✗ Default search for '{$term}' failed: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n=== Custom Search Workflow Completed ===\n";
    }
    
    /**
     * Demonstrates error handling for invalid search strategies.
     */
    public function demonstrateErrorHandling(): void
    {
        echo "\n=== Error Handling Demo ===\n\n";
        
        // 1. Try to use non-existent strategy in search
        echo "1. Trying to use non-existent strategy in search...\n";
        try {
            $products = $this->productService->searchProducts('test', [
                'search_strategy' => 'nonexistent'
            ]);
            echo "   ✗ Should have thrown an exception\n";
        } catch (\Exception $e) {
            echo "   ✓ Caught expected exception: " . $e->getMessage() . "\n";
        }
        
        // 2. Try to use invalid strategy
        echo "\n2. Trying to use invalid strategy in search...\n";
        try {
            $products = $this->productService->searchProducts('test', [
                'search_strategy' => 'invalid'
            ]);
            echo "   ✗ Should have thrown an exception\n";
        } catch (\Exception $e) {
            echo "   ✓ Prevented invalid strategy usage: " . $e->getMessage() . "\n";
        }
        
        echo "\n=== Error Handling Demo Completed ===\n";
    }
    
    /**
     * Demonstrates strategy management features.
     */
    public function demonstrateStrategyManagement(): void
    {
        echo "\n=== Strategy Management Demo ===\n\n";
        
        // 1. Check if strategy exists
        echo "1. Checking if strategies exist...\n";
        $searchService = $this->productService->getSearchService();
        
        $strategies = ['like', 'advanced', 'nonexistent'];
        foreach ($strategies as $strategy) {
            $exists = $searchService->hasStrategy($strategy);
            echo "   ✓ Strategy '{$strategy}': " . ($exists ? 'exists' : 'does not exist') . "\n";
        }
        
        // 2. Get all available strategies
        echo "\n2. All available strategies:\n";
        $availableStrategies = $searchService->getAvailableStrategies();
        echo "   ✓ Available strategies: " . implode(', ', $availableStrategies) . "\n";
        
        echo "\n=== Strategy Management Demo Completed ===\n";
    }
    
    /**
     * Run all demonstrations.
     */
    public function runAllDemonstrations(): void
    {
        echo "=== Custom Search Strategy Demonstrations ===\n\n";
        
        $this->demonstrateCustomSearchWorkflow();
        $this->demonstrateErrorHandling();
        $this->demonstrateStrategyManagement();
        
        echo "\n=== All Demonstrations Completed ===\n";
    }
}
