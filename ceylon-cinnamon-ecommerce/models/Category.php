<?php
/**
 * Category Model
 * Handles product categories with hierarchical support
 * 
 * Requirements:
 * - 6.5: Support product categories and subcategories with CRUD operations
 * - 1.2: Category filtering for products
 */

declare(strict_types=1);

class Category extends Model
{
    protected string $table = 'categories';

    /**
     * Get all active categories
     * 
     * @param bool $activeOnly Only return active categories
     * @return array List of categories
     */
    public function getAll(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table}";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get category by slug
     * 
     * @param string $slug Category slug
     * @return array|null Category data or null
     */
    public function findBySlug(string $slug): ?array
    {
        return $this->findBy('slug', $slug);
    }

    /**
     * Get parent categories (top-level)
     * 
     * @param bool $activeOnly Only return active categories
     * @return array List of parent categories
     */
    public function getParentCategories(bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id IS NULL";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }


    /**
     * Get child categories for a parent
     * 
     * @param int $parentId Parent category ID
     * @param bool $activeOnly Only return active categories
     * @return array List of child categories
     */
    public function getChildren(int $parentId, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE parent_id = :parent_id";
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['parent_id' => $parentId]);
        return $stmt->fetchAll();
    }

    /**
     * Get category tree (hierarchical structure)
     * 
     * @param bool $activeOnly Only return active categories
     * @return array Hierarchical category tree
     */
    public function getTree(bool $activeOnly = true): array
    {
        $categories = $this->getAll($activeOnly);
        return $this->buildTree($categories);
    }

    /**
     * Build hierarchical tree from flat category list
     * 
     * @param array $categories Flat list of categories
     * @param int|null $parentId Parent ID to start from
     * @return array Hierarchical tree
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $children = $this->buildTree($categories, (int)$category['id']);
                if ($children) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }
        return $tree;
    }

    /**
     * Get all descendant category IDs (for filtering)
     * 
     * @param int $categoryId Category ID
     * @return array List of category IDs including the given one
     */
    public function getDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $children = $this->getChildren($categoryId, false);
        
        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantIds((int)$child['id']));
        }
        
        return $ids;
    }

    /**
     * Get breadcrumb path for a category
     * 
     * @param int $categoryId Category ID
     * @return array Breadcrumb path from root to category
     */
    public function getBreadcrumb(int $categoryId): array
    {
        $breadcrumb = [];
        $category = $this->find($categoryId);
        
        while ($category) {
            array_unshift($breadcrumb, $category);
            if ($category['parent_id']) {
                $category = $this->find((int)$category['parent_id']);
            } else {
                break;
            }
        }
        
        return $breadcrumb;
    }

    /**
     * Create a new category
     * 
     * @param array $data Category data
     * @return int New category ID
     */
    public function createCategory(array $data): int
    {
        $this->validateCategoryData($data);
        
        $categoryData = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
            'description' => $data['description'] ?? null,
            'parent_id' => $data['parent_id'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ];
        
        return $this->create($categoryData);
    }

    /**
     * Generate URL-friendly slug from name
     * 
     * @param string $name Category name
     * @return string URL slug
     */
    public function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $baseSlug = $slug;
        $counter = 1;
        while ($this->findBySlug($slug)) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Validate category data
     * 
     * @param array $data Category data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateCategoryData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException("Category name is required");
        }
        
        if (strlen($data['name']) > 100) {
            throw new InvalidArgumentException("Category name must be 100 characters or less");
        }
    }

    /**
     * Get product count for a category
     * 
     * @param int $categoryId Category ID
     * @param bool $includeChildren Include products from child categories
     * @return int Product count
     */
    public function getProductCount(int $categoryId, bool $includeChildren = false): int
    {
        if ($includeChildren) {
            $ids = $this->getDescendantIds($categoryId);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT COUNT(*) FROM products WHERE category_id IN ({$placeholders}) AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($ids);
        } else {
            $sql = "SELECT COUNT(*) FROM products WHERE category_id = :category_id AND is_active = 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['category_id' => $categoryId]);
        }
        
        return (int) $stmt->fetchColumn();
    }
}
