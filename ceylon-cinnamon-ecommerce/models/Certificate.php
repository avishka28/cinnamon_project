<?php
/**
 * Certificate Model
 * Handles certificate CRUD operations
 * 
 * Requirements:
 * - 8.2: Certificate file upload and display
 */

declare(strict_types=1);

class Certificate extends Model
{
    protected string $table = 'certificates';

    /**
     * Get all active certificates for public display
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
     * Get all certificates for admin
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
     * Create a new certificate
     * Requirements: 8.2 - Certificate file upload
     */
    public function createCertificate(array $data): int
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
     * Update certificate
     */
    public function updateCertificate(int $id, array $data): bool
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
     * Get certificates by type
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
}
