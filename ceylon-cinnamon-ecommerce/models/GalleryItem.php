<?php
/**
 * GalleryItem Model
 * Handles gallery item CRUD operations
 * 
 * Requirements:
 * - 8.3: Gallery management for images and videos
 */

declare(strict_types=1);

class GalleryItem extends Model
{
    protected string $table = 'gallery_items';

    /**
     * Get all active gallery items for public display
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY sort_order ASC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all gallery items for admin
     */
    public function getAllForAdmin(): array
    {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY sort_order ASC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get gallery items by type
     * Requirements: 8.3 - Support images and videos
     */
    public function getByType(string $type): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE file_type = :type AND is_active = 1 
                ORDER BY sort_order ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['type' => $type]);
        return $stmt->fetchAll();
    }

    /**
     * Get images only
     */
    public function getImages(): array
    {
        return $this->getByType('image');
    }

    /**
     * Get videos only
     */
    public function getVideos(): array
    {
        return $this->getByType('video');
    }

    /**
     * Create a new gallery item
     * Requirements: 8.3 - Gallery management
     */
    public function createItem(array $data): int
    {
        $sql = "INSERT INTO {$this->table} 
                (title, description, file_url, file_type, thumbnail_url, is_active, sort_order)
                VALUES 
                (:title, :description, :file_url, :file_type, :thumbnail_url, :is_active, :sort_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_url' => $data['file_url'],
            'file_type' => $data['file_type'] ?? 'image',
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update gallery item
     */
    public function updateItem(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} SET
                title = :title,
                description = :description,
                file_url = :file_url,
                file_type = :file_type,
                thumbnail_url = :thumbnail_url,
                is_active = :is_active,
                sort_order = :sort_order
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'file_url' => $data['file_url'],
            'file_type' => $data['file_type'] ?? 'image',
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    /**
     * Count items by type
     */
    public function countByType(string $type): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE file_type = :type AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['type' => $type]);
        return (int) $stmt->fetchColumn();
    }
}
