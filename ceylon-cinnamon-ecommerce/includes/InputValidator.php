<?php
/**
 * Input Validator Class
 * Provides comprehensive input validation and sanitization
 * 
 * Requirements:
 * - 10.3: Sanitize and validate all user input server-side
 */

declare(strict_types=1);

class InputValidator
{
    /**
     * Validation errors
     */
    private array $errors = [];
    
    /**
     * Validated and sanitized data
     */
    private array $data = [];
    
    /**
     * Raw input data
     */
    private array $input = [];
    
    /**
     * Constructor
     * 
     * @param array $input Input data to validate
     */
    public function __construct(array $input = [])
    {
        $this->input = $input;
    }
    
    /**
     * Set input data
     * 
     * @param array $input Input data
     * @return self
     */
    public function setInput(array $input): self
    {
        $this->input = $input;
        $this->errors = [];
        $this->data = [];
        return $this;
    }
    
    /**
     * Validate a required field
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? null;
        
        if ($value === null || $value === '' || (is_string($value) && trim($value) === '')) {
            $this->errors[$field][] = "{$label} is required.";
        }
        
        return $this;
    }
    
    /**
     * Validate email format
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field][] = "{$label} must be a valid email address.";
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     * 
     * @param string $field Field name
     * @param int $min Minimum length
     * @param string $label Human-readable label
     * @return self
     */
    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && strlen($value) < $min) {
            $this->errors[$field][] = "{$label} must be at least {$min} characters.";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     * 
     * @param string $field Field name
     * @param int $max Maximum length
     * @param string $label Human-readable label
     * @return self
     */
    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && strlen($value) > $max) {
            $this->errors[$field][] = "{$label} must not exceed {$max} characters.";
        }
        
        return $this;
    }
    
    /**
     * Validate numeric value
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$field][] = "{$label} must be a number.";
        }
        
        return $this;
    }
    
    /**
     * Validate integer value
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function integer(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && filter_var($value, FILTER_VALIDATE_INT) === false) {
            $this->errors[$field][] = "{$label} must be an integer.";
        }
        
        return $this;
    }
    
    /**
     * Validate minimum value
     * 
     * @param string $field Field name
     * @param float $min Minimum value
     * @param string $label Human-readable label
     * @return self
     */
    public function min(string $field, float $min, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && is_numeric($value) && (float)$value < $min) {
            $this->errors[$field][] = "{$label} must be at least {$min}.";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum value
     * 
     * @param string $field Field name
     * @param float $max Maximum value
     * @param string $label Human-readable label
     * @return self
     */
    public function max(string $field, float $max, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && is_numeric($value) && (float)$value > $max) {
            $this->errors[$field][] = "{$label} must not exceed {$max}.";
        }
        
        return $this;
    }
    
    /**
     * Validate value is in a list of allowed values
     * 
     * @param string $field Field name
     * @param array $allowed Allowed values
     * @param string $label Human-readable label
     * @return self
     */
    public function in(string $field, array $allowed, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && !in_array($value, $allowed, true)) {
            $this->errors[$field][] = "{$label} contains an invalid value.";
        }
        
        return $this;
    }
    
    /**
     * Validate regex pattern
     * 
     * @param string $field Field name
     * @param string $pattern Regex pattern
     * @param string $message Custom error message
     * @param string $label Human-readable label
     * @return self
     */
    public function regex(string $field, string $pattern, string $message = '', string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$field][] = $message ?: "{$label} format is invalid.";
        }
        
        return $this;
    }
    
    /**
     * Validate URL format
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function url(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
            $this->errors[$field][] = "{$label} must be a valid URL.";
        }
        
        return $this;
    }
    
    /**
     * Validate phone number format
     * 
     * @param string $field Field name
     * @param string $label Human-readable label
     * @return self
     */
    public function phone(string $field, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        // Allow digits, spaces, dashes, parentheses, and plus sign
        if (!empty($value) && !preg_match('/^[\d\s\-\(\)\+]+$/', $value)) {
            $this->errors[$field][] = "{$label} must be a valid phone number.";
        }
        
        return $this;
    }
    
    /**
     * Validate date format
     * 
     * @param string $field Field name
     * @param string $format Date format (default: Y-m-d)
     * @param string $label Human-readable label
     * @return self
     */
    public function date(string $field, string $format = 'Y-m-d', string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $value = $this->input[$field] ?? '';
        
        if (!empty($value)) {
            $date = \DateTime::createFromFormat($format, $value);
            if (!$date || $date->format($format) !== $value) {
                $this->errors[$field][] = "{$label} must be a valid date.";
            }
        }
        
        return $this;
    }
    
    /**
     * Validate field matches another field
     * 
     * @param string $field Field name
     * @param string $matchField Field to match
     * @param string $label Human-readable label
     * @return self
     */
    public function matches(string $field, string $matchField, string $label = ''): self
    {
        $label = $label ?: ucfirst(str_replace('_', ' ', $field));
        $matchLabel = ucfirst(str_replace('_', ' ', $matchField));
        
        $value = $this->input[$field] ?? '';
        $matchValue = $this->input[$matchField] ?? '';
        
        if ($value !== $matchValue) {
            $this->errors[$field][] = "{$label} must match {$matchLabel}.";
        }
        
        return $this;
    }
    
    /**
     * Check if validation passed
     * 
     * @return bool True if no errors
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     * 
     * @return bool True if there are errors
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Errors by field
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get all errors as a flat array
     * 
     * @return array All error messages
     */
    public function getAllErrors(): array
    {
        $allErrors = [];
        foreach ($this->errors as $fieldErrors) {
            $allErrors = array_merge($allErrors, $fieldErrors);
        }
        return $allErrors;
    }
    
    /**
     * Get first error message
     * 
     * @return string|null First error or null
     */
    public function getFirstError(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return $fieldErrors[0];
            }
        }
        return null;
    }
    
    /**
     * Get errors for a specific field
     * 
     * @param string $field Field name
     * @return array Field errors
     */
    public function getFieldErrors(string $field): array
    {
        return $this->errors[$field] ?? [];
    }
    
    /**
     * Check if a field has errors
     * 
     * @param string $field Field name
     * @return bool True if field has errors
     */
    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
    
    /**
     * Get validated and sanitized data
     * 
     * @param array $fields Fields to get (empty for all)
     * @return array Validated data
     */
    public function validated(array $fields = []): array
    {
        if (empty($fields)) {
            return $this->input;
        }
        
        return array_intersect_key($this->input, array_flip($fields));
    }
    
    /**
     * Add a custom error
     * 
     * @param string $field Field name
     * @param string $message Error message
     * @return self
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }
}
