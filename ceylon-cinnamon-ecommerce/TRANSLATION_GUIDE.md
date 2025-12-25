# Translation Guide

This document explains how to use the multi-language support system in the Ceylon Cinnamon e-commerce application.

## Overview

The application supports English (en) and Sinhala (si) languages. The language system is automatically initialized when the application starts and provides translation functions for use in templates and controllers.

## Requirements

- Requirements 9.1: Support English and Sinhala languages
- Requirements 9.2: Display interface in selected language
- Requirements 9.3: Maintain page context when switching languages
- Requirements 9.4: Store language preference in user session
- Requirements 9.5: Provide translated content for main pages and product information

## Architecture

### Components

1. **LanguageManager** (`includes/LanguageManager.php`)
   - Handles language detection and switching
   - Manages translation loading and retrieval
   - Stores language preference in session
   - Generates language switcher URLs

2. **TranslationHelper** (`includes/TranslationHelper.php`)
   - Provides global translation functions
   - Initializes the language manager
   - Offers convenient helper functions for templates

3. **Translation Files** (`lang/{language}/{namespace}.php`)
   - Organized by language and namespace
   - Each file returns an array of key-value pairs
   - Supports parameter interpolation

4. **Language Switcher Component** (`views/components/language_switcher.php`)
   - Displays available languages
   - Preserves page context when switching
   - Styled for responsive design

## Usage in Templates

### Basic Translation

```php
<?= t('common.welcome') ?>
```

### Translation with Parameters

```php
<?= t('footer.copyright', ['year' => date('Y')]) ?>
```

### Echo Translation

```php
<?php te('nav.home') ?>
```

### Get Current Language

```php
<?php $lang = current_language(); ?>
```

### Check if Language is Current

```php
<?php if (is_current_language('en')): ?>
    English is selected
<?php endif; ?>
```

### Get Language Name

```php
<?= language_name('si') ?>  <!-- Output: සිංහල -->
```

### Language Switcher URLs

```php
<?php foreach (all_language_switcher_urls() as $lang => $url): ?>
    <a href="<?= $url ?>"><?= language_name($lang) ?></a>
<?php endforeach; ?>
```

## Translation File Structure

### File Organization

```
lang/
├── en/
│   ├── common.php
│   ├── products.php
│   ├── auth.php
│   └── cart.php
└── si/
    ├── common.php
    ├── products.php
    ├── auth.php
    └── cart.php
```

### File Format

Each translation file returns an associative array:

```php
<?php
return [
    'key.name' => 'Translated text',
    'key.with_param' => 'Text with :param placeholder',
    'nav.home' => 'Home',
    'nav.products' => 'Products',
];
```

## Adding New Translations

### Step 1: Create Translation Keys

Add keys to the appropriate namespace file:

```php
// lang/en/products.php
return [
    'product.new_feature' => 'New Feature',
];

// lang/si/products.php
return [
    'product.new_feature' => 'නව විශේෂතාවය',
];
```

### Step 2: Use in Templates

```php
<?= t('products.product.new_feature') ?>
```

### Step 3: Load Namespace if Needed

If using a new namespace, load it first:

```php
<?php load_translations('products'); ?>
```

## Language Detection

The system detects language in this priority order:

1. **Session**: Previously selected language stored in session
2. **URL Parameter**: `?lang=si` in the URL
3. **Browser Accept-Language**: Browser language preference
4. **Default**: English (en)

## Language Switching

### Preserving Page Context

When switching languages, the current page path is preserved:

```
/products?category=1&sort=price
↓ (switch to Sinhala)
/products?category=1&sort=price&lang=si
```

### Language Switcher Component

Include the language switcher in your template:

```php
<?php include __DIR__ . '/../components/language_switcher.php'; ?>
```

## Parameter Interpolation

Translation strings support parameter interpolation using `:paramName` syntax:

```php
// Translation file
'message.welcome' => 'Welcome, :name!'

// Usage
<?= t('message.welcome', ['name' => 'John']) ?>
// Output: Welcome, John!
```

## Best Practices

1. **Use Namespaces**: Organize translations by feature (common, products, auth, cart)
2. **Consistent Keys**: Use dot notation for hierarchical keys (e.g., `nav.home`, `nav.products`)
3. **Avoid Hardcoding**: Always use translation functions instead of hardcoding text
4. **Parameter Names**: Use descriptive parameter names (e.g., `:year`, `:name`, `:price`)
5. **Fallback**: If a translation is missing, the system falls back to English
6. **Load Namespaces**: Load translation namespaces at the beginning of templates that need them

## Supported Languages

- **English (en)**: Default language
- **Sinhala (si)**: Sri Lankan language

## API Reference

### LanguageManager Methods

```php
// Set current language
$languageManager->setLanguage('si');

// Get current language
$lang = $languageManager->getCurrentLanguage();

// Translate a key
$text = $languageManager->translate('common.welcome');

// Get language name
$name = $languageManager->getLanguageName('si');

// Get switcher URL
$url = $languageManager->getSwitcherUrl('en');

// Check if language is current
$isCurrent = $languageManager->isCurrentLanguage('en');

// Load translations
$languageManager->loadTranslations('en', 'products');
```

### Helper Functions

```php
// Translate
t('key.name')
trans('key.name')

// Echo translation
te('key.name')
trans_echo('key.name')

// Get current language
current_language()

// Get supported languages
supported_languages()

// Get language name
language_name('si')

// Get switcher URL
language_switcher_url('en')

// Get all switcher URLs
all_language_switcher_urls()

// Check if language is current
is_current_language('en')

// Load translations
load_translations('products')
```

## Testing

Property-based tests validate the language switching system:

```bash
php vendor/bin/phpunit tests/Property/LanguageSwitchingPropertyTest.php
```

Tests verify:
- Language switching preserves page context
- Language preference is stored in session
- Invalid language codes are rejected
- Language switcher URLs are generated correctly
- Current language identification is accurate
- Supported languages are correctly identified
- Language names are retrieved correctly

## Troubleshooting

### Translations Not Showing

1. Check that the translation file exists in `lang/{language}/{namespace}.php`
2. Verify the key exists in the translation file
3. Ensure the namespace is loaded with `load_translations()`
4. Check browser console for JavaScript errors

### Language Not Switching

1. Verify session is started
2. Check that `?lang=xx` parameter is in the URL
3. Ensure the language code is valid (en or si)
4. Clear browser cookies and try again

### Missing Translations

If a translation key is not found:
1. The system falls back to English
2. If still not found, the key itself is returned
3. Check the translation file for typos
4. Verify the key format matches (e.g., `namespace.key`)

## Future Enhancements

- Add more languages
- Implement translation caching
- Add admin interface for managing translations
- Support for right-to-left (RTL) languages
- Translation versioning and rollback
