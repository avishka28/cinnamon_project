<?php
/**
 * CSRF Helper Functions
 * Provides convenient functions for CSRF protection in views
 * 
 * Requirements:
 * - 10.2: CSRF token validation for all form submissions
 */

declare(strict_types=1);

/**
 * Get the current CSRF token
 * 
 * @return string The CSRF token
 */
function csrf_token(): string
{
    return CsrfMiddleware::token();
}

/**
 * Generate a hidden input field with the CSRF token
 * 
 * @return string HTML hidden input element
 */
function csrf_field(): string
{
    return CsrfMiddleware::field();
}

/**
 * Generate a meta tag with the CSRF token for JavaScript access
 * 
 * @return string HTML meta element
 */
function csrf_meta(): string
{
    $sessionManager = new SessionManager();
    $csrfProtection = new CsrfProtection($sessionManager);
    return $csrfProtection->getMetaTag();
}

/**
 * Validate a CSRF token
 * 
 * @param string $token Token to validate
 * @return bool True if valid
 */
function csrf_validate(string $token): bool
{
    return CsrfMiddleware::validate($token);
}

/**
 * Get the CSRF token name
 * 
 * @return string Token name
 */
function csrf_token_name(): string
{
    return CsrfProtection::getTokenName();
}
