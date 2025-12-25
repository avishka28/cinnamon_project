<?php
/**
 * Property-Based Tests for SEO Features
 * 
 * Feature: ceylon-cinnamon-ecommerce
 * Property 29: Meta tag completeness
 * Property 30: Structured data inclusion
 * 
 * Validates: Requirements 11.1, 11.2
 */

declare(strict_types=1);

namespace Tests\Property;

use PHPUnit\Framework\TestCase;
use Eris\Generator;
use Eris\TestTrait;

class SeoPropertyTest extends TestCase
{
    use TestTrait;

    private \SeoHelper $seoHelper;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Define required constants if not already defined
        if (!defined('APP_NAME')) {
            define('APP_NAME', 'Ceylon Cinnamon');
        }
        if (!defined('APP_URL')) {
            define('APP_URL', 'https://ceyloncinnamon.com');
        }
        
        require_once __DIR__ . '/../../includes/SeoHelper.php';
        
        $this->seoHelper = new \SeoHelper();
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 29: Meta tag completeness
     * 
     * For any page load, appropriate meta tags and Open Graph data should be included.
     * 
     * Validates: Requirements 11.1
     */
    public function testMetaTagCompleteness(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 100,
                    Generator\string()
                ),
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 200,
                    Generator\string()
                ),
                Generator\elements('website', 'product', 'article')
            )
            ->then(function (string $title, string $description, string $ogType): void {
                // Configure SEO with random data
                $seo = new \SeoHelper();
                $seo->setTitle($title)
                    ->setDescription($description)
                    ->setOgType($ogType)
                    ->setCanonicalUrl(APP_URL . '/test-page');
                
                $metaTags = $seo->generateMetaTags();
                
                // Verify meta description is present
                $this->assertStringContainsString(
                    'name="description"',
                    $metaTags,
                    'Meta description tag should be present'
                );
                
                // Verify Open Graph site name is present
                $this->assertStringContainsString(
                    'property="og:site_name"',
                    $metaTags,
                    'Open Graph site name should be present'
                );
                
                // Verify Open Graph title is present
                $this->assertStringContainsString(
                    'property="og:title"',
                    $metaTags,
                    'Open Graph title should be present'
                );
                
                // Verify Open Graph description is present
                $this->assertStringContainsString(
                    'property="og:description"',
                    $metaTags,
                    'Open Graph description should be present'
                );
                
                // Verify Open Graph type is present
                $this->assertStringContainsString(
                    'property="og:type"',
                    $metaTags,
                    'Open Graph type should be present'
                );
                
                // Verify Open Graph URL is present
                $this->assertStringContainsString(
                    'property="og:url"',
                    $metaTags,
                    'Open Graph URL should be present'
                );
                
                // Verify Open Graph image is present
                $this->assertStringContainsString(
                    'property="og:image"',
                    $metaTags,
                    'Open Graph image should be present'
                );
                
                // Verify Twitter card is present
                $this->assertStringContainsString(
                    'name="twitter:card"',
                    $metaTags,
                    'Twitter card should be present'
                );
                
                // Verify canonical URL is present
                $this->assertStringContainsString(
                    'rel="canonical"',
                    $metaTags,
                    'Canonical URL should be present'
                );
            });
    }

    /**
     * Feature: ceylon-cinnamon-ecommerce, Property 30: Structured data inclusion
     * 
     * For any product page, JSON-LD structured data should be included.
     * 
     * Validates: Requirements 11.2
     */
    public function testStructuredDataInclusion(): void
    {
        $this->limitTo(100)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 100,
                    Generator\string()
                ),
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 50,
                    Generator\string()
                ),
                Generator\choose(1, 10000),  // price in cents
                Generator\choose(0, 100),     // stock quantity
                Generator\elements('Sri Lanka', 'Indonesia', 'Vietnam')
            )
            ->then(function (string $name, string $sku, int $priceCents, int $stock, string $origin): void {
                // Create a mock product
                $product = [
                    'id' => 1,
                    'name' => $name,
                    'slug' => 'test-product-' . uniqid(),
                    'sku' => $sku,
                    'description' => 'Test product description',
                    'short_description' => 'Short description',
                    'price' => $priceCents / 100,
                    'sale_price' => null,
                    'stock_quantity' => $stock,
                    'origin' => $origin,
                    'category_name' => 'Test Category',
                    'weight' => 0.5,
                    'images' => [],
                    'review_count' => 0,
                    'average_rating' => 0
                ];
                
                // Configure SEO for product
                $seo = new \SeoHelper();
                $seo->configureForProduct($product);
                
                $structuredData = $seo->generateStructuredData();
                
                // Verify JSON-LD script tag is present
                $this->assertStringContainsString(
                    'application/ld+json',
                    $structuredData,
                    'JSON-LD script tag should be present'
                );
                
                // Verify @context is present
                $this->assertStringContainsString(
                    '"@context"',
                    $structuredData,
                    'JSON-LD @context should be present'
                );
                
                // Verify @type Product is present
                $this->assertStringContainsString(
                    '"@type"',
                    $structuredData,
                    'JSON-LD @type should be present'
                );
                
                // Verify product name is in structured data
                $this->assertStringContainsString(
                    '"name"',
                    $structuredData,
                    'Product name should be in structured data'
                );
                
                // Verify offers section is present
                $this->assertStringContainsString(
                    '"offers"',
                    $structuredData,
                    'Offers section should be present in structured data'
                );
                
                // Verify price is present
                $this->assertStringContainsString(
                    '"price"',
                    $structuredData,
                    'Price should be in structured data'
                );
                
                // Verify availability is present
                $this->assertStringContainsString(
                    '"availability"',
                    $structuredData,
                    'Availability should be in structured data'
                );
                
                // Verify correct availability based on stock
                if ($stock > 0) {
                    $this->assertStringContainsString(
                        'InStock',
                        $structuredData,
                        'Product with stock should show InStock'
                    );
                } else {
                    $this->assertStringContainsString(
                        'OutOfStock',
                        $structuredData,
                        'Product without stock should show OutOfStock'
                    );
                }
            });
    }

    /**
     * Test that meta description is properly truncated
     */
    public function testMetaDescriptionTruncation(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) > 200,
                    Generator\string()
                )
            )
            ->then(function (string $longDescription): void {
                $seo = new \SeoHelper();
                $seo->setDescription($longDescription);
                
                $metaTags = $seo->generateMetaTags();
                
                // Extract description content from meta tag
                preg_match('/name="description" content="([^"]*)"/', $metaTags, $matches);
                
                if (!empty($matches[1])) {
                    // Description should be truncated to 160 characters or less
                    $this->assertLessThanOrEqual(
                        163, // 160 + 3 for "..."
                        strlen($matches[1]),
                        'Meta description should be truncated to 160 characters'
                    );
                }
            });
    }

    /**
     * Test that home page SEO includes organization structured data
     */
    public function testHomePageStructuredData(): void
    {
        $seo = new \SeoHelper();
        $seo->configureForHome();
        
        $structuredData = $seo->generateStructuredData();
        
        // Verify Organization structured data is present
        $this->assertStringContainsString(
            '"@type": "Organization"',
            $structuredData,
            'Organization structured data should be present on home page'
        );
        
        // Verify WebSite structured data is present
        $this->assertStringContainsString(
            '"@type": "WebSite"',
            $structuredData,
            'WebSite structured data should be present on home page'
        );
        
        // Verify search action is present
        $this->assertStringContainsString(
            '"SearchAction"',
            $structuredData,
            'SearchAction should be present in WebSite structured data'
        );
    }

    /**
     * Test that blog post SEO includes article structured data
     */
    public function testBlogPostStructuredData(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 100,
                    Generator\string()
                ),
                Generator\suchThat(
                    fn($s) => strlen($s) > 0 && strlen($s) <= 200,
                    Generator\string()
                )
            )
            ->then(function (string $title, string $excerpt): void {
                $post = [
                    'id' => 1,
                    'title' => $title,
                    'slug' => 'test-post-' . uniqid(),
                    'excerpt' => $excerpt,
                    'content' => 'Full content here',
                    'featured_image' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                    'published_at' => date('Y-m-d H:i:s')
                ];
                
                $seo = new \SeoHelper();
                $seo->configureForBlogPost($post);
                
                $structuredData = $seo->generateStructuredData();
                
                // Verify Article structured data is present
                $this->assertStringContainsString(
                    '"@type": "Article"',
                    $structuredData,
                    'Article structured data should be present for blog posts'
                );
                
                // Verify headline is present
                $this->assertStringContainsString(
                    '"headline"',
                    $structuredData,
                    'Headline should be present in article structured data'
                );
                
                // Verify datePublished is present
                $this->assertStringContainsString(
                    '"datePublished"',
                    $structuredData,
                    'datePublished should be present in article structured data'
                );
                
                // Verify author is present
                $this->assertStringContainsString(
                    '"author"',
                    $structuredData,
                    'Author should be present in article structured data'
                );
                
                // Verify publisher is present
                $this->assertStringContainsString(
                    '"publisher"',
                    $structuredData,
                    'Publisher should be present in article structured data'
                );
            });
    }

    /**
     * Test that product with reviews includes aggregate rating
     */
    public function testProductWithReviewsIncludesAggregateRating(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\choose(1, 100),  // review count
                Generator\choose(10, 50)   // average rating * 10 (1.0 to 5.0)
            )
            ->then(function (int $reviewCount, int $ratingTimes10): void {
                $averageRating = $ratingTimes10 / 10;
                
                $product = [
                    'id' => 1,
                    'name' => 'Test Product',
                    'slug' => 'test-product',
                    'sku' => 'TEST-001',
                    'description' => 'Test description',
                    'short_description' => 'Short desc',
                    'price' => 19.99,
                    'sale_price' => null,
                    'stock_quantity' => 10,
                    'origin' => 'Sri Lanka',
                    'category_name' => 'Test Category',
                    'weight' => 0.5,
                    'images' => [],
                    'review_count' => $reviewCount,
                    'average_rating' => $averageRating
                ];
                
                $seo = new \SeoHelper();
                $seo->configureForProduct($product);
                
                $structuredData = $seo->generateStructuredData();
                
                // Verify aggregateRating is present
                $this->assertStringContainsString(
                    '"aggregateRating"',
                    $structuredData,
                    'aggregateRating should be present for products with reviews'
                );
                
                // Verify ratingValue is present
                $this->assertStringContainsString(
                    '"ratingValue"',
                    $structuredData,
                    'ratingValue should be present in aggregateRating'
                );
                
                // Verify reviewCount is present
                $this->assertStringContainsString(
                    '"reviewCount"',
                    $structuredData,
                    'reviewCount should be present in aggregateRating'
                );
            });
    }

    /**
     * Test breadcrumb structured data generation
     */
    public function testBreadcrumbStructuredData(): void
    {
        $this->limitTo(50)
            ->forAll(
                Generator\choose(1, 5)  // number of breadcrumb items
            )
            ->then(function (int $itemCount): void {
                $breadcrumbs = [];
                for ($i = 0; $i < $itemCount; $i++) {
                    $breadcrumbs[] = [
                        'name' => 'Category ' . ($i + 1),
                        'slug' => 'category-' . ($i + 1)
                    ];
                }
                
                $seo = new \SeoHelper();
                $structuredData = $seo->buildBreadcrumbStructuredData($breadcrumbs);
                
                // Verify BreadcrumbList type
                $this->assertEquals(
                    'BreadcrumbList',
                    $structuredData['@type'],
                    'Structured data should be BreadcrumbList type'
                );
                
                // Verify itemListElement is present
                $this->assertArrayHasKey(
                    'itemListElement',
                    $structuredData,
                    'itemListElement should be present'
                );
                
                // Verify correct number of items (breadcrumbs + home)
                $this->assertCount(
                    $itemCount + 1, // +1 for home
                    $structuredData['itemListElement'],
                    'Should have correct number of breadcrumb items'
                );
                
                // Verify positions are sequential
                foreach ($structuredData['itemListElement'] as $index => $item) {
                    $this->assertEquals(
                        $index + 1,
                        $item['position'],
                        'Breadcrumb positions should be sequential'
                    );
                }
            });
    }
}
