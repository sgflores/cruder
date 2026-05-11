<?php

namespace SgFlores\Cruder\Examples;

use App\Models\Product;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use SgFlores\Cruder\BaseCrudService;
use SgFlores\Cruder\Services\EventService;
use SgFlores\Cruder\Services\ValidationService;
use SgFlores\Cruder\Strategies\Validation\ValidationStrategyInterface;

/**
 * Product Example demonstrating custom validation strategy with BaseCrudService
 *
 * This example shows how to create custom validation strategies and use
 * simple array validation rules for product management.
 */
class ProductExample extends BaseCrudService
{
    public function __construct(
        Product $model,
        EventService $eventService,
        ValidationService $validationService
    ) {
        parent::__construct($model, $eventService, $validationService);
    }

    // ========================================================================
    // --- Override Configuration Methods ---
    // ========================================================================

    /**
     * Define filterable columns for products.
     */
    public function getDirectFilterableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'cost', 'category_id', 'supplier_id', 'status',
        ];
    }

    /**
     * Define sortable columns for products.
     */
    public function getDirectSortableColumns(): array
    {
        return [
            'name', 'sku', 'price', 'cost', 'created_at', 'updated_at',
        ];
    }

    /**
     * Define searchable columns for products.
     */
    public function getDirectTextSearchColumns(): array
    {
        return [
            'name', 'description', 'sku',
        ];
    }

    /**
     * Define relations to load for collections.
     */
    public function getCollectionRelations(): array
    {
        return ['category', 'supplier'];
    }

    /**
     * Define relations to load for single records.
     */
    public function getSingleRecordRelations(): array
    {
        return ['category', 'supplier'];
    }

    /**
     * Create a product with flexible validation
     *
     * @param  array  $data  Product data
     * @param  array|ValidationStrategyInterface|null  $validationRules  Array rules or validation strategy
     * @return Product
     */
    public function createProduct(array $data, $validationRules = null)
    {
        // If no validation provided, use default array rules
        if ($validationRules === null) {
            $validationRules = $this->getDefaultCreateRules();
        }

        return $this->create($data, ['category', 'supplier'], $validationRules);
    }

    /**
     * Update a product with flexible validation
     *
     * @param  int  $id  Product ID
     * @param  array  $data  Product data
     * @param  array|ValidationStrategyInterface|null  $validationRules  Array rules or validation strategy
     * @return Product|null
     */
    public function updateProduct(int $id, array $data, $validationRules = null)
    {
        // If no validation provided, use default update rules
        if ($validationRules === null) {
            $validationRules = $this->getDefaultUpdateRules($id);
        }

        return $this->update($id, $data, ['category', 'supplier'], $validationRules);
    }

    /**
     * Get default validation rules for product creation
     */
    protected function getDefaultCreateRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:50',
            'price' => 'required|numeric|min:0.01',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:active,inactive,discontinued',
        ];
    }

    /**
     * Get default validation rules for product updates
     *
     * @param  int  $id  Product ID
     */
    protected function getDefaultUpdateRules(int $id): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'sku' => 'sometimes|string|unique:products,sku,'.$id.'|max:50',
            'price' => 'sometimes|numeric|min:0.01',
            'cost' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'sometimes|exists:categories,id',
            'supplier_id' => 'sometimes|exists:suppliers,id',
            'status' => 'sometimes|in:active,inactive,discontinued',
        ];
    }

    /**
     * Get business validation rules with advanced logic
     */
    public function getBusinessRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|regex:/^[A-Z]{2,4}-[0-9]{3,6}$/',
            'price' => 'required|numeric|min:0.01',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:active,inactive,discontinued',
            'manager_approval' => 'required_if:price,>,1000|boolean',
        ];
    }

    /**
     * Get conditional validation rules
     */
    public function getConditionalRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|unique:products,sku|max:50',
            'price' => 'required|numeric|min:0.01',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:active,inactive,discontinued',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'sale_price' => 'required_if:discount_percentage,>,0|numeric|min:0|lt:price',
        ];
    }
}

/**
 * Custom Validation Strategy for Product Business Rules
 *
 * This strategy demonstrates advanced validation with custom business logic
 */
