<?php
/**
 * Product Model
 * Handles product data operations with filtering and search
 * 
 * Requirements:
 * - 1.1: Display all available products with pagination
 * - 1.2: Category filtering
 * - 1.3: Price range filtering
 * - 1.4: Origin filtering
 * - 1.5: Organic filtering
 * - 1.6: Product detail with images, videos, descriptions, specifications
 * - 1.7: Customer reviews and ratings
 * - 6.1: Product details (SKU, stock, price, weight, dimensions)
 */

declare(strict_types=1);

class Product extends Model
{
    protected string $table = 'products';

    /**
     * Get products with filtering and pagination
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5
     * 
     * @param array $filters Filter options
     * @param int $limit Number of products per page
     * @param int $offset Pagination offset
     * @return array Products with pagination info
     */
    public function getFiltered(array $filters = [], int $limit = ITEMS_PER_PAGE, int $offset = 0): array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1";
        
        $params = [];
        $conditions = [];

        // Category filter (Requirement 1.2)
        if (!empty($filters['category_id'])) {
            $conditions[] = "p.category_id = :category_id";
            $params['category_id'] = $filters['category_id'];
        }

        // Category IDs filter (for hierarchical categories)
        if (!empty($filters['category_ids']) && is_array($filters['category_ids'])) {
            $placeholders = [];
            foreach ($filters['category_ids'] as $i => $id) {
                $key = "cat_id_{$i}";
                $placeholders[] = ":{$key}";
                $params[$key] = $id;
            }
            $conditions[] = "p.category_id IN (" . implode(',', $placeholders) . ")";
        }

        // Price range filter (Requirement 1.3)
        if (isset($filters['price_min']) && is_numeric($filters['price_min'])) {
            $conditions[] = "p.price >= :price_min";
            $params['price_min'] = (float) $filters['price_min'];
        }

        if (isset($filters['price_max']) && is_numeric($filters['price_max'])) {
            $conditions[] = "p.price <= :price_max";
            $params['price_max'] = (float) $filters['price_max'];
        }

        // Origin filter (Requirement 1.4)
        if (!empty($filters['origin'])) {
            $conditions[] = "p.origin = :origin";
            $params['origin'] = $filters['origin'];
        }

        // Organic filter (Requirement 1.5)
        if (isset($filters['is_organic'])) {
            $conditions[] = "p.is_organic = :is_organic";
            $params['is_organic'] = (int) $filters['is_organic'];
        }

        // Search filter
        if (!empty($filters['search'])) {
            $conditions[] = "(p.name LIKE :search OR p.description LIKE :search OR p.tags LIKE :search)";
            $params['search'] = '%' . $filters['search'] . '%';
        }

        // In stock filter
        if (!empty($filters['in_stock'])) {
            $conditions[] = "p.stock_quantity > 0";
        }

        // On sale filter
        if (!empty($filters['on_sale'])) {
            $conditions[] = "p.sale_price IS NOT NULL AND p.sale_price < p.price";
        }

        if (!empty($conditions)) {
            $sql .= " AND " . implode(' AND ', $conditions);
        }

        // Sorting
        $sortOptions = [
            'newest' => 'p.created_at DESC',
            'oldest' => 'p.created_at ASC',
            'price_low' => 'COALESCE(p.sale_price, p.price) ASC',
            'price_high' => 'COALESCE(p.sale_price, p.price) DESC',
            'name_asc' => 'p.name ASC',
            'name_desc' => 'p.name DESC'
        ];
        $sort = $sortOptions[$filters['sort'] ?? 'newest'] ?? $sortOptions['newest'];
        $sql .= " ORDER BY {$sort}";

