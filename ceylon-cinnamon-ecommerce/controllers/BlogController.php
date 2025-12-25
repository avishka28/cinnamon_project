<?php
/**
 * Blog Controller
 * Handles public blog display
 * 
 * Requirements:
 * - 8.5: Content publishing - make content available to customers
 * - 8.6: Content scheduling
 * - 11.1: Include appropriate meta tags and Open Graph data
 * - 11.2: Include JSON-LD structured data
 */

declare(strict_types=1);

class BlogController extends Controller
{
    private BlogPost $blogPostModel;
    private BlogCategory $blogCategoryModel;

    public function __construct()
    {
        parent::__construct();
        $this->blogPostModel = new BlogPost();
        $this->blogCategoryModel = new BlogCategory();
    }

    /**
     * Display blog listing page
     * Requirements: 8.5 - Published content available to customers, 11.1 - SEO meta tags
     */
    public function index(): void
    {
        // Publish any scheduled posts that are due
        $this->blogPostModel->publishScheduledPosts();
        
        $page = max(1, (int) $this->input('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $posts = $this->blogPostModel->getPublished($limit, $offset);
        $totalPosts = $this->blogPostModel->countPublished();
        $totalPages = (int) ceil($totalPosts / $limit);
        
        $categories = $this->blogCategoryModel->getActive();
        $recentPosts = $this->blogPostModel->getRecent(5);

        // Configure SEO (Requirement 11.1)
        $seo = new SeoHelper();
        $seo->setTitle('Blog - ' . APP_NAME)
            ->setDescription('Read our latest articles about Ceylon cinnamon, health benefits, recipes, and more.')
            ->setCanonicalUrl(APP_URL . '/blog')
            ->setOgType('website');

        $this->view('pages/blog/index', [
            'title' => 'Blog - Ceylon Cinnamon',
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'seo' => $seo
        ]);
    }

    /**
     * Display single blog post
     * Requirements: 8.5 - Published content available to customers, 11.1, 11.2 - SEO
     */
    public function show(string $slug): void
    {
        // Publish any scheduled posts that are due
        $this->blogPostModel->publishScheduledPosts();
        
        $post = $this->blogPostModel->getPublishedBySlug($slug);
        
        if (!$post) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Post Not Found']);
            return;
        }

        $categories = $this->blogCategoryModel->getActive();
        $recentPosts = $this->blogPostModel->getRecent(5);
        
        // Get related posts from same category
        $relatedPosts = [];
        if ($post['category_id']) {
            $relatedPosts = $this->blogPostModel->getByCategory($post['category_id'], 3, 0);
            // Remove current post from related
            $relatedPosts = array_filter($relatedPosts, fn($p) => $p['id'] != $post['id']);
            $relatedPosts = array_slice($relatedPosts, 0, 3);
        }

        // Configure SEO with article structured data (Requirements 11.1, 11.2)
        $seo = new SeoHelper();
        $seo->configureForBlogPost($post);

        $this->view('pages/blog/show', [
            'title' => ($post['meta_title'] ?: $post['title']) . ' - Ceylon Cinnamon Blog',
            'post' => $post,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'relatedPosts' => $relatedPosts,
            'metaDescription' => $post['meta_description'] ?: $post['excerpt'],
            'seo' => $seo
        ]);
    }

    /**
     * Display posts by category
     * Requirement 11.1: SEO meta tags
     */
    public function category(string $slug): void
    {
        $category = $this->blogCategoryModel->getBySlug($slug);
        
        if (!$category || !$category['is_active']) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Category Not Found']);
            return;
        }

        $page = max(1, (int) $this->input('page', 1));
        $limit = 10;
        $offset = ($page - 1) * $limit;

        $posts = $this->blogPostModel->getByCategory($category['id'], $limit, $offset);
        $categories = $this->blogCategoryModel->getActive();
        $recentPosts = $this->blogPostModel->getRecent(5);

        // Configure SEO (Requirement 11.1)
        $seo = new SeoHelper();
        $seo->setTitle($category['name'] . ' - ' . APP_NAME . ' Blog')
            ->setDescription($category['description'] ?? 'Browse articles in ' . $category['name'])
            ->setCanonicalUrl(APP_URL . '/blog/category/' . $slug)
            ->setOgType('website');

        $this->view('pages/blog/category', [
            'title' => $category['name'] . ' - Ceylon Cinnamon Blog',
            'category' => $category,
            'posts' => $posts,
            'categories' => $categories,
            'recentPosts' => $recentPosts,
            'currentPage' => $page,
            'seo' => $seo
        ]);
    }
}
