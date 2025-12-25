<?php
/**
 * BlogCategory Model
 * Handles blog category CRUD operations
 * 
 * Requirements:
 * - 8.1: Blog post categories
 */

declare(strict_types=1);

class BlogCategory extends Model
{
    protected string $table = 'blog_categories';

    /**
     * Get all active categories
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all categories for admin
     */
    public function getAll(): array
    {
        $sql = "SELECT bc.*, 
                       (SELECT COUNT(*) FROM blog_posts bp WHERE bp.category_id = bc.id) as post_count
                FROM {$this->table} bc
                ORDER BY bc.sort_order ASC, bc.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get category by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create a new category
     */
    public function createCategory(array $data): int
    {
        $sql = "INSERT INTO {$this->table} (name, slug, description, is_active, sort_order)
                VALUES (:name, :slug, :description, :is_active, :sort_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                name = :name,
                slug = :slug,
                description = :description,
                is_active = :is_active,
                sort_order = :sort_order
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'slug' => $data['slug'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    /**
     * Check if slug exists
     */
    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE slug = :slug";
        $params = ['slug' => $slug];
        
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Generate unique slug from name
     */
    public function generateSlug(string $name, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Check if category can be deleted (no posts)
     */
    public function canDelete(int $id): bool
    {
        $sql = "SELECT COUNT(*) FROM blog_posts WHERE category_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return (int) $stmt->fetchColumn() === 0;
    }
}
