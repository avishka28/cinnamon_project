<?php
/**
 * Product Controller
 * Handles public product catalog display
 * 
 * Requirements:
 * - 1.1: Display all available products with pagination
 * - 1.2: Category filtering
 * - 1.3: Price range filtering
 * - 1.4: Origin filtering
 * - 1.5: Organic filtering
 * - 1.6: Product detail page with all information
 * - 1.7: Customer reviews and ratings
 * - 11.1: Include appropriate meta tags and Open Graph data
 * - 11.2: Include JSON-LD structured data for products
 * - 13.4: Show wholesale pricing for wholesale customers
 */

declare(strict_types=1);

class ProductController extends Controller
{
    private Product $productModel;
    private Category $categoryModel;
    private User $userModel;
    private WholesalePriceTier $priceTierModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
        $this->categoryModel = new Category();
        $this->userModel = new User();
        $this->priceTierModel = new WholesalePriceTier();
    }

    /**
     * Check if current user is a wholesale customer
     * Requirement 13.4: Wholesale customer identification
     * 
     * @return bool True if user is wholesale customer
     */
    private function isWholesaleCustomer(): bool
    {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        return $this->userModel->isWholesale((int) $_SESSION['user_id']);
    }

    /**
     * Display product listing with filters and pagination
     * Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 11.1, 13.4
     */
    public function index(): void
    {
        // Get filter parameters
        $filters = $this->getFilters();
        
        // Get pagination parameters
        $page = max(1, (int) $this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Check if user is wholesale customer (Requirement 13.4)
        $isWholesale = $this->isWholesaleCustomer();

        // Get filtered products with wholesale pricing if applicable
        if ($isWholesale) {
            $result = $this->productModel->getProductsWithPricing(true, $filters, $limit, $offset);
        } else {
            $result = $this->productModel->getFiltered($filters, $limit, $offset);
        }

        // Get filter options for sidebar
        $categories = $this->categoryModel->getTree();
        $origins = $this->productModel->getOrigins();
        $priceRange = $this->productModel->getPriceRange();

        // Configure SEO (Requirement 11.1)
        $seo = new SeoHelper();
        $seo->setTitle('Our Products - ' . APP_NAME)
            ->setDescription('Browse our collection of premium Ceylon cinnamon products including cinnamon sticks, powder, oil, and more.')
            ->setCanonicalUrl(APP_URL . '/products')
            ->setKeywords('Ceylon cinnamon products, cinnamon sticks, cinnamon powder, cinnamon oil, organic cinnamon');

        $this->view('pages/products', [
            'title' => 'Our Products - Ceylon Cinnamon',
            'products' => $result['products'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'currentPage' => $page,
            'filters' => $filters,
            'categories' => $categories,
            'origins' => $origins,
            'priceRange' => $priceRange,
            'seo' => $seo,
            'isWholesale' => $isWholesale
        ]);
    }


    /**
     * Display products by category
     * Requirement 1.2: Category filtering
     * Requirement 11.1: SEO meta tags
     * Requirement 13.4: Wholesale pricing
     * 
     * @param string $slug Category slug
     */
    public function category(string $slug): void
    {
        $category = $this->categoryModel->findBySlug($slug);
        
        if (!$category) {
            $this->view('errors/404', [
                'title' => 'Category Not Found'
            ]);
            return;
        }

        // Get all descendant category IDs for hierarchical filtering
        $categoryIds = $this->categoryModel->getDescendantIds((int) $category['id']);

        // Get filter parameters
        $filters = $this->getFilters();
        $filters['category_ids'] = $categoryIds;

        // Get pagination parameters
        $page = max(1, (int) $this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        // Check if user is wholesale customer (Requirement 13.4)
        $isWholesale = $this->isWholesaleCustomer();

        // Get filtered products with wholesale pricing if applicable
        if ($isWholesale) {
            $result = $this->productModel->getProductsWithPricing(true, $filters, $limit, $offset);
        } else {
            $result = $this->productModel->getFiltered($filters, $limit, $offset);
        }

        // Get filter options
        $categories = $this->categoryModel->getTree();
        $origins = $this->productModel->getOrigins();
        $priceRange = $this->productModel->getPriceRange();
        $breadcrumb = $this->categoryModel->getBreadcrumb((int) $category['id']);

        // Configure SEO (Requirement 11.1)
        $seo = new SeoHelper();
        $seo->configureForCategory($category);
        
        // Add breadcrumb structured data
        if (!empty($breadcrumb)) {
            $seo->addStructuredData($seo->buildBreadcrumbStructuredData($breadcrumb));
        }

        $this->view('pages/products', [
            'title' => $category['name'] . ' - Ceylon Cinnamon',
            'category' => $category,
            'breadcrumb' => $breadcrumb,
            'products' => $result['products'],
            'total' => $result['total'],
            'pages' => $result['pages'],
            'currentPage' => $page,
            'filters' => $filters,
            'categories' => $categories,
            'origins' => $origins,
            'priceRange' => $priceRange,
            'seo' => $seo,
            'isWholesale' => $isWholesale
        ]);
    }

    /**
     * Display single product detail page
     * Requirements: 1.6, 1.7, 11.1, 11.2, 13.4
     * 
     * @param string $slug Product slug
     */
    public function show(string $slug): void
    {
        $product = $this->productModel->findBySlug($slug);
        
        if (!$product) {
            $this->view('errors/404', [
                'title' => 'Product Not Found'
            ]);
            return;
        }

        // Get full product details including images and reviews
        $product = $this->productModel->getFullDetails((int) $product['id']);

        // Check if user is wholesale customer (Requirement 13.4)
        $isWholesale = $this->isWholesaleCustomer();

        // Add wholesale pricing information if applicable
        if ($isWholesale) {
            $priceInfo = $this->productModel->getEffectivePrice((int) $product['id'], true, 1);
            $product['wholesale_price'] = $priceInfo['effective_price'];
            $product['is_wholesale_price'] = $priceInfo['is_wholesale_price'];
            $product['savings'] = $priceInfo['savings'];
            $product['savings_percentage'] = $priceInfo['savings_percentage'];
            
            // Get all price tiers for display
            $product['price_tiers'] = $this->priceTierModel->getTierSummary((int) $product['id']);
            $product['min_wholesale_qty'] = $this->priceTierModel->getMinimumQuantity((int) $product['id']);
        }

        // Get related products
        $relatedProducts = $this->productModel->getRelated((int) $product['id']);

        // Get category breadcrumb
        $breadcrumb = $this->categoryModel->getBreadcrumb((int) $product['category_id']);

        // Configure SEO with meta tags and structured data (Requirements 11.1, 11.2)
        $seo = new SeoHelper();
        $seo->configureForProduct($product);
        
        // Add breadcrumb structured data
        if (!empty($breadcrumb)) {
            $seo->addStructuredData($seo->buildBreadcrumbStructuredData($breadcrumb));
        }

        $this->view('pages/product_detail', [
            'title' => $product['name'] . ' - Ceylon Cinnamon',
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'breadcrumb' => $breadcrumb,
            'metaTitle' => $product['meta_title'] ?? $product['name'],
            'metaDescription' => $product['meta_description'] ?? $product['short_description'],
            'seo' => $seo,
            'isWholesale' => $isWholesale
        ]);
    }

    /**
     * Get filter parameters from request
     * 
     * @return array Filter parameters
     */
    private function getFilters(): array
    {
        $filters = [];

        // Category filter (Requirement 1.2)
        $categoryId = $this->input('category');
        if ($categoryId !== null && is_numeric($categoryId)) {
            $filters['category_id'] = (int) $categoryId;
        }

        // Price range filter (Requirement 1.3)
        $priceMin = $this->input('price_min');
        if ($priceMin !== null && is_numeric($priceMin)) {
            $filters['price_min'] = (float) $priceMin;
        }

        $priceMax = $this->input('price_max');
        if ($priceMax !== null && is_numeric($priceMax)) {
            $filters['price_max'] = (float) $priceMax;
        }

        // Origin filter (Requirement 1.4)
        $origin = $this->input('origin');
        if ($origin !== null && $origin !== '') {
            $filters['origin'] = $this->sanitize($origin);
        }

        // Organic filter (Requirement 1.5)
        $isOrganic = $this->input('is_organic');
        if ($isOrganic !== null) {
            $filters['is_organic'] = (int) $isOrganic;
        }

        // Search filter
        $search = $this->input('search');
        if ($search !== null && $search !== '') {
            $filters['search'] = $this->sanitize($search);
        }

        // In stock filter
        $inStock = $this->input('in_stock');
        if ($inStock !== null) {
            $filters['in_stock'] = (bool) $inStock;
        }

        // On sale filter
        $onSale = $this->input('on_sale');
        if ($onSale !== null) {
            $filters['on_sale'] = (bool) $onSale;
        }

        // Sort option
        $sort = $this->input('sort');
        if ($sort !== null && in_array($sort, ['newest', 'oldest', 'price_low', 'price_high', 'name_asc', 'name_desc'])) {
            $filters['sort'] = $sort;
        }

        return $filters;
    }

    /**
     * API endpoint for product search (AJAX)
     */
    public function search(): void
    {
        $keyword = $this->sanitize($this->input('q', ''));
        $limit = min(20, max(1, (int) $this->input('limit', 10)));

        if (strlen($keyword) < 2) {
            $this->json(['products' => []]);
            return;
        }

        $products = $this->productModel->search($keyword, $limit);

        $this->json([
            'products' => array_map(function ($product) {
                return [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'price' => $product['price'],
                    'sale_price' => $product['sale_price'],
                    'category_name' => $product['category_name'] ?? null
                ];
            }, $products)
        ]);
    }
}
