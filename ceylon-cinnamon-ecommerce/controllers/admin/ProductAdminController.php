<?php
/**
 * Product Admin Controller
 * Handles product CRUD operations for admin
 * 
 * Requirements:
 * - 6.1: Store product details (SKU, stock, price, weight, dimensions)
 * - 6.5: Product categories and subcategories with CRUD operations
 */

declare(strict_types=1);

class ProductAdminController extends Controller
{
    private SessionManager $sessionManager;
    private Product $productModel;
    private Category $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
    }

    /**
     * Display product list
     * Requirements: 6.1, 6.5
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        // Get filter parameters
        $filters = [
            'search' => $this->input('search', ''),
            'category_id' => $this->input('category', ''),
            'status' => $this->input('status', '')
        ];
        
        $page = max(1, (int) $this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Build query for admin (include inactive products)
        $products = $this->getAdminProducts($filters, $limit, $offset);
        $categories = $this->categoryModel->getAll();

        $this->adminView('products/index', [
            'title' => 'Products - Admin',
            'products' => $products['products'],
            'total' => $products['total'],
            'pages' => $products['pages'],
            'currentPage' => $page,
            'categories' => $categories,
            'filters' => $filters,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create product form
     * Requirements: 6.1
     */
    public function create(): void
    {
        $this->sessionManager->start();
        
        $categories = $this->categoryModel->getAll();

        $this->adminView('products/create', [
            'title' => 'Add Product - Admin',
            'categories' => $categories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new product
     * Requirements: 6.1 - Store product details (SKU, stock, price, weight, dimensions)
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/products/create');
        }

        $this->sessionManager->start();

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/products/create');
            return;
        }

        // Collect and validate product data
        $data = $this->collectProductData();
        $errors = $this->validateProductData($data);

        if (!empty($errors)) {
            $this->adminView('products/create', [
                'title' => 'Add Product - Admin',
                'categories' => $this->categoryModel->getAll(),
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Create product
            $productId = $this->productModel->createProduct($data);

            // Handle image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $this->handleImageUploads($productId);
            }

            $this->sessionManager->setFlash('success', 'Product created successfully.');
            $this->redirect('/admin/products');
        } catch (Exception $e) {
            $this->adminView('products/create', [
                'title' => 'Add Product - Admin',
                'categories' => $this->categoryModel->getAll(),
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit product form
     * Requirements: 6.1
     */
    public function edit($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();
        
        $product = $this->productModel->find($id);
        
        if (!$product) {
            $this->redirect('/admin/products');
        }

        $categories = $this->categoryModel->getAll();
        $images = $this->productModel->getImages($id);

        $this->adminView('products/edit', [
            'title' => 'Edit Product - Admin',
            'product' => $product,
            'categories' => $categories,
            'images' => $images,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update product
     * Requirements: 6.1
     */
    public function update($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/products/' . $id . '/edit');
        }

        $this->sessionManager->start();

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/products/' . $id . '/edit');
            return;
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            $this->redirect('/admin/products');
        }

        // Collect and validate product data
        $data = $this->collectProductData();
        $errors = $this->validateProductData($data, $id);

        if (!empty($errors)) {
            $this->adminView('products/edit', [
                'title' => 'Edit Product - Admin',
                'product' => array_merge($product, $data),
                'categories' => $this->categoryModel->getAll(),
                'images' => $this->productModel->getImages($id),
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Update product
            $this->productModel->update($id, $data);

            // Handle new image uploads
            if (!empty($_FILES['images']['name'][0])) {
                $this->handleImageUploads($id);
            }

            $this->sessionManager->setFlash('success', 'Product updated successfully.');
            $this->redirect('/admin/products');
        } catch (Exception $e) {
            $this->adminView('products/edit', [
                'title' => 'Edit Product - Admin',
                'product' => array_merge($product, $data),
                'categories' => $this->categoryModel->getAll(),
                'images' => $this->productModel->getImages($id),
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete product
     */
    public function destroy($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();

        // For AJAX requests, check header token
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/products');
        }

        $product = $this->productModel->find($id);
        if (!$product) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Product not found'], 404);
            }
            $this->redirect('/admin/products');
        }

        try {
            // Delete product images first
            $this->deleteProductImages($id);
            
            // Delete product
            $this->productModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Product deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Product deleted successfully.');
            $this->redirect('/admin/products');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete product.');
            $this->redirect('/admin/products');
        }
    }

    /**
     * Collect product data from POST
     * 
     * @return array Product data
     */
    private function collectProductData(): array
    {
        return [
            'sku' => $this->sanitize($this->input('sku', '')),
            'name' => $this->sanitize($this->input('name', '')),
            'slug' => $this->sanitize($this->input('slug', '')),
            'description' => $this->input('description', ''),
            'short_description' => $this->sanitize($this->input('short_description', '')),
            'price' => (float) $this->input('price', 0),
            'sale_price' => $this->input('sale_price', '') !== '' ? (float) $this->input('sale_price') : null,
            'weight' => $this->input('weight', '') !== '' ? (float) $this->input('weight') : null,
            'dimensions' => $this->sanitize($this->input('dimensions', '')),
            'stock_quantity' => (int) $this->input('stock_quantity', 0),
            'category_id' => (int) $this->input('category_id', 0),
            'is_organic' => (int) $this->input('is_organic', 0),
            'origin' => $this->sanitize($this->input('origin', '')),
            'tags' => $this->sanitize($this->input('tags', '')),
            'meta_title' => $this->sanitize($this->input('meta_title', '')),
            'meta_description' => $this->sanitize($this->input('meta_description', '')),
            'is_active' => (int) $this->input('is_active', 1)
        ];
    }

    /**
     * Validate product data
     * Requirements: 6.1 - Validate required fields
     * 
     * @param array $data Product data
     * @param int|null $excludeId Product ID to exclude from SKU uniqueness check
     * @return array Validation errors
     */
    private function validateProductData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        // Required fields
        if (empty($data['sku'])) {
            $errors[] = 'SKU is required.';
        } elseif ($this->skuExists($data['sku'], $excludeId)) {
            $errors[] = 'SKU already exists.';
        }

        if (empty($data['name'])) {
            $errors[] = 'Product name is required.';
        }

        if ($data['price'] <= 0) {
            $errors[] = 'Price must be greater than zero.';
        }

        if ($data['category_id'] <= 0) {
            $errors[] = 'Category is required.';
        }

        if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
            $errors[] = 'Sale price must be less than regular price.';
        }

        if ($data['stock_quantity'] < 0) {
            $errors[] = 'Stock quantity cannot be negative.';
        }

        return $errors;
    }

    /**
     * Check if SKU exists
     * 
     * @param string $sku SKU to check
     * @param int|null $excludeId Product ID to exclude
     * @return bool True if SKU exists
     */
    private function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM products WHERE sku = :sku";
        $params = ['sku' => $sku];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Handle image uploads
     * 
     * @param int $productId Product ID
     */
    private function handleImageUploads(int $productId): void
    {
        $files = $_FILES['images'];
        $uploadDir = UPLOADS_PATH . '/products/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $existingImages = $this->productModel->getImages($productId);
        $isPrimary = empty($existingImages);

        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $tmpName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];
            $mimeType = mime_content_type($tmpName);

            // Validate file type
            if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
                continue;
            }

            // Generate unique filename
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $filename = 'product_' . $productId . '_' . uniqid() . '.' . $extension;
            $filepath = $uploadDir . $filename;

            if (move_uploaded_file($tmpName, $filepath)) {
                $this->productModel->addImage($productId, [
                    'image_url' => '/uploads/products/' . $filename,
                    'alt_text' => $this->sanitize($this->input('name', '')),
                    'is_primary' => $isPrimary ? 1 : 0,
                    'sort_order' => $i
                ]);
                $isPrimary = false;
            }
        }
    }

    /**
     * Delete product images
     * 
     * @param int $productId Product ID
     */
    private function deleteProductImages(int $productId): void
    {
        $images = $this->productModel->getImages($productId);
        
        foreach ($images as $image) {
            $filepath = PUBLIC_PATH . $image['image_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        // Delete from database
        $stmt = $this->db->prepare("DELETE FROM product_images WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $productId]);
    }

    /**
     * Get products for admin (including inactive)
     * 
     * @param array $filters Filter options
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Products with pagination
     */
    private function getAdminProducts(array $filters, int $limit, int $offset): array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.sku LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND p.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if ($filters['status'] !== '') {
            $sql .= " AND p.is_active = :status";
            $params['status'] = (int) $filters['status'];
        }

        // Count total
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Add pagination
        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'products' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int) ceil($total / $limit)
        ];
    }

    /**
     * Render admin view with layout
     */
    protected function adminView(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = VIEWS_PATH . '/admin/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Admin view '{$view}' not found.");
        }

        include $viewFile;
    }

    /**
     * Handle error redirect
     */
    private function handleError(string $message, string $redirect): void
    {
        $this->sessionManager->setFlash('error', $message);
        $this->redirect($redirect);
    }

    /**
     * Show CSV import form
     * Requirements: 6.4 - Bulk product import
     */
    public function showImport(): void
    {
        $this->sessionManager->start();

        $this->adminView('products/import', [
            'title' => 'Import Products - Admin',
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Process CSV import
     * Requirements: 6.4 - Bulk product import with data validation
     */
    public function import(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/products/import');
        }

        $this->sessionManager->start();

        // Validate CSRF token
        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/products/import');
            return;
        }

        // Check if file was uploaded
        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $this->adminView('products/import', [
                'title' => 'Import Products - Admin',
                'errors' => ['Please select a CSV file to upload.'],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        $file = $_FILES['csv_file'];

        // Validate file type
        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'])) {
            $this->adminView('products/import', [
                'title' => 'Import Products - Admin',
                'errors' => ['Invalid file type. Please upload a CSV file.'],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        // Process import
        $importer = new CsvProductImporter();
        $results = $importer->importFromFile($file['tmp_name']);

        $this->adminView('products/import_results', [
            'title' => 'Import Results - Admin',
            'results' => $results,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Download CSV template
     * Requirements: 6.4 - CSV template
     */
    public function downloadTemplate(): void
    {
        $template = CsvProductImporter::getTemplate();
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="product_import_template.csv"');
        header('Content-Length: ' . strlen($template));
        
        echo $template;
        exit;
    }
}
