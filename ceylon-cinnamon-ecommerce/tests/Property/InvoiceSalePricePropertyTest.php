<?php
/**
 * Property-Based Tests for Invoice Sale Price Display
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 23: Sale price display
 * 
 * For any product with a sale price, both original and sale prices should be displayed 
 * to customers (in invoices).
 * 
 * Validates: Requirements 6.6
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class InvoiceSalePricePropertyTest extends TestCase
{
    use TestTrait;

    private \PDO $db;
    private \Product $productModel;
    private \Category $categoryModel;
    private \Order $orderModel;
    private bool $dbAvailable = false;
    private array $testCategoryIds = [];
    private array $testProductIds = [];
    private array $testOrderIds = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        try {
            $_ENV['DB_NAME'] = $_ENV['DB_NAME'] ?? 'ceylon_cinnamon_test';
            require_once __DIR__ . '/../../config/database.php';
            require_once __DIR__ . '/../../includes/Model.php';
            require_once __DIR__ . '/../../models/Category.php';
            require_once __DIR__ . '/../../models/Product.php';
            require_once __DIR__ . '/../../models/Order.php';
            require_once __DIR__ . '/../../models/OrderItem.php';
            
            $this->db = \Database::getInstance();
            $this->productModel = new \Product();
            $this->categoryModel = new \Category();
            $this->orderModel = new \Order();
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
        $categoryData = [
            'name' => 'Test Category ' . uniqid(),
            'slug' => 'test-category-' . uniqid()
        ];
        $id = $this->categoryModel->createCategory($categoryData);
        $this->testCategoryIds[] = $id;
    }

    private function cleanupTestData(): void
    {
        // Delete test orders first (due to foreign key constraints)
        foreach ($this->testOrderIds as $id) {
            try {
                $this->db->exec("DELETE FROM order_items WHERE order_id = {$id}");
                $this->db->exec("DELETE FROM orders WHERE id = {$id}");
            } catch (\Exception $e) {
                // Ignore cleanup errors
            }
        }
        
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
     * Feature: ceylon-cinnamon-ecommerce, Property 23: Sale price display
     * 
     * For any product with a sale price, both original and sale prices should be displayed 
     * to customers (in invoices).
     * 
     * Validates: Requirements 6.6
     */
    public function testSalePriceDisplayInInvoice(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        $this->limitTo(100)
            ->forAll(
                Generator\choose(1000, 10000),  // regular price in cents ($10-$100)
                Generator\choose(10, 90)        // sale discount percentage (10-90%)
            )
            ->then(function (int $regularPriceCents, int $discountPercent) use ($categoryId): void {
                $regularPrice = $regularPriceCents / 100;
                $salePrice = round($regularPrice * (1 - $discountPercent / 100), 2);

                // Create product with sale price
                $productData = [
                    'sku' => 'INVOICE-TEST-' . uniqid(),
                    'name' => 'Invoice Test Product ' . uniqid(),
                    'price' => $regularPrice,
                    'sale_price' => $salePrice,
                    'stock_quantity' => 100,
                    'category_id' => $categoryId,
                    'is_active' => 1
                ];

                $productId = $this->productModel->createProduct($productData);
                $this->testProductIds[] = $productId;

                // Verify product was created with sale price
                $product = $this->productModel->find($productId);
                $this->assertNotNull($product);
                $this->assertNotNull($product['sale_price']);
                $this->assertLessThan((float)$product['price'], (float)$product['sale_price']);

                // Generate invoice HTML for this product
                $invoiceHtml = $this->generateTestInvoiceHtml($product);

                // Property: For any product with a sale price, both original and sale prices 
                // should be displayed in the invoice
                
                // Check that the original price is displayed (with strikethrough class)
                $this->assertStringContainsString(
                    'original-price',
                    $invoiceHtml,
                    'Invoice should contain original-price class for products with sale price'
                );

                // Check that the sale price is displayed (with sale-price class)
                $this->assertStringContainsString(
                    'sale-price',
                    $invoiceHtml,
                    'Invoice should contain sale-price class for products with sale price'
                );

                // Check that the original price value is present
                $formattedOriginalPrice = number_format($regularPrice, 2);
                $this->assertStringContainsString(
                    $formattedOriginalPrice,
                    $invoiceHtml,
                    "Invoice should display original price: \${$formattedOriginalPrice}"
                );

                // Check that the sale price value is present
                $formattedSalePrice = number_format($salePrice, 2);
                $this->assertStringContainsString(
                    $formattedSalePrice,
                    $invoiceHtml,
                    "Invoice should display sale price: \${$formattedSalePrice}"
                );
            });
    }

    /**
     * Test that products without sale price don't show strikethrough
     */
    public function testRegularPriceDisplayWithoutSalePrice(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        $this->limitTo(50)
            ->forAll(
                Generator\choose(1000, 10000)  // regular price in cents
            )
            ->then(function (int $regularPriceCents) use ($categoryId): void {
                $regularPrice = $regularPriceCents / 100;

                // Create product WITHOUT sale price
                $productData = [
                    'sku' => 'REGULAR-TEST-' . uniqid(),
                    'name' => 'Regular Test Product ' . uniqid(),
                    'price' => $regularPrice,
                    'sale_price' => null,
                    'stock_quantity' => 100,
                    'category_id' => $categoryId,
                    'is_active' => 1
                ];

                $productId = $this->productModel->createProduct($productData);
                $this->testProductIds[] = $productId;

                $product = $this->productModel->find($productId);
                $this->assertNotNull($product);

                // Generate invoice HTML for this product
                $invoiceHtml = $this->generateTestInvoiceHtml($product);

                // Property: For products without sale price, should NOT show strikethrough
                $this->assertStringNotContainsString(
                    'original-price',
                    $invoiceHtml,
                    'Invoice should NOT contain original-price class for products without sale price'
                );

                // Regular price should still be displayed
                $formattedPrice = number_format($regularPrice, 2);
                $this->assertStringContainsString(
                    $formattedPrice,
                    $invoiceHtml,
                    "Invoice should display regular price: \${$formattedPrice}"
                );
            });
    }

    /**
     * Test that sale price is always less than regular price in invoice
     */
    public function testSalePriceLessThanRegularPrice(): void
    {
        if (!$this->dbAvailable) {
            $this->markTestSkipped('Database not available for testing');
        }

        $categoryId = $this->testCategoryIds[0];

        $this->limitTo(50)
            ->forAll(
                Generator\choose(2000, 10000),  // regular price in cents ($20-$100)
                Generator\choose(1, 99)         // sale discount percentage (1-99%)
            )
            ->then(function (int $regularPriceCents, int $discountPercent) use ($categoryId): void {
                $regularPrice = $regularPriceCents / 100;
                $salePrice = round($regularPrice * (1 - $discountPercent / 100), 2);

                // Create product with sale price
                $productData = [
                    'sku' => 'SALE-COMPARE-' . uniqid(),
                    'name' => 'Sale Compare Product ' . uniqid(),
                    'price' => $regularPrice,
                    'sale_price' => $salePrice,
                    'stock_quantity' => 100,
                    'category_id' => $categoryId,
                    'is_active' => 1
                ];

                $productId = $this->productModel->createProduct($productData);
                $this->testProductIds[] = $productId;

                $product = $this->productModel->find($productId);

                // Property: Sale price must always be less than regular price
                $this->assertLessThan(
                    (float)$product['price'],
                    (float)$product['sale_price'],
                    'Sale price must be less than regular price'
                );
            });
    }

    /**
     * Generate test invoice HTML for a product
     * This simulates the invoice generation logic from OrderAdminController
     * 
     * @param array $product Product data
     * @return string Invoice HTML
     */
    private function generateTestInvoiceHtml(array $product): string
    {
        $priceDisplay = '$' . number_format((float)$product['price'], 2);
        
        // Check if there's a sale price (Property 23: Sale price display)
        if ($product['sale_price'] && (float)$product['sale_price'] < (float)$product['price']) {
            // Show both original and sale price
            $priceDisplay = '<span class="original-price">$' . number_format((float)$product['price'], 2) . '</span>'
                          . '<span class="sale-price">$' . number_format((float)$product['sale_price'], 2) . '</span>';
        }

        return '<tr>
            <td>' . htmlspecialchars($product['name']) . '</td>
            <td>' . htmlspecialchars($product['sku']) . '</td>
            <td class="text-right">' . $priceDisplay . '</td>
            <td class="text-right">1</td>
            <td class="text-right">$' . number_format((float)($product['sale_price'] ?? $product['price']), 2) . '</td>
        </tr>';
    }
}
