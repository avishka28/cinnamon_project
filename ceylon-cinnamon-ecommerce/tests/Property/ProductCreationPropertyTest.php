<?php
/**
 * Property-Based Tests for Product Creation
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 21: Product creation completeness
 * 
 * For any product created by admin, all required fields (SKU, stock, price, weight, dimensions) 
 * should be stored correctly.
 * 
 * Validates: Requirements 6.1
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class ProductCreationPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Product $productModel;
    private \Category $categoryModel;
    private bool $dbAvailable = false;
    private array $testCategoryIds = [];
    private array $testProductIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../models/Product.php';
            
            $this->db = \Database::getInstance();
            $this->productModel = new \Product();
            $this->categoryModel = new \Category();
            $this->dbAvailable = true;
            $this->setupTestCategories();
        } catch (\Exception $e) {
            $this->dbAvailable = false;
        }
    }

    protected function tearDown(): void
    {
        if ($this->dbAvailable) {
            $this->cleanupTestData();
            \Database::closeConnection();
        }
        parent::tearDown();
    }

    private function setupTestCategories(): void
    {
        // Create test category
        $categoryData = [
            'name' => 'Test Category ' . uniqid(),
            'slug' => 'test-category-' . uniqid()
        ];
        $id = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $id;
    }

    private function cleanupTestData(): void
    {
        // Delete test products
        foreach ($this->testProductIds as $id) {
            try {
                $this->productModel->delete($id);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
        // Delete test categories
        foreach ($this->testCategoryIds as $id) {
            try {
                $this->categoryModel->delete($id);
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 21: Product creation completeness
     * 
     * For any product created by admin, all required fields (SKU, stock, price, weight, dimensions) 
     * should be stored correctly.
     * 
     * Validates: Requirements 6.1
     */
    public function testProductCreationCompleteness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) >= 3 && strlen($s) <= 20,
                    Generator\string()
                ),
                Generator\suchThat(
                    fn($s) => strlen($s) >= 3 && strlen($s) <= 50,
                    Generator\string()
                ),
                Generator\choose(1, 100000),  // price in cents
                Generator\choose(0, 1000),    // stock quantity
                Generator\choose(1, 10000),   // weight in grams
                Generator\suchThat(
                    fn($s) => strlen($s) <= 50,
                    Generator\string()
                )
            )
            ->then(function (
                string $skuSuffix,
                string $nameSuffix,
                int $priceCents,
                int $stockQuantity,
                int $weightGrams,
                string $dimensions
            ) use ($categoryId): void {
                // Generate unique SKU and name
                $sku = 'TEST-' . uniqid() . '-' . preg_replace('/[^a-zA-Z0-9]/', '', $skuSuffix);
                $name = 'Test Product ' . preg_replace('/[^a-zA-Z0-9 ]/', '', $nameSuffix);
                $price = $priceCents / 100;
                $weight = $weightGrams / 1000;
                $dimensionsClean = preg_replace('/[^a-zA-Z0-9x\s]/', '', $dimensions);

                $productData = [
                    'sku' => $sku,
                    'name' => $name,
                    'price' => $price,
                    'stock_quantity' => $stockQuantity,
                    'weight' => $weight,
                    'dimensions' => $dimensionsClean,
                    'category_id' => $categoryId,
                    'is_active' => 1
                ];

                // Create product
                $productId = $this->productModel->createProduct($productData);
                $this->testProductIds[] = $productId;

                // Retrieve the created product
                $createdProduct = $this->productModel->find($productId);

                // Verify all required fields are stored correctly
                $this->assertNotNull($createdProduct, 'Product should be created and retrievable');
                
                // SKU should be stored correctly
                $this->assertEquals(
                    $sku,
                    $createdProduct['sku'],
                    'SKU should be stored correctly'
                );

                // Name should be stored correctly
                $this->assertEquals(
                    $name,
                    $createdProduct['name'],
                    'Name should be stored correctly'
                );

                // Price should be stored correctly (with floating point tolerance)
                $this->assertEqualsWithDelta(
                    $price,
                    (float) $createdProduct['price'],
                    0.01,
                    'Price should be stored correctly'
                );

                // Stock quantity should be stored correctly
                $this->assertEquals(
                    $stockQuantity,
                    (int) $createdProduct['stock_quantity'],
                    'Stock quantity should be stored correctly'
                );

                // Weight should be stored correctly (with floating point tolerance)
                if ($weight > 0) {
                    $this->assertEqualsWithDelta(
                        $weight,
                        (float) $createdProduct['weight'],
                        0.001,
                        'Weight should be stored correctly'
                    );
                }

                // Dimensions should be stored correctly
                $this->assertEquals(
                    $dimensionsClean,
                    $createdProduct['dimensions'] ?? '',
                    'Dimensions should be stored correctly'
                );

                // Category ID should be stored correctly
                $this->assertEquals(
                    $categoryId,
                    (int) $createdProduct['category_id'],
                    'Category ID should be stored correctly'
                );
            });
    }

    /**
     * Test that SKU uniqueness is enforced
     * 
     * For any two products with the same SKU, the second creation should fail.
     */
    public function testSkuUniquenessEnforced(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];
        $sku = 'UNIQUE-TEST-' . uniqid();

        // Create first product
        $productData = [
            'sku' => $sku,
            'name' => 'First Product',
            'price' => 10.00,
            'category_id' => $categoryId
        ];

        $productId = $this->productModel->createProduct($productData);
        $this->testProductIds[] = $productId;

        // Attempt to create second product with same SKU should fail
        $this->expectException(\Exception::class);
        
        $duplicateData = [
            'sku' => $sku,
            'name' => 'Second Product',
            'price' => 20.00,
            'category_id' => $categoryId
        ];

        $this->productModel->createProduct($duplicateData);
    }

    /**
     * Test that required fields validation works
     * 
     * For any product missing required fields, creation should fail.
     */
    public function testRequiredFieldsValidation(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        // Missing SKU
        $this->expectException(\InvalidArgumentException::class);
        $this->productModel->createProduct([
            'name' => 'Test Product',
            'price' => 10.00,
            'category_id' => $categoryId
        ]);
    }

    /**
     * Test that price validation works
     * 
     * For any product with invalid price, creation should fail.
     */
    public function testPriceValidation(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        // Negative price
        $this->expectException(\InvalidArgumentException::class);
        $this->productModel->createProduct([
            'sku' => 'INVALID-PRICE-' . uniqid(),
            'name' => 'Test Product',
            'price' => -10.00,
            'category_id' => $categoryId
        ]);
    }

    /**
     * Test that sale price must be less than regular price
     */
    public function testSalePriceValidation(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        $this->limitTo(20)
            ->forAll(
                Generator\choose(100, 10000),  // regular price in cents
                Generator\choose(0, 99)        // sale price percentage (0-99% of regular)
            )
            ->then(function (int $regularPriceCents, int $salePercentage) use ($categoryId): void {
                $regularPrice = $regularPriceCents / 100;
                $salePrice = $regularPrice * ($salePercentage / 100);

                $productData = [
                    'sku' => 'SALE-TEST-' . uniqid(),
                    'name' => 'Sale Test Product',
                    'price' => $regularPrice,
                    'sale_price' => $salePrice,
                    'category_id' => $categoryId
                ];

                $productId = $this->productModel->createProduct($productData);
                $this->testProductIds[] = $productId;

                $createdProduct = $this->productModel->find($productId);

                // Sale price should be less than regular price
                if ($createdProduct['sale_price'] !== null) {
                    $this->assertLessThan(
                        (float) $createdProduct['price'],
                        (float) $createdProduct['sale_price'],
                        'Sale price should be less than regular price'
                    );
                }
            });
    }
}
