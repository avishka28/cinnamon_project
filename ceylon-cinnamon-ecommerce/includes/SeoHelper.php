<?php
/**
 * SEO Helper Class
 * Handles meta tag generation and structured data for SEO optimization
 * 
 * Requirements:
 * - 11.1: Include appropriate meta tags and Open Graph data
 * - 11.2: Include JSON-LD structured data for products
 */

declare(strict_types=1);

class SeoHelper
{
    private string $siteName;
    private string $siteUrl;
    private string $defaultImage;
    private string $locale;
    
    private ?string $title = null;
    private ?string $description = null;
    private ?string $keywords = null;
    private ?string $canonicalUrl = null;
    private ?string $ogType = null;
    private ?string $ogImage = null;
    private ?string $twitterCard = null;
    private array $structuredData = [];
    private ?string $robots = null;

    public function __construct()
    {
        $this->siteName = APP_NAME;
        $this->siteUrl = APP_URL;
        $this->defaultImage = APP_URL . '/assets/images/og-default.jpg';
        $this->locale = function_exists('current_language') ? current_language() : 'en';
    }

    /**
     * Set page title
     */
    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set meta description
     */
    public function setDescription(string $description): self
    {
        $this->description = $this->truncate($description, 160);
        return $this;
    }

    /**
     * Set meta keywords
     */
    public function setKeywords(string $keywords): self
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * Set canonical URL
     */
    public function setCanonicalUrl(string $url): self
    {
        $this->canonicalUrl = $url;
        return $this;
    }

    /**
     * Set Open Graph type
     */
    public function setOgType(string $type): self
    {
        $this->ogType = $type;
        return $this;
    }

    /**
     * Set Open Graph image
     */
    public function setOgImage(string $imageUrl): self
    {
        $this->ogImage = $imageUrl;
        return $this;
    }

    /**
     * Set Twitter card type
     */
    public function setTwitterCard(string $cardType): self
    {
        $this->twitterCard = $cardType;
        return $this;
    }

    /**
     * Set robots meta tag
     */
    public function setRobots(string $robots): self
    {
        $this->robots = $robots;
        return $this;
    }

    /**
     * Add structured data (JSON-LD)
     */
    public function addStructuredData(array $data): self
    {
        $this->structuredData[] = $data;
        return $this;
    }

    /**
     * Generate meta tags HTML
     * Requirement 11.1: Include appropriate meta tags and Open Graph data
     */
    public function generateMetaTags(): string
    {
        $tags = [];
        
        // Basic meta tags
        if ($this->description) {
            $tags[] = '<meta name="description" content="' . $this->escape($this->description) . '">';
        }
        
        if ($this->keywords) {
            $tags[] = '<meta name="keywords" content="' . $this->escape($this->keywords) . '">';
        }
        
        if ($this->robots) {
            $tags[] = '<meta name="robots" content="' . $this->escape($this->robots) . '">';
        }
        
        // Canonical URL
        if ($this->canonicalUrl) {
            $tags[] = '<link rel="canonical" href="' . $this->escape($this->canonicalUrl) . '">';
        }
        
        // Open Graph tags
        $tags[] = '<meta property="og:site_name" content="' . $this->escape($this->siteName) . '">';
        $tags[] = '<meta property="og:locale" content="' . $this->escape($this->getOgLocale()) . '">';
        
        if ($this->title) {
            $tags[] = '<meta property="og:title" content="' . $this->escape($this->title) . '">';
        }
        
        if ($this->description) {
            $tags[] = '<meta property="og:description" content="' . $this->escape($this->description) . '">';
        }
        
        $tags[] = '<meta property="og:type" content="' . $this->escape($this->ogType ?? 'website') . '">';
        
        if ($this->canonicalUrl) {
            $tags[] = '<meta property="og:url" content="' . $this->escape($this->canonicalUrl) . '">';
        }
        
        $ogImage = $this->ogImage ?? $this->defaultImage;
        $tags[] = '<meta property="og:image" content="' . $this->escape($ogImage) . '">';
        
        // Twitter Card tags
        $tags[] = '<meta name="twitter:card" content="' . $this->escape($this->twitterCard ?? 'summary_large_image') . '">';
        
        if ($this->title) {
            $tags[] = '<meta name="twitter:title" content="' . $this->escape($this->title) . '">';
        }
        
        if ($this->description) {
            $tags[] = '<meta name="twitter:description" content="' . $this->escape($this->description) . '">';
        }
        
        $tags[] = '<meta name="twitter:image" content="' . $this->escape($ogImage) . '">';
        
        return implode("\n    ", $tags);
    }

