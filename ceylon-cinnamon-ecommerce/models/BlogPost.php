<?php
/**
 * BlogPost Model
 * Handles blog post CRUD operations
 * 
 * Requirements:
 * - 8.1: Blog post with categories and tags
 * - 8.5: Content publishing
 * - 8.6: Content scheduling
 */

declare(strict_types=1);

class BlogPost extends Model
{
    protected string $table = 'blog_posts';

    /**
     * Get all published blog posts with pagination
     * Requirements: 8.5 - Published content available to customers
     */
    public function getPublished(int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug,
                       CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM {$this->table} bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                LEFT JOIN users u ON bp.author_id = u.id
                WHERE bp.status = 'published' 
                AND (bp.published_at IS NULL OR bp.published_at <= NOW())
                ORDER BY bp.published_at DESC, bp.created_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get all blog posts for admin (including drafts and scheduled)
     */
    public function getAllForAdmin(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT bp.*, bc.name as category_name,
                       CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM {$this->table} bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                LEFT JOIN users u ON bp.author_id = u.id
                WHERE 1=1";
        
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (bp.title LIKE :search OR bp.content LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category_id'])) {
            $sql .= " AND bp.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND bp.status = :status";
            $params['status'] = $filters['status'];
        }

        // Count total
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Add pagination
        $sql .= " ORDER BY bp.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'posts' => $stmt->fetchAll(),
            'total' => $total,
            'pages' => (int) ceil($total / $limit)
        ];
    }

    /**
     * Get blog post by slug
     */
    public function getBySlug(string $slug): ?array
    {
        $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug,
                       CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM {$this->table} bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                LEFT JOIN users u ON bp.author_id = u.id
                WHERE bp.slug = :slug";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Get published blog post by slug (for public view)
     */
    public function getPublishedBySlug(string $slug): ?array
    {
        $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug,
                       CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM {$this->table} bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                LEFT JOIN users u ON bp.author_id = u.id
                WHERE bp.slug = :slug 
                AND bp.status = 'published'
                AND (bp.published_at IS NULL OR bp.published_at <= NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $result = $stmt->fetch();
        
        return $result ?: null;
    }

    /**
     * Get posts by category
     */
    public function getByCategory(int $categoryId, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT bp.*, bc.name as category_name, bc.slug as category_slug,
                       CONCAT(u.first_name, ' ', u.last_name) as author_name
                FROM {$this->table} bp
                LEFT JOIN blog_categories bc ON bp.category_id = bc.id
                LEFT JOIN users u ON bp.author_id = u.id
                WHERE bp.category_id = :category_id
                AND bp.status = 'published'
                AND (bp.published_at IS NULL OR bp.published_at <= NOW())
                ORDER BY bp.published_at DESC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Create a new blog post
     * Requirements: 8.1 - Create blog post with categories and tags
     */
    public function createPost(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (title, slug, excerpt, content, featured_image, category_id, author_id, 
                 tags, meta_title, meta_description, status, published_at)
                VALUES 
                (:title, :slug, :excerpt, :content, :featured_image, :category_id, :author_id,
                 :tags, :meta_title, :meta_description, :status, :published_at)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'category_id' => $data['category_id'] ?: null,
            'author_id' => $data['author_id'],
            'tags' => $data['tags'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'published_at' => $data['published_at'] ?? null
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update blog post
     */
    public function updatePost(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                title = :title,
                slug = :slug,
                excerpt = :excerpt,
                content = :content,
                featured_image = :featured_image,
                category_id = :category_id,
                tags = :tags,
                meta_title = :meta_title,
                meta_description = :meta_description,
                status = :status,
                published_at = :published_at
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'slug' => $data['slug'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'],
            'featured_image' => $data['featured_image'] ?? null,
            'category_id' => $data['category_id'] ?: null,
            'tags' => $data['tags'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'status' => $data['status'] ?? 'draft',
            'published_at' => $data['published_at'] ?? null
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
     * Generate unique slug from title
     */
    public function generateSlug(string $title, ?int $excludeId = null): string
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
        $originalSlug = $slug;
        $counter = 1;
        
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    /**
     * Count published posts
     */
    public function countPublished(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE status = 'published' 
                AND (published_at IS NULL OR published_at <= NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get recent posts
     */
    public function getRecent(int $limit = 5): array
    {
        return $this->getPublished($limit, 0);
    }

    /**
     * Publish scheduled posts
     * Requirements: 8.6 - Content scheduling
     */
    public function publishScheduledPosts(): int
    {
        $sql = "UPDATE {$this->table} 
                SET status = 'published' 
                WHERE status = 'scheduled' 
                AND published_at <= NOW()";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->rowCount();
    }
}
