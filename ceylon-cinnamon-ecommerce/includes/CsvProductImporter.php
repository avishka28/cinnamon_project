<?php
/**
 * CSV Product Importer
 * Handles bulk product import from CSV files with validation
 * 
 * Requirements:
 * - 6.4: Bulk product import with data validation
 */

declare(strict_types=1);

class CsvProductImporter
{
    private Product $productModel;
    private Category $categoryModel;
    private array $errors = [];
    private array $imported = [];
    private array $skipped = [];
    private array $categoryCache = [];

    /**
     * Required CSV columns
     */
    private const REQUIRED_COLUMNS = ['sku', 'name', 'price', 'category'];

    /**
     * Optional CSV columns with defaults
     */
    private const OPTIONAL_COLUMNS = [
        'description' => '',
        'short_description' => '',
        'sale_price' => null,
        'stock_quantity' => 0,
        'weight' => null,
        'dimensions' => '',
        'is_organic' => 0,
        'origin' => '',
        'tags' => '',
        'meta_title' => '',
        'meta_description' => '',
        'is_active' => 1
    ];

    public function __construct()
    {
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->loadCategoryCache();
    }

    /**
     * Load categories into cache for faster lookup
     */
    private function loadCategoryCache(): void
    {
        $categories = $this->categoryModel->getAll();
        foreach ($categories as $category) {
            $this->categoryCache[strtolower($category['name'])] = $category['id'];
            $this->categoryCache[strtolower($category['slug'])] = $category['id'];
            $this->categoryCache[(string) $category['id']] = $category['id'];
        }
    }

    /**
     * Import products from CSV file
     * Requirements: 6.4 - Bulk product import with data validation
     * 
     * @param string $filepath Path to CSV file
     * @param bool $skipFirstRow Whether to skip header row
     * @return array Import results
     */
    public function importFromFile(string $filepath, bool $skipFirstRow = true): array
    {
        $this->errors = [];
        $this->imported = [];
        $this->skipped = [];

        if (!file_exists($filepath)) {
            $this->errors[] = 'CSV file not found';
            return $this->getResults();
        }

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            $this->errors[] = 'Failed to open CSV file';
            return $this->getResults();
        }

        $rowNumber = 0;
        $headers = null;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // First row is headers
            if ($rowNumber === 1) {
                $headers = $this->normalizeHeaders($row);
                
                // Validate required columns exist
                $missingColumns = $this->validateHeaders($headers);
                if (!empty($missingColumns)) {
                    $this->errors[] = 'Missing required columns: ' . implode(', ', $missingColumns);
                    fclose($handle);
                    return $this->getResults();
                }

                if ($skipFirstRow) {
                    continue;
                }
            }

