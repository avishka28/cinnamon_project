<?php
/**
 * Property-Based Tests for Product Filtering
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 1: Category filtering correctness
 * Property 2: Price range filtering correctness
 * Property 3: Origin filtering correctness
 * Property 4: Organic filtering correctness
 * 
 * Validates: Requirements 1.2, 1.3, 1.4, 1.5
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class ProductFilteringPropertyTest extends TestCase
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
            $this->setupTestData();
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


    private function setupTestData(): void
    {
        // Create test categories
        $categories = [
            ['name' => 'Test Cinnamon Sticks', 'slug' => 'test-cinnamon-sticks-' . uniqid()],
            ['name' => 'Test Cinnamon Powder', 'slug' => 'test-cinnamon-powder-' . uniqid()],
            ['name' => 'Test Cinnamon Oil', 'slug' => 'test-cinnamon-oil-' . uniqid()],
        ];

        foreach ($categories as $cat) {
            $id = $this->categoryModel->createCategory($cat);
            $this->testCategoryIds[] = $id;
        }

        // Create test products with various attributes
        $products = [
            ['sku' => 'TEST-001-' . uniqid(), 'name' => 'Test Organic Ceylon Sticks', 'price' => 15.99, 'category_id' => $this->testCategoryIds[0], 'is_organic' => 1, 'origin' => 'Sri Lanka'],
            ['sku' => 'TEST-002-' . uniqid(), 'name' => 'Test Regular Ceylon Sticks', 'price' => 12.99, 'category_id' => $this->testCategoryIds[0], 'is_organic' => 0, 'origin' => 'Sri Lanka'],
            ['sku' => 'TEST-003-' . uniqid(), 'name' => 'Test Organic Powder', 'price' => 8.99, 'category_id' => $this->testCategoryIds[1], 'is_organic' => 1, 'origin' => 'Sri Lanka'],
            ['sku' => 'TEST-004-' . uniqid(), 'name' => 'Test Indonesian Powder', 'price' => 6.99, 'category_id' => $this->testCategoryIds[1], 'is_organic' => 0, 'origin' => 'Indonesia'],
            ['sku' => 'TEST-005-' . uniqid(), 'name' => 'Test Premium Oil', 'price' => 45.99, 'category_id' => $this->testCategoryIds[2], 'is_organic' => 1, 'origin' => 'Sri Lanka'],
            ['sku' => 'TEST-006-' . uniqid(), 'name' => 'Test Budget Oil', 'price' => 25.99, 'category_id' => $this->testCategoryIds[2], 'is_organic' => 0, 'origin' => 'Vietnam'],
        ];

        foreach ($products as $product) {
            $id = $this->productModel->createProduct($product);
            $this->testProductIds[] = $id;
        }
    }

    private function cleanupTestData(): void
    {
        // Delete test products
        foreach ($this->testProductIds as $id) {
            $this->productModel->delete($id);
        }
        
        // Delete test categories
        foreach ($this->testCategoryIds as $id) {
            $this->categoryModel->delete($id);
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 1: Category filtering correctness
     * 
     * For any product catalog and category filter, all returned products 
     * should belong to the specified category.
     * 
     * Validates: Requirements 1.2
     */
    public function testCategoryFilteringCorrectness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testCategoryIds)
            )
            ->then(function (int $categoryId): void {
                // Apply category filter
                $result = $this->productModel->getFiltered(['category_id' => $categoryId]);
                
                // Verify all returned products belong to the specified category
                foreach ($result['products'] as $product) {
                    $this->assertEquals(
                        $categoryId,
                        (int) $product['category_id'],
                        "Product '{$product['name']}' should belong to category {$categoryId}"
                    );
                }
            });
    }


    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 2: Price range filtering correctness
     * 
     * For any product catalog and price range filter, all returned products 
     * should have prices within the specified range.
     * 
     * Validates: Requirements 1.3
     */
    public function testPriceRangeFilteringCorrectness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\choose(0, 50),  // price_min
                Generator\choose(0, 100)  // price_max offset
            )
            ->then(function (int $priceMin, int $priceMaxOffset): void {
                $priceMax = $priceMin + $priceMaxOffset;
                
                // Apply price range filter
                $result = $this->productModel->getFiltered([
                    'price_min' => (float) $priceMin,
                    'price_max' => (float) $priceMax
                ]);
                
                // Verify all returned products have prices within the range
                foreach ($result['products'] as $product) {
                    $price = (float) $product['price'];
                    
                    $this->assertGreaterThanOrEqual(
                        $priceMin,
                        $price,
                        "Product '{$product['name']}' price {$price} should be >= {$priceMin}"
                    );
                    
                    $this->assertLessThanOrEqual(
                        $priceMax,
                        $price,
                        "Product '{$product['name']}' price {$price} should be <= {$priceMax}"
                    );
                }
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 3: Origin filtering correctness
     * 
     * For any product catalog and origin filter, all returned products 
     * should match the specified origin.
     * 
     * Validates: Requirements 1.4
     */
    public function testOriginFilteringCorrectness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements('Sri Lanka', 'Indonesia', 'Vietnam')
            )
            ->then(function (string $origin): void {
                // Apply origin filter
                $result = $this->productModel->getFiltered(['origin' => $origin]);
                
                // Verify all returned products match the specified origin
                foreach ($result['products'] as $product) {
                    $this->assertEquals(
                        $origin,
                        $product['origin'],
                        "Product '{$product['name']}' should have origin '{$origin}'"
                    );
                }
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 4: Organic filtering correctness
     * 
     * For any product catalog and organic filter, all returned products 
     * should be marked as organic.
     * 
     * Validates: Requirements 1.5
     */
    public function testOrganicFilteringCorrectness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(0, 1)
            )
            ->then(function (int $isOrganic): void {
                // Apply organic filter
                $result = $this->productModel->getFiltered(['is_organic' => $isOrganic]);
                
                // Verify all returned products match the organic status
                foreach ($result['products'] as $product) {
                    $this->assertEquals(
                        $isOrganic,
                        (int) $product['is_organic'],
                        "Product '{$product['name']}' organic status should be {$isOrganic}"
                    );
                }
            });
    }

    /**
     * Test combined filters work correctly together.
     * 
     * For any combination of filters, all returned products should satisfy
     * ALL filter conditions simultaneously.
     */
    public function testCombinedFiltersCorrectness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testCategoryIds),
                Generator\choose(0, 30),
                Generator\elements(0, 1)
            )
            ->then(function (int $categoryId, int $priceMin, int $isOrganic): void {
                // Apply combined filters
                $result = $this->productModel->getFiltered([
                    'category_id' => $categoryId,
                    'price_min' => (float) $priceMin,
                    'is_organic' => $isOrganic
                ]);
                
                // Verify all returned products satisfy ALL conditions
                foreach ($result['products'] as $product) {
                    $this->assertEquals(
                        $categoryId,
                        (int) $product['category_id'],
                        "Product should belong to category {$categoryId}"
                    );
                    
                    $this->assertGreaterThanOrEqual(
                        $priceMin,
                        (float) $product['price'],
                        "Product price should be >= {$priceMin}"
                    );
                    
                    $this->assertEquals(
                        $isOrganic,
                        (int) $product['is_organic'],
                        "Product organic status should be {$isOrganic}"
                    );
                }
            });
    }
}
