<?php
/**
 * Asset Helper Class
 * Handles CDN support and asset URL generation for performance optimization
 * 
 * Requirements:
 * - 11.4: Implement lazy loading for images
 * - 11.6: Support CDN integration for static assets
 */

declare(strict_types=1);

class AssetHelper
{
    private static ?string $cdnUrl = null;
    private static bool $cdnEnabled = false;

    /**
     * Initialize CDN configuration
     */
    public static function init(): void
    {
        self::$cdnUrl = $_ENV['CDN_URL'] ?? null;
        self::$cdnEnabled = !empty(self::$cdnUrl) && filter_var($_ENV['CDN_ENABLED'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get asset URL with CDN support
     * Requirement 11.6: CDN integration for static assets
     * 
     * @param string $path Asset path relative to public directory
     * @return string Full URL to asset
     */
    public static function asset(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        
        if (self::$cdnEnabled && self::$cdnUrl) {
            return rtrim(self::$cdnUrl, '/') . $path;
        }
        
        return url($path);
    }

    /**
     * Get image URL with CDN support
     * 
     * @param string $path Image path
     * @return string Full URL to image
     */
    public static function image(string $path): string
    {
        return self::asset('/assets/images/' . ltrim($path, '/'));
    }

    /**
     * Get CSS URL with CDN support
     * 
     * @param string $path CSS file path
     * @return string Full URL to CSS file
     */
    public static function css(string $path): string
    {
        return self::asset('/assets/css/' . ltrim($path, '/'));
    }

    /**
     * Get JavaScript URL with CDN support
     * 
     * @param string $path JavaScript file path
     * @return string Full URL to JavaScript file
     */
    public static function js(string $path): string
    {
        return self::asset('/assets/js/' . ltrim($path, '/'));
    }

    /**
     * Get upload URL with CDN support
     * 
     * @param string $path Upload path
     * @return string Full URL to uploaded file
     */
    public static function upload(string $path): string
    {
        return self::asset('/uploads/' . ltrim($path, '/'));
    }

    /**
     * Generate lazy loading image tag
     * Requirement 11.4: Implement lazy loading for images
     * 
     * @param string $src Image source URL
     * @param string $alt Alt text
     * @param array $attributes Additional HTML attributes
     * @return string HTML img tag with lazy loading
     */
    public static function lazyImage(string $src, string $alt = '', array $attributes = []): string
    {
        $defaultAttributes = [
            'loading' => 'lazy',
            'decoding' => 'async'
        ];
        
        $attributes = array_merge($defaultAttributes, $attributes);
        $attributes['src'] = $src;
        $attributes['alt'] = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        
        $attrString = self::buildAttributeString($attributes);
        
        return "<img {$attrString}>";
    }

    /**
     * Generate responsive lazy loading image with srcset
     * Requirement 11.4: Implement lazy loading for images
     * 
     * @param string $src Base image source URL
     * @param string $alt Alt text
     * @param array $sizes Array of sizes for srcset (e.g., [320, 640, 1024])
     * @param array $attributes Additional HTML attributes
     * @return string HTML img tag with lazy loading and srcset
     */
    public static function responsiveLazyImage(
        string $src, 
        string $alt = '', 
        array $sizes = [320, 640, 1024, 1920],
        array $attributes = []
    ): string {
        $defaultAttributes = [
            'loading' => 'lazy',
            'decoding' => 'async'
        ];
        
        $attributes = array_merge($defaultAttributes, $attributes);
        $attributes['src'] = $src;
        $attributes['alt'] = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        
        // Generate srcset if image supports resizing
        // This assumes images are served through a CDN or image service that supports resizing
        if (self::$cdnEnabled && self::supportsImageResizing()) {
            $srcset = [];
            foreach ($sizes as $width) {
                $resizedUrl = self::getResizedImageUrl($src, $width);
                $srcset[] = "{$resizedUrl} {$width}w";
            }
            $attributes['srcset'] = implode(', ', $srcset);
            $attributes['sizes'] = '(max-width: 320px) 320px, (max-width: 640px) 640px, (max-width: 1024px) 1024px, 1920px';
        }
        
        $attrString = self::buildAttributeString($attributes);
        
        return "<img {$attrString}>";
    }

    /**
     * Generate picture element with WebP support and lazy loading
     * 
     * @param string $src Original image source
     * @param string $alt Alt text
     * @param array $attributes Additional attributes
     * @return string HTML picture element
     */
    public static function pictureElement(string $src, string $alt = '', array $attributes = []): string
    {
        $webpSrc = self::getWebPUrl($src);
        $defaultAttributes = [
            'loading' => 'lazy',
            'decoding' => 'async'
        ];
        
        $imgAttributes = array_merge($defaultAttributes, $attributes);
        $imgAttributes['src'] = $src;
        $imgAttributes['alt'] = htmlspecialchars($alt, ENT_QUOTES, 'UTF-8');
        
        $attrString = self::buildAttributeString($imgAttributes);
        
        $html = "<picture>\n";
        
        // WebP source
        if ($webpSrc !== $src) {
            $html .= "  <source srcset=\"{$webpSrc}\" type=\"image/webp\">\n";
        }
        
        // Fallback img
        $html .= "  <img {$attrString}>\n";
        $html .= "</picture>";
        
        return $html;
    }

    /**
     * Generate preload link for critical assets
     * 
     * @param string $href Asset URL
     * @param string $as Asset type (script, style, image, font)
     * @param string|null $type MIME type
     * @return string HTML link tag for preloading
     */
    public static function preload(string $href, string $as, ?string $type = null): string
    {
        $attributes = [
            'rel' => 'preload',
            'href' => $href,
            'as' => $as
        ];
        
        if ($type) {
            $attributes['type'] = $type;
        }
        
        if ($as === 'font') {
            $attributes['crossorigin'] = 'anonymous';
        }
        
        $attrString = self::buildAttributeString($attributes);
        
        return "<link {$attrString}>";
    }

    /**
     * Generate preconnect link for external resources
     * 
     * @param string $origin External origin URL
     * @return string HTML link tag for preconnect
     */
    public static function preconnect(string $origin): string
    {
        return "<link rel=\"preconnect\" href=\"{$origin}\">\n" .
               "<link rel=\"dns-prefetch\" href=\"{$origin}\">";
    }

    /**
     * Check if CDN supports image resizing
     */
    private static function supportsImageResizing(): bool
    {
        // This would be configured based on the CDN being used
        // Common CDNs like Cloudflare, Cloudinary, imgix support this
        return filter_var($_ENV['CDN_SUPPORTS_RESIZE'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Get resized image URL (CDN-specific implementation)
     */
    private static function getResizedImageUrl(string $src, int $width): string
    {
        // This implementation depends on the CDN being used
        // Example for Cloudflare: /cdn-cgi/image/width={width}/{src}
        // Example for Cloudinary: /w_{width}/{src}
        
        $cdnType = $_ENV['CDN_TYPE'] ?? 'generic';
        
        switch ($cdnType) {
            case 'cloudflare':
                return str_replace('/uploads/', "/cdn-cgi/image/width={$width}/uploads/", $src);
            case 'cloudinary':
                return preg_replace('/\/upload\//', "/upload/w_{$width}/", $src);
            default:
                return $src;
        }
    }

    /**
     * Get WebP version URL of an image
     */
    private static function getWebPUrl(string $src): string
    {
        // Check if CDN automatically serves WebP
        if (filter_var($_ENV['CDN_AUTO_WEBP'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $src; // CDN handles format negotiation
        }
        
        // Otherwise, try to find a .webp version
        $pathInfo = pathinfo($src);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
        
        return $webpPath;
    }

    /**
     * Build HTML attribute string from array
     */
    private static function buildAttributeString(array $attributes): string
    {
        $parts = [];
        foreach ($attributes as $key => $value) {
            if ($value === true) {
                $parts[] = $key;
            } elseif ($value !== false && $value !== null) {
                $parts[] = $key . '="' . htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return implode(' ', $parts);
    }

    /**
     * Check if CDN is enabled
     */
    public static function isCdnEnabled(): bool
    {
        return self::$cdnEnabled;
    }

    /**
     * Get CDN URL
     */
    public static function getCdnUrl(): ?string
    {
        return self::$cdnUrl;
    }
}

// Initialize on load
AssetHelper::init();