class ProductBusinessValidationStrategy implements ValidationStrategyInterface
{
    public function validate(array $data, string $operation = 'create', array $context = []): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50',
            'price' => 'required|numeric|min:0.01',
            'cost' => 'required|numeric|min:0',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'required|exists:categories,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'status' => 'required|in:active,inactive,discontinued',
        ];

        // Add SKU uniqueness rule based on operation
        if ($operation === 'create') {
            $rules['sku'] .= '|unique:products,sku';
        } else {
            $productId = $context['model']->id ?? null;
            if ($productId) {
                $rules['sku'] .= '|unique:products,sku,'.$productId;
            }
        }

        $validator = Validator::make($data, $rules);

        // Advanced business validation
        $validator->after(function ($validator) use ($data, $operation, $context) {
            // Business rule: Price must be at least 20% higher than cost
            if (isset($data['price']) && isset($data['cost'])) {
                $minPrice = $data['cost'] * 1.2; // 20% markup
                if ($data['price'] < $minPrice) {
                    $validator->errors()->add('price', "Price must be at least 20% higher than cost (minimum: \${$minPrice}).");
                }
            }

            // Business rule: SKU must follow company format
            if (isset($data['sku'])) {
                if (! preg_match('/^[A-Z]{2,4}-[0-9]{3,6}$/', $data['sku'])) {
                    $validator->errors()->add('sku', 'SKU must follow format: XX-XXX (2-4 letters, hyphen, 3-6 numbers).');
                }
            }

            // Business rule: Product name must be unique within category
            if (isset($data['name']) && isset($data['category_id'])) {
                $query = Product::where('name', $data['name'])
                    ->where('category_id', $data['category_id']);

                if ($operation === 'update' && isset($context['model'])) {
                    $query->where('id', '!=', $context['model']->id);
                }

                if ($query->exists()) {
                    $validator->errors()->add('name', 'A product with this name already exists in the selected category.');
                }
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}

/**
 * Usage Examples for ProductExample
 *
 * This class demonstrates how to use the ProductExample with different
 * validation approaches: array rules and validation strategies.
 */
class ProductExampleUsage
{
    protected ProductExample $productService;

    public function __construct(ProductExample $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Example 1: Using array validation rules
     */
    public function createWithArrayRules()
    {
        $productData = [
            'name' => 'Laptop Computer',
            'sku' => 'LAP-001',
            'price' => 999.99,
            'cost' => 600.00,
            'description' => 'High-performance laptop',
            'category_id' => 1,
            'supplier_id' => 1,
            'status' => 'active',
        ];

        // Use default array rules
        $product = $this->productService->createProduct($productData);

        // Or use custom array rules
        $customRules = $this->productService->getBusinessRules();
        $product = $this->productService->createProduct($productData, $customRules);

        return $product;
    }

    /**
     * Example 2: Using validation strategy
     */
    public function createWithValidationStrategy()
    {
        $productData = [
            'name' => 'Gaming Mouse',
            'sku' => 'GM-001',
            'price' => 79.99,
            'cost' => 45.00,
            'description' => 'High-precision gaming mouse',
            'category_id' => 2,
            'supplier_id' => 1,
            'status' => 'active',
        ];

        // Use custom validation strategy
        $strategy = new ProductBusinessValidationStrategy;
        $product = $this->productService->createProduct($productData, $strategy);

        return $product;
    }

    /**
     * Example 3: Update with conditional validation
     */
    public function updateWithConditionalValidation()
    {
        $productId = 1;
        $updateData = [
            'price' => 899.99,
            'discount_percentage' => 10,
            'sale_price' => 809.99,
        ];

        // Use conditional validation rules
        $conditionalRules = $this->productService->getConditionalRules();
        $product = $this->productService->updateProduct($productId, $updateData, $conditionalRules);

        return $product;
    }

    /**
     * Example 4: Flexible validation approach
     */
    public function flexibleValidation()
    {
        $productData = [
            'name' => 'Wireless Headphones',
            'sku' => 'WH-001',
            'price' => 199.99,
            'cost' => 120.00,
            'category_id' => 1,
            'supplier_id' => 1,
            'status' => 'active',
        ];

        // Option 1: No validation (uses default rules)
        $product1 = $this->productService->createProduct($productData);

        // Option 2: Array validation rules
        $arrayRules = $this->productService->getDefaultCreateRules();
        $product2 = $this->productService->createProduct($productData, $arrayRules);

        // Option 3: Validation strategy
        $strategy = new ProductBusinessValidationStrategy;
        $product3 = $this->productService->createProduct($productData, $strategy);

        return [$product1, $product2, $product3];
    }
}
