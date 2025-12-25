<?php
/**
 * Sitemap Generator Class
 * Generates sitemap.xml for SEO optimization
 * 
 * Requirements:
 * - 11.3: Generate sitemap.xml and robots.txt files automatically
 */

declare(strict_types=1);

class SitemapGenerator
{
    private string $siteUrl;
    private array $urls = [];
    private \PDO $db;

    public function __construct()
    {
        $this->siteUrl = rtrim(APP_URL, '/');
        $this->db = Database::getInstance();
    }

    /**
     * Generate complete sitemap XML
     */
    public function generate(): string
    {
        $this->collectUrls();
        return $this->buildXml();
    }

    /**
     * Collect all URLs for the sitemap
     */
    private function collectUrls(): void
    {
        // Static pages
        $this->addStaticPages();
        
        // Product pages
        $this->addProductPages();
        
        // Category pages
        $this->addCategoryPages();
        
        // Blog pages
        $this->addBlogPages();
    }

    /**
     * Add static pages to sitemap
     */
    private function addStaticPages(): void
    {
        $staticPages = [
            ['loc' => '/', 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => '/products', 'priority' => '0.9', 'changefreq' => 'daily'],
            ['loc' => '/blog', 'priority' => '0.8', 'changefreq' => 'weekly'],
            ['loc' => '/about', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/contact', 'priority' => '0.6', 'changefreq' => 'monthly'],
            ['loc' => '/certificates', 'priority' => '0.5', 'changefreq' => 'monthly'],
            ['loc' => '/gallery', 'priority' => '0.5', 'changefreq' => 'weekly'],
            ['loc' => '/wholesale', 'priority' => '0.7', 'changefreq' => 'monthly'],
        ];

        foreach ($staticPages as $page) {
            $this->urls[] = [
                'loc' => $this->siteUrl . $page['loc'],
                'lastmod' => date('Y-m-d'),
                'changefreq' => $page['changefreq'],
                'priority' => $page['priority']
            ];
        }
    }

    /**
     * Add product pages to sitemap
     */
    private function addProductPages(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT slug, updated_at 
                FROM products 
                WHERE is_active = 1 
                ORDER BY updated_at DESC
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($products as $product) {
                $this->urls[] = [
                    'loc' => $this->siteUrl . '/products/' . $product['slug'],
                    'lastmod' => date('Y-m-d', strtotime($product['updated_at'])),
                    'changefreq' => 'weekly',
                    'priority' => '0.8'
                ];
            }
        } catch (\PDOException $e) {
            // Log error but continue with other URLs
            error_log('Sitemap: Failed to fetch products - ' . $e->getMessage());
        }
    }

    /**
     * Add category pages to sitemap
     */
    private function addCategoryPages(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT slug 
                FROM categories 
                WHERE is_active = 1 
                ORDER BY name
            ");
            $stmt->execute();
            $categories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($categories as $category) {
                $this->urls[] = [
                    'loc' => $this->siteUrl . '/category/' . $category['slug'],
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.7'
                ];
            }
        } catch (\PDOException $e) {
            error_log('Sitemap: Failed to fetch categories - ' . $e->getMessage());
        }
    }

    /**
     * Add blog pages to sitemap
     */
    private function addBlogPages(): void
    {
        try {
            $stmt = $this->db->prepare("
                SELECT slug, updated_at 
                FROM blog_posts 
                WHERE status = 'published' 
                ORDER BY published_at DESC
            ");
            $stmt->execute();
            $posts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($posts as $post) {
                $this->urls[] = [
                    'loc' => $this->siteUrl . '/blog/' . $post['slug'],
                    'lastmod' => date('Y-m-d', strtotime($post['updated_at'])),
                    'changefreq' => 'monthly',
                    'priority' => '0.6'
                ];
            }

            // Add blog category pages
            $stmt = $this->db->prepare("
                SELECT slug 
                FROM blog_categories 
                WHERE is_active = 1
            ");
            $stmt->execute();
            $blogCategories = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($blogCategories as $category) {
                $this->urls[] = [
                    'loc' => $this->siteUrl . '/blog/category/' . $category['slug'],
                    'lastmod' => date('Y-m-d'),
                    'changefreq' => 'weekly',
                    'priority' => '0.5'
                ];
            }
        } catch (\PDOException $e) {
            error_log('Sitemap: Failed to fetch blog posts - ' . $e->getMessage());
        }
    }

    /**
     * Build XML sitemap string
     */
    private function buildXml(): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->urls as $url) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= "    <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "    <changefreq>" . $url['changefreq'] . "</changefreq>\n";
            $xml .= "    <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Save sitemap to file
     */
    public function saveToFile(string $path = null): bool
    {
        $path = $path ?? PUBLIC_PATH . '/sitemap.xml';
        $xml = $this->generate();
        
        return file_put_contents($path, $xml) !== false;
    }

    /**
     * Get URL count
     */
    public function getUrlCount(): int
    {
        return count($this->urls);
    }
}
