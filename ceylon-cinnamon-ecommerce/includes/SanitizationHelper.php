<?php
/**
 * Sanitization Helper Functions
 * Provides convenient functions for input sanitization
 * 
 * Requirements:
 * - 10.3: Sanitize and validate all user input server-side
 */

declare(strict_types=1);

/**
 * Sanitize a string for safe HTML output (XSS protection)
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitize(string $input): string
{
    return InputSanitizer::sanitizeString($input);
}

/**
 * Alias for sanitize - escape HTML entities
 * 
 * @param string $input Input string
 * @return string Escaped string
 */
function e(string $input): string
{
    return InputSanitizer::sanitizeString($input);
}

/**
 * Sanitize for HTML attribute
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function attr(string $input): string
{
    return InputSanitizer::attribute($input);
}

/**
 * Sanitize for JavaScript
 * 
 * @param string $input Input string
 * @return string JSON-encoded string
 */
function js_escape(string $input): string
{
    return InputSanitizer::js($input);
}

/**
 * Sanitize email
 * 
 * @param string $email Email address
 * @return string|false Sanitized email or false
 */
function sanitize_email(string $email): string|false
{
    return InputSanitizer::email($email);
}

/**
 * Sanitize integer
 * 
 * @param mixed $input Input value
 * @return int Sanitized integer
 */
function sanitize_int(mixed $input): int
{
    return InputSanitizer::int($input);
}

/**
 * Sanitize float
 * 
 * @param mixed $input Input value
 * @return float Sanitized float
 */
function sanitize_float(mixed $input): float
{
    return InputSanitizer::float($input);
}

/**
 * Sanitize filename
 * 
 * @param string $filename Filename
 * @return string Sanitized filename
 */
function sanitize_filename(string $filename): string
{
    return InputSanitizer::filename($filename);
}

/**
 * Create a URL-friendly slug
 * 
 * @param string $input Input string
 * @return string Slug
 */
function slugify(string $input): string
{
    return InputSanitizer::slug($input);
}

/**
 * Strip all HTML tags
 * 
 * @param string $input Input string
 * @return string String without tags
 */
function strip_html(string $input): string
{
    return InputSanitizer::stripTags($input);
}

/**
 * Sanitize rich text (allow safe HTML)
 * 
 * @param string $input Input string
 * @return string Sanitized HTML
 */
function sanitize_rich_text(string $input): string
{
    return InputSanitizer::richText($input);
}

/**
 * Check if input contains potential XSS
 * 
 * @param string $input Input string
 * @return bool True if XSS detected
 */
function has_xss(string $input): bool
{
    return InputSanitizer::containsXss($input);
}

/**
 * Create a new input validator
 * 
 * @param array $input Input data
 * @return InputValidator Validator instance
 */
function validate(array $input): InputValidator
{
    return new InputValidator($input);
}
