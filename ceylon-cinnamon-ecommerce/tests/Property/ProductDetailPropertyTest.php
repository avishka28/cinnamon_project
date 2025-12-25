<?php
/**
 * Property-Based Tests for Product Detail Completeness
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 5: Product detail completeness
 * 
 * Validates: Requirements 1.6
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class ProductDetailPropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Product $productModel;
    private \Category $categoryModel;
    private bool $dbAvailable = false;
    private int $testCategoryId = 0;
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
        // Create test category
        $this->testCategoryId = $this->categoryModel->createCategory([
            'name' => 'Test Category Detail',
            'slug' => 'test-category-detail-' . uniqid()
        ]);

        // Create test products with full details
        $products = [
            [
                'sku' => 'TEST-DETAIL-001-' . uniqid(),
                'name' => 'Test Product With Full Details',
                'description' => 'This is a detailed description of the product.',
                'short_description' => 'Short description here.',
                'price' => 29.99,
                'weight' => 0.5,
                'dimensions' => '10x5x3 cm',
                'stock_quantity' => 100,
                'category_id' => $this->testCategoryId,
                'is_organic' => 1,
                'origin' => 'Sri Lanka',
                'meta_title' => 'Test Product SEO Title',
                'meta_description' => 'Test product meta description for SEO.'
            ],
            [
                'sku' => 'TEST-DETAIL-002-' . uniqid(),
                'name' => 'Test Product Minimal',
                'price' => 19.99,
                'category_id' => $this->testCategoryId
            ]
        ];

        foreach ($products as $product) {
            $id = $this->productModel->createProduct($product);
            $this->testProductIds[] = $id;
            
            // Add test images for first product
            if (count($this->testProductIds) === 1) {
                $this->productModel->addImage($id, [
                    'image_url' => 'test-image-1.jpg',
                    'alt_text' => 'Test Image 1',
                    'is_primary' => 1,
                    'sort_order' => 0
                ]);
                $this->productModel->addImage($id, [
                    'image_url' => 'test-image-2.jpg',
                    'alt_text' => 'Test Image 2',
                    'is_primary' => 0,
                    'sort_order' => 1
                ]);
            }
        }
    }

    private function cleanupTestData(): void
    {
        // Delete test product images
        foreach ($this->testProductIds as $id) {
            $this->db->prepare("DELETE FROM product_images WHERE product_id = :id")
                     ->execute(['id' => $id]);
        }
        
        // Delete test products
        foreach ($this->testProductIds as $id) {
            $this->productModel->delete($id);
        }
        
        // Delete test category
        if ($this->testCategoryId > 0) {
            $this->categoryModel->delete($this->testCategoryId);
        }
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 5: Product detail completeness
     * 
     * For any product detail page, the rendered content should include
     * images, descriptions, specifications, and related products.
     * 
     * Validates: Requirements 1.6
     */
    public function testProductDetailCompleteness(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds)
            )
            ->then(function (int $productId): void {
                // Get full product details
                $product = $this->productModel->getFullDetails($productId);
                
                // Verify product exists
                $this->assertNotNull($product, "Product {$productId} should exist");
                
                // Verify required fields are present (Requirement 1.6)
                $requiredFields = [
                    'id', 'sku', 'name', 'price', 'category_id',
                    'images', 'reviews', 'average_rating', 'review_count'
                ];
                
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $product,
                        "Product should have '{$field}' field"
                    );
                }
                
                // Verify images is an array
                $this->assertIsArray(
                    $product['images'],
                    "Product images should be an array"
                );
                
                // Verify reviews is an array
                $this->assertIsArray(
                    $product['reviews'],
                    "Product reviews should be an array"
                );
                
                // Verify average_rating is numeric
                $this->assertIsFloat(
                    $product['average_rating'],
                    "Average rating should be a float"
                );
                
                // Verify review_count is numeric
                $this->assertIsInt(
                    $product['review_count'],
                    "Review count should be an integer"
                );
                
                // Verify category info is included
                $this->assertArrayHasKey(
                    'category_name',
                    $product,
                    "Product should have category_name"
                );
            });
    }


    /**
     * Test that product images are properly ordered with primary first.
     */
    public function testProductImagesOrdering(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        // Test with the product that has images
        $productId = $this->testProductIds[0];
        $images = $this->productModel->getImages($productId);
        
        if (count($images) > 1) {
            // First image should be primary
            $this->assertEquals(
                1,
                (int) $images[0]['is_primary'],
                "First image should be the primary image"
            );
            
            // Verify sort order is respected
            $previousOrder = -1;
            foreach ($images as $image) {
                if ((int) $image['is_primary'] === 0) {
                    $this->assertGreaterThanOrEqual(
                        $previousOrder,
                        (int) $image['sort_order'],
                        "Images should be ordered by sort_order"
                    );
                    $previousOrder = (int) $image['sort_order'];
                }
            }
        }
    }

    /**
     * Test that related products are from the same category.
     */
    public function testRelatedProductsSameCategory(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds)
            )
            ->then(function (int $productId): void {
                $product = $this->productModel->find($productId);
                $relatedProducts = $this->productModel->getRelated($productId);
                
                // Verify related products are from the same category
                foreach ($relatedProducts as $related) {
                    $this->assertEquals(
                        $product['category_id'],
                        $related['category_id'],
                        "Related product should be from the same category"
                    );
                    
                    // Verify related product is not the same as current product
                    $this->assertNotEquals(
                        $productId,
                        (int) $related['id'],
                        "Related product should not be the same as current product"
                    );
                }
            });
    }

    /**
     * Test that product specifications are properly formatted.
     */
    public function testProductSpecificationsFormat(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds)
            )
            ->then(function (int $productId): void {
                $product = $this->productModel->getFullDetails($productId);
                
                // SKU should be a non-empty string
                $this->assertNotEmpty(
                    $product['sku'],
                    "Product SKU should not be empty"
                );
                
                // Price should be positive
                $this->assertGreaterThan(
                    0,
                    (float) $product['price'],
                    "Product price should be positive"
                );
                
                // If weight is set, it should be positive
                if ($product['weight'] !== null) {
                    $this->assertGreaterThanOrEqual(
                        0,
                        (float) $product['weight'],
                        "Product weight should be non-negative"
                    );
                }
                
                // Stock quantity should be non-negative
                $this->assertGreaterThanOrEqual(
                    0,
                    (int) $product['stock_quantity'],
                    "Stock quantity should be non-negative"
                );
            });
    }

    /**
     * Test that average rating is within valid range.
     */
    public function testAverageRatingRange(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $this->limitTo(10)
            ->forAll(
                Generator\elements(...$this->testProductIds)
            )
            ->then(function (int $productId): void {
                $avgRating = $this->productModel->getAverageRating($productId);
                
                // Average rating should be between 0 and 5
                $this->assertGreaterThanOrEqual(
                    0,
                    $avgRating,
                    "Average rating should be >= 0"
                );
                
                $this->assertLessThanOrEqual(
                    5,
                    $avgRating,
                    "Average rating should be <= 5"
                );
            });
    }
}
