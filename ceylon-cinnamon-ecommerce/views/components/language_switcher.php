<?php
/**
 * Language Switcher Component
 * Displays language selection options
 * 
 * Requirements: 9.1, 9.2, 9.3 - Multi-language support with context preservation
 */

$currentLanguage = current_language();
$supportedLanguages = supported_languages();
$allUrls = all_language_switcher_urls();
?>

<div class="language-switcher btn-group" role="group" aria-label="Language selection">
    <?php foreach ($supportedLanguages as $lang): ?>
        <?php if ($lang === $currentLanguage): ?>
            <span class="btn btn-primary btn-sm disabled" title="<?php echo htmlspecialchars(language_name($lang)); ?>">
                <?php echo htmlspecialchars(language_name($lang)); ?>
            </span>
        <?php else: ?>
            <a href="<?php echo htmlspecialchars($allUrls[$lang]); ?>" 
               class="btn btn-outline-secondary btn-sm" 
               title="<?php echo htmlspecialchars(language_name($lang)); ?>">
                <?php echo htmlspecialchars(language_name($lang)); ?>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
