<?php
/**
 * Translation Helper Functions
 * Provides global translation functions for use in templates
 * 
 * Requirements:
 * - 9.5: Provide translated content for main pages and product information
 */

declare(strict_types=1);

/**
 * Global language manager instance
 */
$GLOBALS['languageManager'] = null;

/**
 * Initialize the global language manager
 * 
 * @param LanguageManager $languageManager Language manager instance
 */
function initializeLanguageManager(LanguageManager $languageManager): void
{
    $GLOBALS['languageManager'] = $languageManager;
}

/**
 * Get the global language manager
 * 
 * @return LanguageManager|null Language manager instance
 */
function getLanguageManager(): ?LanguageManager
{
    return $GLOBALS['languageManager'] ?? null;
}

/**
 * Translate a key
 * Shorthand for LanguageManager::translate()
 * 
 * Requirements: 9.5 - Provide translated content
 * 
 * @param string $key Translation key (e.g., 'common.welcome')
 * @param array $params Parameters for string interpolation
 * @return string Translated string or key if not found
 */
function trans(string $key, array $params = []): string
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return $key;
    }
    
    return $manager->translate($key, $params);
}

/**
 * Alias for trans() - shorter form
 * 
 * @param string $key Translation key
 * @param array $params Parameters for interpolation
 * @return string Translated string
 */
function t(string $key, array $params = []): string
{
    return trans($key, $params);
}

/**
 * Echo a translated string
 * 
 * @param string $key Translation key
 * @param array $params Parameters for interpolation
 */
function trans_echo(string $key, array $params = []): void
{
    echo trans($key, $params);
}

/**
 * Alias for trans_echo() - shorter form
 * 
 * @param string $key Translation key
 * @param array $params Parameters for interpolation
 */
function te(string $key, array $params = []): void
{
    trans_echo($key, $params);
}

/**
 * Get the current language
 * 
 * @return string Current language code
 */
function current_language(): string
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return 'en';
    }
    
    return $manager->getCurrentLanguage();
}

/**
 * Get all supported languages
 * 
 * @return array Array of supported language codes
 */
function supported_languages(): array
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return ['en', 'si'];
    }
    
    return $manager->getSupportedLanguages();
}

/**
 * Get language name
 * 
 * @param string $language Language code
 * @return string Language name
 */
function language_name(string $language): string
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return $language;
    }
    
    return $manager->getLanguageName($language);
}

/**
 * Get language switcher URL
 * 
 * @param string $language Language code
 * @return string Switcher URL
 */
function language_switcher_url(string $language): string
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return '';
    }
    
    return $manager->getSwitcherUrl($language);
}

/**
 * Get all language switcher URLs
 * 
 * @return array Array of language codes and their switcher URLs
 */
function all_language_switcher_urls(): array
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return [];
    }
    
    return $manager->getAllSwitcherUrls();
}

/**
 * Check if a language is the current language
 * 
 * @param string $language Language code
 * @return bool True if current language
 */
function is_current_language(string $language): bool
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return $language === 'en';
    }
    
    return $manager->isCurrentLanguage($language);
}

/**
 * Load translations for a namespace
 * 
 * @param string $namespace Translation namespace
 * @return bool True if translations loaded
 */
function load_translations(string $namespace): bool
{
    $manager = getLanguageManager();
    
    if ($manager === null) {
        return false;
    }
    
    return $manager->loadTranslations($manager->getCurrentLanguage(), $namespace);
}
