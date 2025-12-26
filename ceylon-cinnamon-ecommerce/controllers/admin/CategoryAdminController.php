<?php
/**
 * Category Admin Controller
 * Handles product category CRUD operations for admin
 * 
 * Requirements:
 * - 6.5: Product categories and subcategories with CRUD operations
 */

declare(strict_types=1);

class CategoryAdminController extends Controller
{
    private SessionManager $sessionManager;
    private Category $categoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->categoryModel = new Category();
    }

    /**
     * Display category list
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        $categories = $this->categoryModel->getAll(false);
        
        // Add product count to each category
        foreach ($categories as &$category) {
            $category['product_count'] = $this->categoryModel->getProductCount((int)$category['id']);
        }

        $this->adminView('categories/index', [
            'title' => 'Categories - Admin',
            'categories' => $categories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create category form
     */
    public function create(): void
    {
        $this->sessionManager->start();
        
        $parentCategories = $this->categoryModel->getParentCategories(false);

        $this->adminView('categories/create', [
            'title' => 'Add Category - Admin',
            'parentCategories' => $parentCategories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new category
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/categories/create');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/categories/create');
            return;
        }

        $data = $this->collectCategoryData();
        $errors = $this->validateCategoryData($data);

        if (!empty($errors)) {
            $this->adminView('categories/create', [
                'title' => 'Add Category - Admin',
                'parentCategories' => $this->categoryModel->getParentCategories(false),
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            $this->categoryModel->createCategory($data);
            $this->sessionManager->setFlash('success', 'Category created successfully.');
            $this->redirect('/admin/categories');
        } catch (Exception $e) {
            $this->adminView('categories/create', [
                'title' => 'Add Category - Admin',
                'parentCategories' => $this->categoryModel->getParentCategories(false),
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit category form
     */
    public function edit($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();
        
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            $this->sessionManager->setFlash('error', 'Category not found.');
            $this->redirect('/admin/categories');
            return;
        }

        $parentCategories = $this->categoryModel->getParentCategories(false);
        // Remove current category from parent options to prevent self-reference
        $parentCategories = array_filter($parentCategories, fn($c) => $c['id'] != $id);

        $this->adminView('categories/edit', [
            'title' => 'Edit Category - Admin',
            'category' => $category,
            'parentCategories' => $parentCategories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update category
     */
    public function update($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/categories/' . $id . '/edit');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->sessionManager->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/categories/' . $id . '/edit');
            return;
        }

        $category = $this->categoryModel->find($id);
        if (!$category) {
            $this->sessionManager->setFlash('error', 'Category not found.');
            $this->redirect('/admin/categories');
            return;
        }

        $data = $this->collectCategoryData();
        $errors = $this->validateCategoryData($data, $id);

        if (!empty($errors)) {
            $parentCategories = $this->categoryModel->getParentCategories(false);
            $parentCategories = array_filter($parentCategories, fn($c) => $c['id'] != $id);
            
            $this->adminView('categories/edit', [
                'title' => 'Edit Category - Admin',
                'category' => array_merge($category, $data),
                'parentCategories' => $parentCategories,
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            $this->categoryModel->update($id, $data);
            $this->sessionManager->setFlash('success', 'Category updated successfully.');
            $this->redirect('/admin/categories');
        } catch (Exception $e) {
            $parentCategories = $this->categoryModel->getParentCategories(false);
            $parentCategories = array_filter($parentCategories, fn($c) => $c['id'] != $id);
            
            $this->adminView('categories/edit', [
                'title' => 'Edit Category - Admin',
                'category' => array_merge($category, $data),
                'parentCategories' => $parentCategories,
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete category
     */
    public function destroy($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/categories');
            return;
        }

        $category = $this->categoryModel->find($id);
        if (!$category) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Category not found'], 404);
            }
            $this->redirect('/admin/categories');
            return;
        }

        // Check if category has products
        $productCount = $this->categoryModel->getProductCount($id);
        if ($productCount > 0) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Cannot delete category with products. Move or delete products first.'], 400);
            }
            $this->sessionManager->setFlash('error', 'Cannot delete category with products.');
            $this->redirect('/admin/categories');
            return;
        }

        // Check if category has children
        $children = $this->categoryModel->getChildren($id, false);
        if (!empty($children)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Cannot delete category with subcategories.'], 400);
            }
            $this->sessionManager->setFlash('error', 'Cannot delete category with subcategories.');
            $this->redirect('/admin/categories');
            return;
        }

        try {
            $this->categoryModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Category deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Category deleted successfully.');
            $this->redirect('/admin/categories');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete category.');
            $this->redirect('/admin/categories');
        }
    }

    /**
     * Collect category data from POST
     */
    private function collectCategoryData(): array
    {
        return [
            'name' => $this->sanitize($this->input('name', '')),
            'slug' => $this->sanitize($this->input('slug', '')),
            'description' => $this->sanitize($this->input('description', '')),
            'parent_id' => $this->input('parent_id', '') !== '' ? (int)$this->input('parent_id') : null,
            'image_url' => $this->sanitize($this->input('image_url', '')),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0)
        ];
    }

    /**
     * Validate category data
     */
    private function validateCategoryData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors[] = 'Category name is required.';
        } elseif (strlen($data['name']) > 100) {
            $errors[] = 'Category name must be 100 characters or less.';
        }

        // Check slug uniqueness
        if (!empty($data['slug'])) {
            $existing = $this->categoryModel->findBySlug($data['slug']);
            if ($existing && ($excludeId === null || $existing['id'] != $excludeId)) {
                $errors[] = 'Slug already exists.';
            }
        }

        return $errors;
    }

    /**
     * Render admin view
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
}
