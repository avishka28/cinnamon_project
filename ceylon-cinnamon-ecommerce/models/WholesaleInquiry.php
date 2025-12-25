<?php
/**
 * Wholesale Inquiry Model
 * Handles wholesale inquiry data operations
 * 
 * Requirements:
 * - 13.1: Display wholesale inquiry form
 * - 13.2: Send notification to admin on inquiry submission
 */

declare(strict_types=1);

class WholesaleInquiry extends Model
{
    protected string $table = 'wholesale_inquiries';

    /**
     * Inquiry status constants
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    /**
     * Create a new wholesale inquiry
     * Requirement 13.1: Store wholesale inquiry data
     * 
     * @param array $data Inquiry data
     * @return int New inquiry ID
     * @throws InvalidArgumentException If required fields are missing
     */
    public function createInquiry(array $data): int
    {
        $this->validateInquiryData($data);

        $inquiryData = [
            'company_name' => $data['company_name'],
            'contact_name' => $data['contact_name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'country' => $data['country'] ?? null,
            'business_type' => $data['business_type'] ?? null,
            'estimated_quantity' => $data['estimated_quantity'] ?? null,
            'products_interested' => $data['products_interested'] ?? null,
            'message' => $data['message'] ?? null,
            'status' => self::STATUS_PENDING
        ];

        return $this->create($inquiryData);
    }

    /**
     * Validate inquiry data
     * 
     * @param array $data Inquiry data to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateInquiryData(array $data): void
    {
        $required = ['company_name', 'contact_name', 'email'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email format");
        }
    }

    /**
     * Get all inquiries with optional filtering
     * 
     * @param array $filters Filter options
     * @param int $limit Number of results
     * @param int $offset Pagination offset
     * @return array Inquiries with pagination info
     */
    public function getFiltered(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (company_name LIKE :search OR contact_name LIKE :search OR email LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // Get total count
        $countSql = str_replace('SELECT *', 'SELECT COUNT(*)', $sql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Add ordering and pagination
        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value, PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'inquiries' => $stmt->fetchAll(),
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => (int) ceil($total / $limit)
        ];
    }

    /**
     * Update inquiry status
     * 
     * @param int $id Inquiry ID
     * @param string $status New status
     * @return bool Success
     */
    public function updateStatus(int $id, string $status): bool
    {
        $validStatuses = [self::STATUS_PENDING, self::STATUS_CONTACTED, self::STATUS_APPROVED, self::STATUS_REJECTED];
        
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Invalid status: {$status}");
        }

        return $this->update($id, ['status' => $status]);
    }

    /**
     * Get pending inquiries count
     * 
     * @return int Number of pending inquiries
     */
    public function getPendingCount(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE status = :status";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => self::STATUS_PENDING]);
        return (int) $stmt->fetchColumn();
    }
}
