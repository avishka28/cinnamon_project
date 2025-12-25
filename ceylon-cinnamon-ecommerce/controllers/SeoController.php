<?php
/**
 * SEO Controller
 * Handles SEO-related endpoints like sitemap.xml
 * 
 * Requirements:
 * - 11.3: Generate sitemap.xml and robots.txt files automatically
 */

declare(strict_types=1);

class SeoController extends Controller
{
    /**
     * Generate and serve sitemap.xml
     * Requirement 11.3
     */
    public function sitemap(): void
    {
        $generator = new SitemapGenerator();
        $xml = $generator->generate();
        
        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: public, max-age=3600'); // Cache for 1 hour
        echo $xml;
        exit;
    }

    /**
     * Serve robots.txt
     * Requirement 11.3
     */
    public function robots(): void
    {
        $robotsFile = PUBLIC_PATH . '/robots.txt';
        
        if (file_exists($robotsFile)) {
            header('Content-Type: text/plain; charset=utf-8');
            header('Cache-Control: public, max-age=86400'); // Cache for 24 hours
            readfile($robotsFile);
        } else {
            // Generate default robots.txt if file doesn't exist
            header('Content-Type: text/plain; charset=utf-8');
            echo $this->generateDefaultRobots();
        }
        exit;
    }

    /**
     * Generate default robots.txt content
     */
    private function generateDefaultRobots(): string
    {
        $siteUrl = rtrim(APP_URL, '/');
        
        return <<<ROBOTS
# robots.txt for Ceylon Cinnamon E-commerce
User-agent: *
Allow: /

Disallow: /admin/
Disallow: /api/
Disallow: /cart
Disallow: /checkout
Disallow: /login
Disallow: /register

Sitemap: {$siteUrl}/sitemap.xml
ROBOTS;
    }
}
