<?php
/**
 * Language Manager
 * Handles language detection, switching, and session-based preference storage
 * 
 * Requirements:
 * - 9.1: Support English and Sinhala languages
 * - 9.2: Display interface in selected language
 * - 9.3: Maintain page context when switching languages
 * - 9.4: Store language preference in user session
 */

declare(strict_types=1);

class LanguageManager
{
    /**
     * Supported languages
     */
    private const SUPPORTED_LANGUAGES = ['en', 'si'];
    private const DEFAULT_LANGUAGE = 'en';
    private const SESSION_KEY = 'language';

    /**
     * Session manager instance
     */
    private SessionManager $sessionManager;

    /**
     * Current language
     */
    private string $currentLanguage;

    /**
     * Loaded translations
     */
    private array $translations = [];

    /**
     * Constructor
     * 
     * @param SessionManager $sessionManager Session manager instance
     */
    public function __construct(SessionManager $sessionManager)
    {
        $this->sessionManager = $sessionManager;
        $this->currentLanguage = $this->detectLanguage();
    }

    /**
     * Detect language from various sources
     * Priority: Session > URL parameter > Browser Accept-Language > Default
     * Requirements: 9.1, 9.2, 9.4
     * 
     * @return string Language code
     */
    private function detectLanguage(): string
    {
        // 1. Check session for stored preference
        if ($this->sessionManager->has(self::SESSION_KEY)) {
            $language = $this->sessionManager->get(self::SESSION_KEY);
            if ($this->isSupported($language)) {
                return $language;
            }
        }

        // 2. Check URL parameter
        if (isset($_GET['lang'])) {
            $language = $this->sanitizeLanguageCode($_GET['lang']);
            if ($this->isSupported($language)) {
                $this->setLanguage($language);
                return $language;
            }
        }

        // 3. Check browser Accept-Language header
        $browserLanguage = $this->detectBrowserLanguage();
        if ($browserLanguage) {
            return $browserLanguage;
        }

        // 4. Use default language
        return self::DEFAULT_LANGUAGE;
    }

    /**
     * Detect language from browser Accept-Language header
     * 
     * @return string|null Language code or null if not supported
     */
    private function detectBrowserLanguage(): ?string
    {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        }

        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        
        // Parse Accept-Language header
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $langCode = strtolower(trim($parts[0]));
            
            // Extract primary language code (e.g., 'en' from 'en-US')
            $primaryLang = explode('-', $langCode)[0];
            
            $quality = 1.0;
            if (isset($parts[1])) {
                preg_match('/q=([0-9.]+)/', $parts[1], $matches);
                if (isset($matches[1])) {
                    $quality = (float)$matches[1];
                }
            }
            