    /**
     * Generate structured data JSON-LD
     * Requirement 11.2: Include JSON-LD structured data for products
     */
    public function generateStructuredData(): string
    {
        if (empty($this->structuredData)) {
            return '';
        }
        
        $scripts = [];
        foreach ($this->structuredData as $data) {
            $scripts[] = '<script type="application/ld+json">' . "\n" . 
                         json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . 
                         "\n</script>";
        }
        
        return implode("\n", $scripts);
    }

    /**
     * Configure SEO for a product page
     * Requirements: 11.1, 11.2
     */
    public function configureForProduct(array $product): self
    {
        $productUrl = $this->siteUrl . '/products/' . ($product['slug'] ?? '');
        $productImage = !empty($product['images'][0]['image_url']) 
            ? $this->siteUrl . '/uploads/products/' . $product['images'][0]['image_url']
            : $this->defaultImage;
        
        $this->setTitle($product['meta_title'] ?? $product['name'] ?? 'Product')
             ->setDescription($product['meta_description'] ?? $product['short_description'] ?? $product['description'] ?? '')
             ->setCanonicalUrl($productUrl)
             ->setOgType('product')
             ->setOgImage($productImage)
             ->setTwitterCard('summary_large_image');
        
        // Add product structured data (JSON-LD)
        $structuredData = $this->buildProductStructuredData($product);
        $this->addStructuredData($structuredData);
        
        return $this;
    }

    /**
     * Configure SEO for a category page
     */
    public function configureForCategory(array $category): self
    {
        $categoryUrl = $this->siteUrl . '/category/' . ($category['slug'] ?? '');
        
        $this->setTitle($category['name'] . ' - ' . $this->siteName)
             ->setDescription($category['description'] ?? 'Browse our ' . $category['name'] . ' collection')
             ->setCanonicalUrl($categoryUrl)
             ->setOgType('website');
        
        return $this;
    }

    /**
     * Configure SEO for the home page
     */
    public function configureForHome(): self
    {
        $this->setTitle($this->siteName . ' - Premium Ceylon Cinnamon Products')
             ->setDescription('Shop premium Ceylon cinnamon products including cinnamon sticks, powder, oil, and more. Authentic Sri Lankan cinnamon delivered worldwide.')
             ->setCanonicalUrl($this->siteUrl)
             ->setOgType('website')
             ->setKeywords('Ceylon cinnamon, Sri Lanka cinnamon, cinnamon sticks, cinnamon powder, cinnamon oil, organic cinnamon');
        
        // Add organization structured data
        $this->addStructuredData($this->buildOrganizationStructuredData());
        
        // Add website structured data
        $this->addStructuredData($this->buildWebsiteStructuredData());
        
        return $this;
    }

    /**
     * Configure SEO for a blog post
     */
    public function configureForBlogPost(array $post): self
    {
        $postUrl = $this->siteUrl . '/blog/' . ($post['slug'] ?? '');
        $postImage = !empty($post['featured_image']) 
            ? $this->siteUrl . '/uploads/blog/' . $post['featured_image']
            : $this->defaultImage;
        
        $this->setTitle($post['title'] . ' - ' . $this->siteName)
             ->setDescription($post['excerpt'] ?? $this->truncate($post['content'] ?? '', 160))
             ->setCanonicalUrl($postUrl)
             ->setOgType('article')
             ->setOgImage($postImage);
        
        // Add article structured data
        $this->addStructuredData($this->buildArticleStructuredData($post));
        
        return $this;
    }