        // Get total count for pagination
        $countSql = preg_replace('/SELECT .* FROM/', 'SELECT COUNT(*) FROM', $sql);
        $countSql = preg_replace('/ORDER BY.*$/', '', $countSql);
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        // Add pagination
        $sql .= " LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if (is_int($value)) {
                $stmt->bindValue(":{$key}", $value, PDO::PARAM_INT);
            } elseif (is_float($value)) {
                $stmt->bindValue(":{$key}", $value);
            } else {
                $stmt->bindValue(":{$key}", $value, PDO::PARAM_STR);
            }
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $products = $stmt->fetchAll();

        return [
            'products' => $products,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => (int) ceil($total / $limit)
        ];
    }


    /**
     * Get product by slug with full details
     * Requirement 1.6: Product detail with all information
     * 
     * @param string $slug Product slug
     * @return array|null Product data or null
     */
    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT p.*, c.name as category_name, c.slug as category_slug 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.slug = :slug AND p.is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        
        return $product ?: null;
    }

    /**
     * Get product with all details including images and reviews
     * Requirement 1.6, 1.7
     * 
     * @param int $id Product ID
     * @return array|null Product with full details
     */
    public function getFullDetails(int $id): ?array
    {
        $product = $this->find($id);
        if (!$product) {
            return null;
        }

        // Get category info
        $sql = "SELECT name, slug FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $product['category_id']]);
        $category = $stmt->fetch();
        if ($category) {
            $product['category_name'] = $category['name'];
            $product['category_slug'] = $category['slug'];
        }

        // Get images
        $product['images'] = $this->getImages($id);

        // Get reviews (Requirement 1.7)
        $product['reviews'] = $this->getReviews($id);

        // Calculate average rating
        $product['average_rating'] = $this->getAverageRating($id);
        $product['review_count'] = count($product['reviews']);

        return $product;
    }

    /**
     * Get product images
     * 
     * @param int $productId Product ID
     * @return array List of images
     */
    public function getImages(int $productId): array
    {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY is_primary DESC, sort_order ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get primary image for a product
     * 
     * @param int $productId Product ID
     * @return array|null Primary image or null
     */
    public function getPrimaryImage(int $productId): ?array
    {
        $sql = "SELECT * FROM product_images 
                WHERE product_id = :product_id 
                ORDER BY is_primary DESC, sort_order ASC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        $image = $stmt->fetch();
        return $image ?: null;
    }

    /**
     * Get product reviews
     * Requirement 1.7: Customer reviews and ratings
     * 
     * @param int $productId Product ID
     * @param bool $approvedOnly Only return approved reviews
     * @return array List of reviews
     */
    public function getReviews(int $productId, bool $approvedOnly = true): array
    {
        $sql = "SELECT r.*, u.first_name, u.last_name 
                FROM product_reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = :product_id";
        
        if ($approvedOnly) {
            $sql .= " AND r.is_approved = 1";
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        return $stmt->fetchAll();
    }

    /**
     * Get average rating for a product
     * 
     * @param int $productId Product ID
     * @return float Average rating (0 if no reviews)
     */
    public function getAverageRating(int $productId): float
    {
        $sql = "SELECT AVG(rating) FROM product_reviews 
                WHERE product_id = :product_id AND is_approved = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['product_id' => $productId]);
        $avg = $stmt->fetchColumn();
        
        return $avg ? round((float) $avg, 1) : 0.0;
    }


    /**
     * Get related products
     * 
     * @param int $productId Current product ID
     * @param int $limit Number of related products
     * @return array Related products
     */
    public function getRelated(int $productId, int $limit = 4): array
    {
        $product = $this->find($productId);
        if (!$product) {
            return [];
        }

        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id != :product_id 
                AND p.is_active = 1 
                AND p.category_id = :category_id 
                ORDER BY RAND() 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':product_id', $productId, PDO::PARAM_INT);
        $stmt->bindValue(':category_id', $product['category_id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get featured products
     * 
     * @param int $limit Number of products
     * @return array Featured products
     */
    public function getFeatured(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 AND p.stock_quantity > 0 
                ORDER BY p.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get products on sale
     * 
     * @param int $limit Number of products
     * @return array Products on sale
     */
    public function getOnSale(int $limit = 8): array
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_active = 1 
                AND p.sale_price IS NOT NULL 
                AND p.sale_price < p.price 
                ORDER BY (p.price - p.sale_price) / p.price DESC 
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    /**
     * Get distinct origins for filtering
     * 
     * @return array List of origins
     */
    public function getOrigins(): array
    {
        $sql = "SELECT DISTINCT origin FROM {$this->table} 
                WHERE origin IS NOT NULL AND origin != '' AND is_active = 1 
                ORDER BY origin ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get price range for filtering
     * 
     * @return array Min and max prices
     */
    public function getPriceRange(): array
    {
        $sql = "SELECT MIN(COALESCE(sale_price, price)) as min_price, 
                       MAX(price) as max_price 
                FROM {$this->table} 
                WHERE is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch();
    }

    /**
     * Create a new product
     * Requirement 6.1: Store product details
     * 
     * @param array $data Product data
     * @return int New product ID
     */
    public function createProduct(array $data): int
    {
        $this->validateProductData($data);
        
        $productData = [
            'sku' => $data['sku'],
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $this->generateSlug($data['name']),
            'description' => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'sale_price' => $data['sale_price'] ?? null,
            'weight' => $data['weight'] ?? null,
            'dimensions' => $data['dimensions'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'category_id' => $data['category_id'],
            'is_organic' => $data['is_organic'] ?? 0,
            'origin' => $data['origin'] ?? null,
            'tags' => $data['tags'] ?? null,
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'is_active' => $data['is_active'] ?? 1
        ];
        
        return $this->create($productData);
    }

    /**
     * Generate URL-friendly slug from name
     * 
     * @param string $name Product name
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
     * Validate product data
     * 
     * @param array $data Product data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateProductData(array $data): void
    {
        $required = ['sku', 'name', 'price', 'category_id'];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || (empty($data[$field]) && $data[$field] !== 0)) {
                throw new InvalidArgumentException("Field '{$field}' is required");
            }
        }
        
        if (!is_numeric($data['price']) || $data['price'] < 0) {
            throw new InvalidArgumentException("Price must be a positive number");
        }
        
        if (isset($data['sale_price']) && $data['sale_price'] !== null) {
            if (!is_numeric($data['sale_price']) || $data['sale_price'] < 0) {
                throw new InvalidArgumentException("Sale price must be a positive number");
            }
        }
    }

    /**
     * Update product stock
     * 
     * @param int $productId Product ID
     * @param int $quantity Quantity to reduce (positive) or add (negative)
     * @return bool Success
     */
    public function updateStock(int $productId, int $quantity): bool
    {
        $sql = "UPDATE {$this->table} 
                SET stock_quantity = stock_quantity - :quantity 
                WHERE id = :id AND stock_quantity >= :quantity";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $productId,
            'quantity' => $quantity
        ]);
    }

    /**
     * Add product image
     * 
     * @param int $productId Product ID
     * @param array $imageData Image data
     * @return int New image ID
     */
    public function addImage(int $productId, array $imageData): int
    {
        $sql = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary, sort_order) 
                VALUES (:product_id, :image_url, :alt_text, :is_primary, :sort_order)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'product_id' => $productId,
            'image_url' => $imageData['image_url'],
            'alt_text' => $imageData['alt_text'] ?? null,
            'is_primary' => $imageData['is_primary'] ?? 0,
            'sort_order' => $imageData['sort_order'] ?? 0
        ]);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Search products by keyword
     * 
     * @param string $keyword Search keyword
     * @param int $limit Number of results
     * @return array Matching products
     */
    public function search(string $keyword, int $limit = 20): array
    {
        return $this->getFiltered(['search' => $keyword], $limit)['products'];
    }

    /**
     * Get effective price for a product based on user type and quantity
     * Requirement 13.4: Show wholesale pricing for wholesale customers
     * 
     * @param int $productId Product ID
     * @param bool $isWholesale Whether user is a wholesale customer
     * @param int $quantity Order quantity (for wholesale tier calculation)
     * @return array Price information with retail_price, effective_price, and is_wholesale_price flag
     */
    public function getEffectivePrice(int $productId, bool $isWholesale = false, int $quantity = 1): array
    {
        $product = $this->find($productId);
        if (!$product) {
            return [
                'retail_price' => 0,
                'effective_price' => 0,
                'is_wholesale_price' => false,
                'wholesale_tier' => null
            ];
        }

        $retailPrice = (float) ($product['sale_price'] ?? $product['price']);
        $effectivePrice = $retailPrice;
        $isWholesalePrice = false;
        $wholesaleTier = null;

        if ($isWholesale) {
            $priceTierModel = new WholesalePriceTier();
            $wholesalePrice = $priceTierModel->getWholesalePrice($productId, $quantity);
            
            if ($wholesalePrice !== null && $wholesalePrice < $retailPrice) {
                $effectivePrice = $wholesalePrice;
                $isWholesalePrice = true;
                
                // Get the tier info
                $tiers = $priceTierModel->getProductTiers($productId);
                foreach ($tiers as $tier) {
                    if ($tier['min_quantity'] <= $quantity && 
                        ($tier['max_quantity'] === null || $tier['max_quantity'] >= $quantity)) {
                        $wholesaleTier = $tier;
                        break;
                    }
                }
            }
        }

        return [
            'retail_price' => $retailPrice,
            'effective_price' => $effectivePrice,
            'is_wholesale_price' => $isWholesalePrice,
            'wholesale_tier' => $wholesaleTier,
            'savings' => $retailPrice - $effectivePrice,
            'savings_percentage' => $retailPrice > 0 ? round((($retailPrice - $effectivePrice) / $retailPrice) * 100, 1) : 0
        ];
    }

    /**
     * Get products with wholesale pricing information
     * Requirement 13.5: Wholesale-specific product catalogs
     * 
     * @param bool $isWholesale Whether user is a wholesale customer
     * @param array $filters Filter options
     * @param int $limit Number of products per page
     * @param int $offset Pagination offset
     * @return array Products with pricing info
     */
    public function getProductsWithPricing(bool $isWholesale, array $filters = [], int $limit = ITEMS_PER_PAGE, int $offset = 0): array
    {
        $result = $this->getFiltered($filters, $limit, $offset);
        
        if ($isWholesale) {
            $priceTierModel = new WholesalePriceTier();
            
            foreach ($result['products'] as &$product) {
                $priceInfo = $this->getEffectivePrice((int) $product['id'], true, 1);
                $product['wholesale_price'] = $priceInfo['effective_price'];
                $product['is_wholesale_price'] = $priceInfo['is_wholesale_price'];
                $product['savings'] = $priceInfo['savings'];
                $product['savings_percentage'] = $priceInfo['savings_percentage'];
                
                // Get minimum wholesale quantity
                $minQty = $priceTierModel->getMinimumQuantity((int) $product['id']);
                $product['min_wholesale_qty'] = $minQty;
                
                // Get all price tiers for display
                $product['price_tiers'] = $priceTierModel->getTierSummary((int) $product['id']);
            }
        }
        
        return $result;
    }
}
