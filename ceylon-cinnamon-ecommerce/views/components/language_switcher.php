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

<div class="language-switcher">
    <div class="language-options">
        <?php foreach ($supportedLanguages as $lang): ?>
            <?php if ($lang === $currentLanguage): ?>
                <span class="language-option active" title="<?php echo htmlspecialchars(language_name($lang)); ?>">
                    <?php echo htmlspecialchars(language_name($lang)); ?>
                </span>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars($allUrls[$lang]); ?>" 
                   class="language-option" 
                   title="<?php echo htmlspecialchars(language_name($lang)); ?>">
                    <?php echo htmlspecialchars(language_name($lang)); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>

<style>
.language-switcher {
    display: inline-block;
}

.language-options {
    display: flex;
    gap: 10px;
    align-items: center;
}

.language-option {
    padding: 5px 10px;
    text-decoration: none;
    color: #333;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.language-option:hover {
    background-color: #f5f5f5;
    border-color: #999;
}

.language-option.active {
    background-color: #8B4513;
    color: white;
    border-color: #8B4513;
    cursor: default;
}

@media (max-width: 768px) {
    .language-options {
        gap: 5px;
    }
    
    .language-option {
        padding: 4px 8px;
        font-size: 12px;
    }
}
</style>