    /**
     * Build product structured data (JSON-LD)
     * Requirement 11.2
     */
    private function buildProductStructuredData(array $product): array
    {
        $productUrl = $this->siteUrl . '/products/' . ($product['slug'] ?? '');
        $productImage = !empty($product['images'][0]['image_url']) 
            ? $this->siteUrl . '/uploads/products/' . $product['images'][0]['image_url']
            : $this->defaultImage;
        
        $price = $product['sale_price'] ?? $product['price'] ?? 0;
        $availability = ($product['stock_quantity'] ?? 0) > 0 
            ? 'https://schema.org/InStock' 
            : 'https://schema.org/OutOfStock';
        
        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product['name'] ?? '',
            'description' => $product['description'] ?? $product['short_description'] ?? '',
            'image' => $productImage,
            'url' => $productUrl,
            'sku' => $product['sku'] ?? '',
            'brand' => [
                '@type' => 'Brand',
                'name' => $this->siteName
            ],
            'offers' => [
                '@type' => 'Offer',
                'price' => number_format((float)$price, 2, '.', ''),
                'priceCurrency' => 'USD',
                'availability' => $availability,
                'url' => $productUrl,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $this->siteName
                ]
            ]
        ];
        
        // Add category
        if (!empty($product['category_name'])) {
            $data['category'] = $product['category_name'];
        }
        
        // Add weight
        if (!empty($product['weight'])) {
            $data['weight'] = [
                '@type' => 'QuantitativeValue',
                'value' => $product['weight'],
                'unitCode' => 'KGM'
            ];
        }
        
        // Add aggregate rating if reviews exist
        if (!empty($product['review_count']) && $product['review_count'] > 0) {
            $data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float)($product['average_rating'] ?? 0), 1),
                'reviewCount' => (int)$product['review_count']
            ];
        }
        
        return $data;
    }

    /**
     * Build organization structured data
     */
    private function buildOrganizationStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $this->siteName,
            'url' => $this->siteUrl,
            'logo' => $this->siteUrl . '/assets/images/logo.png',
            'description' => 'Premium Ceylon cinnamon products from Sri Lanka',
            'address' => [
                '@type' => 'PostalAddress',
                'addressCountry' => 'LK'
            ]
        ];
    }

    /**
     * Build website structured data
     */
    private function buildWebsiteStructuredData(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->siteName,
            'url' => $this->siteUrl,
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->siteUrl . '/products?search={search_term_string}'
                ],
                'query-input' => 'required name=search_term_string'
            ]
        ];
    }

    /**
     * Build article structured data for blog posts
     */
    private function buildArticleStructuredData(array $post): array
    {
        $postUrl = $this->siteUrl . '/blog/' . ($post['slug'] ?? '');
        $postImage = !empty($post['featured_image']) 
            ? $this->siteUrl . '/uploads/blog/' . $post['featured_image']
            : $this->defaultImage;
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $post['title'] ?? '',
            'description' => $post['excerpt'] ?? '',
            'image' => $postImage,
            'url' => $postUrl,
            'datePublished' => $post['published_at'] ?? $post['created_at'] ?? date('c'),
            'dateModified' => $post['updated_at'] ?? $post['created_at'] ?? date('c'),
            'author' => [
                '@type' => 'Organization',
                'name' => $this->siteName
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->siteName,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $this->siteUrl . '/assets/images/logo.png'
                ]
            ]
        ];
    }

    /**
     * Build breadcrumb structured data
     */
    public function buildBreadcrumbStructuredData(array $breadcrumbs): array
    {
        $items = [];
        $position = 1;
        
        // Add home
        $items[] = [
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => 'Home',
            'item' => $this->siteUrl
        ];
        
        foreach ($breadcrumbs as $crumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $crumb['name'],
                'item' => $this->siteUrl . '/category/' . $crumb['slug']
            ];
        }
        
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items
        ];
    }

    /**
     * Get Open Graph locale format
     */
    private function getOgLocale(): string
    {
        $localeMap = [
            'en' => 'en_US',
            'si' => 'si_LK'
        ];
        
        return $localeMap[$this->locale] ?? 'en_US';
    }

    /**
     * Truncate text to specified length
     */
    private function truncate(string $text, int $length): string
    {
        $text = strip_tags($text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length - 3) . '...';
    }

    /**
     * Escape HTML special characters
     */
    private function escape(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Get title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Get description
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Check if meta tags are configured
     */
    public function hasMetaTags(): bool
    {
        return $this->title !== null || $this->description !== null;
    }

    /**
     * Check if structured data is configured
     */
    public function hasStructuredData(): bool
    {
        return !empty($this->structuredData);
    }
}