            // Process data row
            $this->processRow($row, $headers, $rowNumber);
        }

        fclose($handle);
        return $this->getResults();
    }

    /**
     * Import products from CSV string
     * 
     * @param string $csvContent CSV content as string
     * @param bool $skipFirstRow Whether to skip header row
     * @return array Import results
     */
    public function importFromString(string $csvContent, bool $skipFirstRow = true): array
    {
        $this->errors = [];
        $this->imported = [];
        $this->skipped = [];

        $lines = explode("\n", $csvContent);
        $rowNumber = 0;
        $headers = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $row = str_getcsv($line);
            $rowNumber++;

            // First row is headers
            if ($rowNumber === 1) {
                $headers = $this->normalizeHeaders($row);
                
                $missingColumns = $this->validateHeaders($headers);
                if (!empty($missingColumns)) {
                    $this->errors[] = 'Missing required columns: ' . implode(', ', $missingColumns);
                    return $this->getResults();
                }

                if ($skipFirstRow) {
                    continue;
                }
            }

            $this->processRow($row, $headers, $rowNumber);
        }

        return $this->getResults();
    }

    /**
     * Normalize header names
     * 
     * @param array $headers Raw headers
     * @return array Normalized headers
     */
    private function normalizeHeaders(array $headers): array
    {
        return array_map(function ($header) {
            return strtolower(trim(str_replace([' ', '-'], '_', $header)));
        }, $headers);
    }

    /**
     * Validate that required headers exist
     * 
     * @param array $headers Normalized headers
     * @return array Missing required columns
     */
    private function validateHeaders(array $headers): array
    {
        $missing = [];
        foreach (self::REQUIRED_COLUMNS as $required) {
            if (!in_array($required, $headers)) {
                $missing[] = $required;
            }
        }
        return $missing;
    }

    /**
     * Process a single CSV row
     * 
     * @param array $row CSV row data
     * @param array $headers Column headers
     * @param int $rowNumber Row number for error reporting
     */
    private function processRow(array $row, array $headers, int $rowNumber): void
    {
        // Map row data to headers
        $data = [];
        foreach ($headers as $index => $header) {
            $data[$header] = $row[$index] ?? '';
        }

        // Validate row data
        $validationErrors = $this->validateRowData($data, $rowNumber);
        if (!empty($validationErrors)) {
            $this->skipped[] = [
                'row' => $rowNumber,
                'sku' => $data['sku'] ?? 'N/A',
                'errors' => $validationErrors
            ];
            return;
        }

        // Check for duplicate SKU
        if ($this->skuExists($data['sku'])) {
            $this->skipped[] = [
                'row' => $rowNumber,
                'sku' => $data['sku'],
                'errors' => ['SKU already exists']
            ];
            return;
        }

        // Prepare product data
        $productData = $this->prepareProductData($data);

        try {
            $productId = $this->productModel->createProduct($productData);
            $this->imported[] = [
                'row' => $rowNumber,
                'sku' => $data['sku'],
                'id' => $productId,
                'name' => $data['name']
            ];
        } catch (\Exception $e) {
            $this->skipped[] = [
                'row' => $rowNumber,
                'sku' => $data['sku'],
                'errors' => [$e->getMessage()]
            ];
        }
    }

    /**
     * Validate row data
     * Requirements: 6.4 - Data validation
     * 
     * @param array $data Row data
     * @param int $rowNumber Row number
     * @return array Validation errors
     */
    private function validateRowData(array $data, int $rowNumber): array
    {
        $errors = [];

        // Required fields
        if (empty($data['sku'])) {
            $errors[] = 'SKU is required';
        }

        if (empty($data['name'])) {
            $errors[] = 'Name is required';
        }

        if (empty($data['price']) || !is_numeric($data['price']) || (float) $data['price'] < 0) {
            $errors[] = 'Valid price is required';
        }

        if (empty($data['category'])) {
            $errors[] = 'Category is required';
        } elseif (!$this->getCategoryId($data['category'])) {
            $errors[] = "Category '{$data['category']}' not found";
        }

        // Optional field validation
        if (!empty($data['sale_price'])) {
            if (!is_numeric($data['sale_price']) || (float) $data['sale_price'] < 0) {
                $errors[] = 'Sale price must be a positive number';
            } elseif ((float) $data['sale_price'] >= (float) $data['price']) {
                $errors[] = 'Sale price must be less than regular price';
            }
        }

        if (isset($data['stock_quantity']) && $data['stock_quantity'] !== '') {
            if (!is_numeric($data['stock_quantity']) || (int) $data['stock_quantity'] < 0) {
                $errors[] = 'Stock quantity must be a non-negative integer';
            }
        }

        if (!empty($data['weight']) && !is_numeric($data['weight'])) {
            $errors[] = 'Weight must be a number';
        }

        return $errors;
    }

    /**
     * Prepare product data for insertion
     * 
     * @param array $data Raw row data
     * @return array Prepared product data
     */
    private function prepareProductData(array $data): array
    {
        $productData = [
            'sku' => trim($data['sku']),
            'name' => trim($data['name']),
            'price' => (float) $data['price'],
            'category_id' => $this->getCategoryId($data['category'])
        ];

        // Add optional fields with defaults
        foreach (self::OPTIONAL_COLUMNS as $column => $default) {
            if (isset($data[$column]) && $data[$column] !== '') {
                $value = trim($data[$column]);
                
                // Type conversion
                if (in_array($column, ['sale_price', 'weight'])) {
                    $productData[$column] = $value !== '' ? (float) $value : null;
                } elseif (in_array($column, ['stock_quantity', 'is_organic', 'is_active'])) {
                    $productData[$column] = (int) $value;
                } else {
                    $productData[$column] = $value;
                }
            } else {
                $productData[$column] = $default;
            }
        }

        return $productData;
    }

    /**
     * Get category ID from name, slug, or ID
     * 
     * @param string $category Category identifier
     * @return int|null Category ID or null
     */
    private function getCategoryId(string $category): ?int
    {
        $category = strtolower(trim($category));
        return $this->categoryCache[$category] ?? null;
    }

    /**
     * Check if SKU already exists
     * 
     * @param string $sku SKU to check
     * @return bool True if exists
     */
    private function skuExists(string $sku): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM products WHERE sku = :sku");
        $stmt->execute(['sku' => $sku]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Get import results
     * 
     * @return array Results array
     */
    public function getResults(): array
    {
        return [
            'success' => empty($this->errors),
            'imported_count' => count($this->imported),
            'skipped_count' => count($this->skipped),
            'imported' => $this->imported,
            'skipped' => $this->skipped,
            'errors' => $this->errors
        ];
    }

    /**
     * Get CSV template content
     * 
     * @return string CSV template
     */
    public static function getTemplate(): string
    {
        $headers = array_merge(self::REQUIRED_COLUMNS, array_keys(self::OPTIONAL_COLUMNS));
        
        $template = implode(',', $headers) . "\n";
        $template .= "SAMPLE-001,Ceylon Cinnamon Sticks 100g,15.99,Cinnamon Sticks,\"Premium Ceylon cinnamon sticks, hand-rolled\",\"100g pack of premium cinnamon\",12.99,50,0.1,\"10x5x3\",1,\"Sri Lanka\",\"premium,organic\",\"Ceylon Cinnamon Sticks | Premium Quality\",\"Buy premium Ceylon cinnamon sticks\",1\n";
        $template .= "SAMPLE-002,Cinnamon Powder 250g,12.99,Cinnamon Powder,\"Fine ground Ceylon cinnamon powder\",\"250g pack of cinnamon powder\",,100,0.25,\"15x10x5\",0,\"Sri Lanka\",\"powder,cooking\",,\"Buy Ceylon cinnamon powder\",1\n";
        
        return $template;
    }

    /**
     * Get template headers
     * 
     * @return array Headers
     */
    public static function getTemplateHeaders(): array
    {
        return array_merge(self::REQUIRED_COLUMNS, array_keys(self::OPTIONAL_COLUMNS));
    }

    /**
     * Validate CSV file before import
     * 
     * @param string $filepath Path to CSV file
     * @return array Validation results
     */
    public function validateFile(string $filepath): array
    {
        $errors = [];
        $warnings = [];
        $rowCount = 0;

        if (!file_exists($filepath)) {
            return ['valid' => false, 'errors' => ['File not found'], 'warnings' => [], 'row_count' => 0];
        }

        $handle = fopen($filepath, 'r');
        if ($handle === false) {
            return ['valid' => false, 'errors' => ['Cannot open file'], 'warnings' => [], 'row_count' => 0];
        }

        $headers = null;
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;

            if ($rowCount === 1) {
                $headers = $this->normalizeHeaders($row);
                $missingColumns = $this->validateHeaders($headers);
                if (!empty($missingColumns)) {
                    $errors[] = 'Missing required columns: ' . implode(', ', $missingColumns);
                }
                continue;
            }

            // Basic row validation
            if (count($row) !== count($headers)) {
                $warnings[] = "Row {$rowCount}: Column count mismatch";
            }
        }

        fclose($handle);

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'row_count' => $rowCount - 1 // Exclude header
        ];
    }
}
