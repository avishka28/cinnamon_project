<?php
/**
 * Content Admin Controller
 * Handles blog post and content management for admin/content managers
 * 
 * Requirements:
 * - 8.1: Blog post with categories and tags
 * - 8.5: Content publishing
 * - 8.6: Content scheduling
 */

declare(strict_types=1);

class ContentAdminController extends Controller
{
    private SessionManager $sessionManager;
    private BlogPost $blogPostModel;
    private BlogCategory $blogCategoryModel;
    private Certificate $certificateModel;
    private GalleryItem $galleryItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->blogPostModel = new BlogPost();
        $this->blogCategoryModel = new BlogCategory();
        $this->certificateModel = new Certificate();
        $this->galleryItemModel = new GalleryItem();
    }

    /**
     * Display blog posts list
     * Requirements: 8.1
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        $filters = [
            'search' => $this->input('search', ''),
            'category_id' => $this->input('category', ''),
            'status' => $this->input('status', '')
        ];
        
        $page = max(1, (int) $this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $posts = $this->blogPostModel->getAllForAdmin($filters, $limit, $offset);
        $categories = $this->blogCategoryModel->getAll();

        $this->adminView('content/posts/index', [
            'title' => 'Blog Posts - Admin',
            'posts' => $posts['posts'],
            'total' => $posts['total'],
            'pages' => $posts['pages'],
            'currentPage' => $page,
            'categories' => $categories,
            'filters' => $filters,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create blog post form
     * Requirements: 8.1
     */
    public function create(): void
    {
        $this->sessionManager->start();
        
        $categories = $this->blogCategoryModel->getActive();

        $this->adminView('content/posts/create', [
            'title' => 'Create Blog Post - Admin',
            'categories' => $categories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new blog post
     * Requirements: 8.1 - Create blog post with categories and tags
     */
    public function store(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/posts/create');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/posts/create');
            return;
        }

        $data = $this->collectPostData();
        $errors = $this->validatePostData($data);

        if (!empty($errors)) {
            $this->adminView('content/posts/create', [
                'title' => 'Create Blog Post - Admin',
                'categories' => $this->blogCategoryModel->getActive(),
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->blogPostModel->generateSlug($data['title']);
            }

            // Set author
            $data['author_id'] = $this->sessionManager->getUserId();

            // Handle featured image upload
            if (!empty($_FILES['featured_image']['name'])) {
                $data['featured_image'] = $this->handleImageUpload($_FILES['featured_image']);
            }

            // Handle scheduling
            if ($data['status'] === 'scheduled' && !empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s', strtotime($data['published_at']));
            } elseif ($data['status'] === 'published') {
                $data['published_at'] = date('Y-m-d H:i:s');
            } else {
                $data['published_at'] = null;
            }

            $this->blogPostModel->createPost($data);

            $this->sessionManager->setFlash('success', 'Blog post created successfully.');
            $this->redirect('/admin/content/posts');
        } catch (Exception $e) {
            $this->adminView('content/posts/create', [
                'title' => 'Create Blog Post - Admin',
                'categories' => $this->blogCategoryModel->getActive(),
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit blog post form
     */
    public function edit(int $id): void
    {
        $this->sessionManager->start();
        
        $post = $this->blogPostModel->find($id);
        
        if (!$post) {
            $this->redirect('/admin/content/posts');
        }

        $categories = $this->blogCategoryModel->getActive();

        $this->adminView('content/posts/edit', [
            'title' => 'Edit Blog Post - Admin',
            'post' => $post,
            'categories' => $categories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update blog post
     */
    public function update(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/posts/' . $id . '/edit');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/posts/' . $id . '/edit');
            return;
        }

        $post = $this->blogPostModel->find($id);
        if (!$post) {
            $this->redirect('/admin/content/posts');
        }

        $data = $this->collectPostData();
        $errors = $this->validatePostData($data, $id);

        if (!empty($errors)) {
            $this->adminView('content/posts/edit', [
                'title' => 'Edit Blog Post - Admin',
                'post' => array_merge($post, $data),
                'categories' => $this->blogCategoryModel->getActive(),
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = $this->blogPostModel->generateSlug($data['title'], $id);
            }

            // Handle featured image upload
            if (!empty($_FILES['featured_image']['name'])) {
                $data['featured_image'] = $this->handleImageUpload($_FILES['featured_image']);
                // Delete old image
                if (!empty($post['featured_image'])) {
                    $this->deleteImage($post['featured_image']);
                }
            } else {
                $data['featured_image'] = $post['featured_image'];
            }

            // Handle scheduling
            if ($data['status'] === 'scheduled' && !empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s', strtotime($data['published_at']));
            } elseif ($data['status'] === 'published' && empty($post['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            } elseif ($data['status'] === 'published') {
                $data['published_at'] = $post['published_at'];
            } else {
                $data['published_at'] = null;
            }

            $this->blogPostModel->updatePost($id, $data);

            $this->sessionManager->setFlash('success', 'Blog post updated successfully.');
            $this->redirect('/admin/content/posts');
        } catch (Exception $e) {
            $this->adminView('content/posts/edit', [
                'title' => 'Edit Blog Post - Admin',
                'post' => array_merge($post, $data),
                'categories' => $this->blogCategoryModel->getActive(),
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete blog post
     */
    public function destroy(int $id): void
    {
        $this->sessionManager->start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/content/posts');
        }

        $post = $this->blogPostModel->find($id);
        if (!$post) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Post not found'], 404);
            }
            $this->redirect('/admin/content/posts');
        }

        try {
            // Delete featured image
            if (!empty($post['featured_image'])) {
                $this->deleteImage($post['featured_image']);
            }
            
            $this->blogPostModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Post deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Blog post deleted successfully.');
            $this->redirect('/admin/content/posts');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete post.');
            $this->redirect('/admin/content/posts');
        }
    }

    // ==================== Blog Categories ====================

    /**
     * Display blog categories list
     */
    public function categories(): void
    {
        $this->sessionManager->start();
        
        $categories = $this->blogCategoryModel->getAll();

        $this->adminView('content/categories/index', [
            'title' => 'Blog Categories - Admin',
            'categories' => $categories,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create category form
     */
    public function createCategory(): void
    {
        $this->sessionManager->start();

        $this->adminView('content/categories/create', [
            'title' => 'Create Category - Admin',
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new category
     */
    public function storeCategory(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/categories/create');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/categories/create');
            return;
        }

        $data = [
            'name' => $this->sanitize($this->input('name', '')),
            'slug' => $this->sanitize($this->input('slug', '')),
            'description' => $this->input('description', ''),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Category name is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/categories/create', [
                'title' => 'Create Category - Admin',
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            if (empty($data['slug'])) {
                $data['slug'] = $this->blogCategoryModel->generateSlug($data['name']);
            }

            $this->blogCategoryModel->createCategory($data);

            $this->sessionManager->setFlash('success', 'Category created successfully.');
            $this->redirect('/admin/content/categories');
        } catch (Exception $e) {
            $this->adminView('content/categories/create', [
                'title' => 'Create Category - Admin',
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit category form
     */
    public function editCategory(int $id): void
    {
        $this->sessionManager->start();
        
        $category = $this->blogCategoryModel->find($id);
        
        if (!$category) {
            $this->redirect('/admin/content/categories');
        }

        $this->adminView('content/categories/edit', [
            'title' => 'Edit Category - Admin',
            'category' => $category,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update category
     */
    public function updateCategory(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/categories/' . $id . '/edit');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/categories/' . $id . '/edit');
            return;
        }

        $category = $this->blogCategoryModel->find($id);
        if (!$category) {
            $this->redirect('/admin/content/categories');
        }

        $data = [
            'name' => $this->sanitize($this->input('name', '')),
            'slug' => $this->sanitize($this->input('slug', '')),
            'description' => $this->input('description', ''),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        $errors = [];
        if (empty($data['name'])) {
            $errors[] = 'Category name is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/categories/edit', [
                'title' => 'Edit Category - Admin',
                'category' => array_merge($category, $data),
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            if (empty($data['slug'])) {
                $data['slug'] = $this->blogCategoryModel->generateSlug($data['name'], $id);
            }

            $this->blogCategoryModel->updateCategory($id, $data);

            $this->sessionManager->setFlash('success', 'Category updated successfully.');
            $this->redirect('/admin/content/categories');
        } catch (Exception $e) {
            $this->adminView('content/categories/edit', [
                'title' => 'Edit Category - Admin',
                'category' => array_merge($category, $data),
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete category
     */
    public function destroyCategory(int $id): void
    {
        $this->sessionManager->start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/content/categories');
        }

        $category = $this->blogCategoryModel->find($id);
        if (!$category) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Category not found'], 404);
            }
            $this->redirect('/admin/content/categories');
        }

        if (!$this->blogCategoryModel->canDelete($id)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Cannot delete category with posts'], 400);
            }
            $this->sessionManager->setFlash('error', 'Cannot delete category that has posts.');
            $this->redirect('/admin/content/categories');
        }

        try {
            $this->blogCategoryModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Category deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Category deleted successfully.');
            $this->redirect('/admin/content/categories');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete category.');
            $this->redirect('/admin/content/categories');
        }
    }

    // ==================== Certificates ====================

    /**
     * Display certificates list
     * Requirements: 8.2
     */
    public function certificates(): void
    {
        $this->sessionManager->start();
        
        $certificates = $this->certificateModel->getAllForAdmin();

        $this->adminView('content/certificates/index', [
            'title' => 'Certificates - Admin',
            'certificates' => $certificates,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create certificate form
     */
    public function createCertificate(): void
    {
        $this->sessionManager->start();

        $this->adminView('content/certificates/create', [
            'title' => 'Add Certificate - Admin',
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new certificate
     * Requirements: 8.2 - Certificate file upload
     */
    public function storeCertificate(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/certificates/create');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/certificates/create');
            return;
        }

        $data = [
            'title' => $this->sanitize($this->input('title', '')),
            'description' => $this->input('description', ''),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        if (empty($_FILES['certificate_file']['name'])) {
            $errors[] = 'Certificate file is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/certificates/create', [
                'title' => 'Add Certificate - Admin',
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            $fileData = $this->handleCertificateUpload($_FILES['certificate_file']);
            $data['file_url'] = $fileData['file_url'];
            $data['file_type'] = $fileData['file_type'];
            $data['thumbnail_url'] = $fileData['thumbnail_url'] ?? null;

            $this->certificateModel->createCertificate($data);

            $this->sessionManager->setFlash('success', 'Certificate added successfully.');
            $this->redirect('/admin/content/certificates');
        } catch (Exception $e) {
            $this->adminView('content/certificates/create', [
                'title' => 'Add Certificate - Admin',
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit certificate form
     */
    public function editCertificate(int $id): void
    {
        $this->sessionManager->start();
        
        $certificate = $this->certificateModel->find($id);
        
        if (!$certificate) {
            $this->redirect('/admin/content/certificates');
        }

        $this->adminView('content/certificates/edit', [
            'title' => 'Edit Certificate - Admin',
            'certificate' => $certificate,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update certificate
     */
    public function updateCertificate(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/certificates/' . $id . '/edit');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/certificates/' . $id . '/edit');
            return;
        }

        $certificate = $this->certificateModel->find($id);
        if (!$certificate) {
            $this->redirect('/admin/content/certificates');
        }

        $data = [
            'title' => $this->sanitize($this->input('title', '')),
            'description' => $this->input('description', ''),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0),
            'file_url' => $certificate['file_url'],
            'file_type' => $certificate['file_type'],
            'thumbnail_url' => $certificate['thumbnail_url']
        ];

        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/certificates/edit', [
                'title' => 'Edit Certificate - Admin',
                'certificate' => array_merge($certificate, $data),
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Handle new file upload
            if (!empty($_FILES['certificate_file']['name'])) {
                $fileData = $this->handleCertificateUpload($_FILES['certificate_file']);
                $data['file_url'] = $fileData['file_url'];
                $data['file_type'] = $fileData['file_type'];
                $data['thumbnail_url'] = $fileData['thumbnail_url'] ?? null;
                
                // Delete old file
                $this->deleteFile($certificate['file_url']);
                if ($certificate['thumbnail_url']) {
                    $this->deleteFile($certificate['thumbnail_url']);
                }
            }

            $this->certificateModel->updateCertificate($id, $data);

            $this->sessionManager->setFlash('success', 'Certificate updated successfully.');
            $this->redirect('/admin/content/certificates');
        } catch (Exception $e) {
            $this->adminView('content/certificates/edit', [
                'title' => 'Edit Certificate - Admin',
                'certificate' => array_merge($certificate, $data),
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete certificate
     */
    public function destroyCertificate(int $id): void
    {
        $this->sessionManager->start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/content/certificates');
        }

        $certificate = $this->certificateModel->find($id);
        if (!$certificate) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Certificate not found'], 404);
            }
            $this->redirect('/admin/content/certificates');
        }

        try {
            // Delete files
            $this->deleteFile($certificate['file_url']);
            if ($certificate['thumbnail_url']) {
                $this->deleteFile($certificate['thumbnail_url']);
            }
            
            $this->certificateModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Certificate deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Certificate deleted successfully.');
            $this->redirect('/admin/content/certificates');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete certificate.');
            $this->redirect('/admin/content/certificates');
        }
    }

    // ==================== Gallery ====================

    /**
     * Display gallery items list
     * Requirements: 8.3
     */
    public function gallery(): void
    {
        $this->sessionManager->start();
        
        $items = $this->galleryItemModel->getAllForAdmin();

        $this->adminView('content/gallery/index', [
            'title' => 'Gallery - Admin',
            'items' => $items,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create gallery item form
     */
    public function createGalleryItem(): void
    {
        $this->sessionManager->start();

        $this->adminView('content/gallery/create', [
            'title' => 'Add Gallery Item - Admin',
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new gallery item
     * Requirements: 8.3 - Gallery management for images and videos
     */
    public function storeGalleryItem(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/gallery/create');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/gallery/create');
            return;
        }

        $data = [
            'title' => $this->sanitize($this->input('title', '')),
            'description' => $this->input('description', ''),
            'file_type' => $this->input('file_type', 'image'),
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        if (empty($_FILES['gallery_file']['name'])) {
            $errors[] = 'File is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/gallery/create', [
                'title' => 'Add Gallery Item - Admin',
                'errors' => $errors,
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            $fileData = $this->handleGalleryUpload($_FILES['gallery_file'], $data['file_type']);
            $data['file_url'] = $fileData['file_url'];
            $data['thumbnail_url'] = $fileData['thumbnail_url'] ?? null;

            $this->galleryItemModel->createItem($data);

            $this->sessionManager->setFlash('success', 'Gallery item added successfully.');
            $this->redirect('/admin/content/gallery');
        } catch (Exception $e) {
            $this->adminView('content/gallery/create', [
                'title' => 'Add Gallery Item - Admin',
                'errors' => [$e->getMessage()],
                'old' => $data,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Show edit gallery item form
     */
    public function editGalleryItem(int $id): void
    {
        $this->sessionManager->start();
        
        $item = $this->galleryItemModel->find($id);
        
        if (!$item) {
            $this->redirect('/admin/content/gallery');
        }

        $this->adminView('content/gallery/edit', [
            'title' => 'Edit Gallery Item - Admin',
            'item' => $item,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update gallery item
     */
    public function updateGalleryItem(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content/gallery/' . $id . '/edit');
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/content/gallery/' . $id . '/edit');
            return;
        }

        $item = $this->galleryItemModel->find($id);
        if (!$item) {
            $this->redirect('/admin/content/gallery');
        }

        $data = [
            'title' => $this->sanitize($this->input('title', '')),
            'description' => $this->input('description', ''),
            'file_type' => $item['file_type'],
            'is_active' => (int) $this->input('is_active', 1),
            'sort_order' => (int) $this->input('sort_order', 0),
            'file_url' => $item['file_url'],
            'thumbnail_url' => $item['thumbnail_url']
        ];

        $errors = [];
        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        if (!empty($errors)) {
            $this->adminView('content/gallery/edit', [
                'title' => 'Edit Gallery Item - Admin',
                'item' => array_merge($item, $data),
                'errors' => $errors,
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
            return;
        }

        try {
            // Handle new file upload
            if (!empty($_FILES['gallery_file']['name'])) {
                $fileData = $this->handleGalleryUpload($_FILES['gallery_file'], $data['file_type']);
                $data['file_url'] = $fileData['file_url'];
                $data['thumbnail_url'] = $fileData['thumbnail_url'] ?? null;
                
                // Delete old files
                $this->deleteFile($item['file_url']);
                if ($item['thumbnail_url']) {
                    $this->deleteFile($item['thumbnail_url']);
                }
            }

            $this->galleryItemModel->updateItem($id, $data);

            $this->sessionManager->setFlash('success', 'Gallery item updated successfully.');
            $this->redirect('/admin/content/gallery');
        } catch (Exception $e) {
            $this->adminView('content/gallery/edit', [
                'title' => 'Edit Gallery Item - Admin',
                'item' => array_merge($item, $data),
                'errors' => [$e->getMessage()],
                'csrf_token' => $this->sessionManager->getCsrfToken()
            ]);
        }
    }

    /**
     * Delete gallery item
     */
    public function destroyGalleryItem(int $id): void
    {
        $this->sessionManager->start();

        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $this->input('csrf_token', '');
        if (!$this->sessionManager->validateCsrfToken($token)) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Invalid security token'], 403);
            }
            $this->redirect('/admin/content/gallery');
        }

        $item = $this->galleryItemModel->find($id);
        if (!$item) {
            if ($this->isAjax()) {
                $this->json(['error' => 'Item not found'], 404);
            }
            $this->redirect('/admin/content/gallery');
        }

        try {
            // Delete files
            $this->deleteFile($item['file_url']);
            if ($item['thumbnail_url']) {
                $this->deleteFile($item['thumbnail_url']);
            }
            
            $this->galleryItemModel->delete($id);

            if ($this->isAjax()) {
                $this->json(['success' => true, 'message' => 'Item deleted successfully']);
            }
            
            $this->sessionManager->setFlash('success', 'Gallery item deleted successfully.');
            $this->redirect('/admin/content/gallery');
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['error' => $e->getMessage()], 500);
            }
            $this->sessionManager->setFlash('error', 'Failed to delete item.');
            $this->redirect('/admin/content/gallery');
        }
    }

    // ==================== Helper Methods ====================

    /**
     * Collect post data from form
     */
    private function collectPostData(): array
    {
        return [
            'title' => $this->sanitize($this->input('title', '')),
            'slug' => $this->sanitize($this->input('slug', '')),
            'excerpt' => $this->sanitize($this->input('excerpt', '')),
            'content' => $this->input('content', ''),
            'category_id' => (int) $this->input('category_id', 0),
            'tags' => $this->sanitize($this->input('tags', '')),
            'meta_title' => $this->sanitize($this->input('meta_title', '')),
            'meta_description' => $this->sanitize($this->input('meta_description', '')),
            'status' => $this->input('status', 'draft'),
            'published_at' => $this->input('published_at', '')
        ];
    }

    /**
     * Validate post data
     */
    private function validatePostData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors[] = 'Title is required.';
        }

        if (empty($data['content'])) {
            $errors[] = 'Content is required.';
        }

        if (!empty($data['slug']) && $this->blogPostModel->slugExists($data['slug'], $excludeId)) {
            $errors[] = 'Slug already exists.';
        }

        if (!in_array($data['status'], ['draft', 'published', 'scheduled'])) {
            $errors[] = 'Invalid status.';
        }

        if ($data['status'] === 'scheduled' && empty($data['published_at'])) {
            $errors[] = 'Publish date is required for scheduled posts.';
        }

        return $errors;
    }

    /**
     * Handle image upload
     */
    private function handleImageUpload(array $file): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $uploadDir = UPLOADS_PATH . '/blog/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $mimeType = mime_content_type($file['tmp_name']);
        if (!in_array($mimeType, ALLOWED_IMAGE_TYPES)) {
            throw new InvalidArgumentException('Invalid image type.');
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'blog_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return '/uploads/blog/' . $filename;
        }

        return null;
    }

    /**
     * Delete image file
     */
    private function deleteImage(string $imageUrl): void
    {
        $filepath = PUBLIC_PATH . $imageUrl;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
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

    /**
     * Handle error redirect
     */
    private function handleError(string $message, string $redirect): void
    {
        $this->sessionManager->setFlash('error', $message);
        $this->redirect($redirect);
    }

    /**
     * Handle certificate file upload
     * Requirements: 8.2 - Certificate file upload (PDF and images)
     */
    private function handleCertificateUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload failed.');
        }

        $uploadDir = UPLOADS_PATH . '/certificates/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $mimeType = mime_content_type($file['tmp_name']);
        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($mimeType, $allowedTypes)) {
            throw new InvalidArgumentException('Invalid file type. Allowed: PDF, JPEG, PNG, GIF.');
        }

        $fileType = ($mimeType === 'application/pdf') ? 'pdf' : 'image';
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'cert_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }

        return [
            'file_url' => '/uploads/certificates/' . $filename,
            'file_type' => $fileType,
            'thumbnail_url' => ($fileType === 'image') ? '/uploads/certificates/' . $filename : null
        ];
    }

    /**
     * Handle gallery file upload
     * Requirements: 8.3 - Gallery management for images and videos
     */
    private function handleGalleryUpload(array $file, string $type): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('File upload failed.');
        }

        $uploadDir = UPLOADS_PATH . '/gallery/';
        
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $mimeType = mime_content_type($file['tmp_name']);
        
        if ($type === 'image') {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedTypes)) {
                throw new InvalidArgumentException('Invalid image type. Allowed: JPEG, PNG, GIF, WebP.');
            }
        } else {
            $allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            if (!in_array($mimeType, $allowedTypes)) {
                throw new InvalidArgumentException('Invalid video type. Allowed: MP4, WebM, OGG.');
            }
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'gallery_' . uniqid() . '.' . $extension;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new RuntimeException('Failed to save uploaded file.');
        }

        return [
            'file_url' => '/uploads/gallery/' . $filename,
            'thumbnail_url' => ($type === 'image') ? '/uploads/gallery/' . $filename : null
        ];
    }

    /**
     * Delete file from uploads
     */
    private function deleteFile(string $fileUrl): void
    {
        $filepath = PUBLIC_PATH . $fileUrl;
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
}