            $languages[$primaryLang] = $quality;
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Find first supported language
        foreach ($languages as $lang => $quality) {
            if ($this->isSupported($lang)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Check if language is supported
     * 
     * @param string $language Language code
     * @return bool True if supported
     */
    private function isSupported(string $language): bool
    {
        return in_array($language, self::SUPPORTED_LANGUAGES, true);
    }

    /**
     * Sanitize language code
     * 
     * @param string $language Language code
     * @return string Sanitized language code
     */
    private function sanitizeLanguageCode(string $language): string
    {
        return strtolower(preg_replace('/[^a-z]/', '', $language));
    }

    /**
     * Set the current language
     * Requirements: 9.2, 9.4 - Store preference in session
     * 
     * @param string $language Language code
     * @return bool True if language was set
     */
    public function setLanguage(string $language): bool
    {
        $language = $this->sanitizeLanguageCode($language);
        
        if (!$this->isSupported($language)) {
            return false;
        }

        $this->currentLanguage = $language;
        $this->sessionManager->set(self::SESSION_KEY, $language);
        
        return true;
    }

    /**
     * Get the current language
     * 
     * @return string Language code
     */
    public function getCurrentLanguage(): string
    {
        return $this->currentLanguage;
    }

    /**
     * Get all supported languages
     * 
     * @return array Array of supported language codes
     */
    public function getSupportedLanguages(): array
    {
        return self::SUPPORTED_LANGUAGES;
    }

    /**
     * Get language name in English
     * 
     * @param string $language Language code
     * @return string Language name
     */
    public function getLanguageName(string $language): string
    {
        $names = [
            'en' => 'English',
            'si' => 'සිංහල'
        ];

        return $names[$language] ?? $language;
    }

    /**
     * Load translations for a specific language
     * 
     * @param string $language Language code
     * @param string $namespace Translation namespace (e.g., 'common', 'products')
     * @return bool True if translations loaded
     */
    public function loadTranslations(string $language, string $namespace = 'common'): bool
    {
        if (!$this->isSupported($language)) {
            return false;
        }

        $translationFile = $this->getTranslationFilePath($language, $namespace);
        
        if (!file_exists($translationFile)) {
            return false;
        }

        $translations = include $translationFile;
        
        if (!is_array($translations)) {
            return false;
        }

        $key = "{$language}.{$namespace}";
        $this->translations[$key] = $translations;
        
        return true;
    }

    /**
     * Get translation file path
     * 
     * @param string $language Language code
     * @param string $namespace Translation namespace
     * @return string File path
     */
    private function getTranslationFilePath(string $language, string $namespace): string
    {
        $basePath = dirname(__DIR__) . '/lang';
        return "{$basePath}/{$language}/{$namespace}.php";
    }

    /**
     * Translate a key
     * Requirements: 9.5 - Provide translated content
     * 
     * @param string $key Translation key (e.g., 'common.welcome')
     * @param array $params Parameters for string interpolation
     * @return string Translated string or key if not found
     */
    public function translate(string $key, array $params = []): string
    {
        // Parse key format: namespace.key or just key
        $parts = explode('.', $key, 2);
        
        if (count($parts) === 2) {
            $namespace = $parts[0];
            $translationKey = $parts[1];
        } else {
            $namespace = 'common';
            $translationKey = $key;
        }

        // Load translations if not already loaded
        $cacheKey = "{$this->currentLanguage}.{$namespace}";
        if (!isset($this->translations[$cacheKey])) {
            $this->loadTranslations($this->currentLanguage, $namespace);
        }

        // Get translation
        if (isset($this->translations[$cacheKey][$translationKey])) {
            $translation = $this->translations[$cacheKey][$translationKey];
        } else {
            // Fallback to English if translation not found
            if ($this->currentLanguage !== 'en') {
                $this->loadTranslations('en', $namespace);
                $enKey = "en.{$namespace}";
                $translation = $this->translations[$enKey][$translationKey] ?? $key;
            } else {
                $translation = $key;
            }
        }

        // Interpolate parameters
        if (!empty($params)) {
            foreach ($params as $paramKey => $paramValue) {
                $translation = str_replace(":{$paramKey}", $paramValue, $translation);
            }
        }

        return $translation;
    }

    /**
     * Alias for translate method
     * 
     * @param string $key Translation key
     * @param array $params Parameters for interpolation
     * @return string Translated string
     */
    public function t(string $key, array $params = []): string
    {
        return $this->translate($key, $params);
    }

    /**
     * Get language switcher URL
     * Requirements: 9.3 - Maintain page context when switching
     * 
     * @param string $language Language code
     * @return string URL with language parameter
     */
    public function getSwitcherUrl(string $language): string
    {
        if (!$this->isSupported($language)) {
            return '';
        }

        // Get current URL
        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Remove existing lang parameter if present
        $url = preg_replace('/[?&]lang=[a-z]+/', '', $currentUrl);
        
        // Add new lang parameter
        $separator = strpos($url, '?') !== false ? '&' : '?';
        
        return $url . $separator . 'lang=' . $language;
    }

    /**
     * Get all language switcher URLs
     * 
     * @return array Array of language codes and their switcher URLs
     */
    public function getAllSwitcherUrls(): array
    {
        $urls = [];
        
        foreach (self::SUPPORTED_LANGUAGES as $language) {
            $urls[$language] = $this->getSwitcherUrl($language);
        }
        
        return $urls;
    }

    /**
     * Check if a language is the current language
     * 
     * @param string $language Language code
     * @return bool True if current language
     */
    public function isCurrentLanguage(string $language): bool
    {
        return $this->currentLanguage === $language;
    }
}
