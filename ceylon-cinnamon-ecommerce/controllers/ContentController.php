<?php
/**
 * Content Controller
 * Handles public content display (certificates, gallery)
 * 
 * Requirements:
 * - 8.2: Certificate display
 * - 8.3: Gallery display
 * - 8.5: Published content available to customers
 */

declare(strict_types=1);

class ContentController extends Controller
{
    private Certificate $certificateModel;
    private GalleryItem $galleryItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->certificateModel = new Certificate();
        $this->galleryItemModel = new GalleryItem();
    }

    /**
     * Display certificates page
     * Requirements: 8.2 - Certificate display
     */
    public function certificates(): void
    {
        $certificates = $this->certificateModel->getActive();

        $this->view('pages/certificates', [
            'title' => 'Our Certificates - Ceylon Cinnamon',
            'certificates' => $certificates
        ]);
    }

    /**
     * Display gallery page
     * Requirements: 8.3 - Gallery display for images and videos
     */
    public function gallery(): void
    {
        $items = $this->galleryItemModel->getActive();
        $images = array_filter($items, fn($item) => $item['file_type'] === 'image');
        $videos = array_filter($items, fn($item) => $item['file_type'] === 'video');

        $this->view('pages/gallery', [
            'title' => 'Gallery - Ceylon Cinnamon',
            'items' => $items,
            'images' => array_values($images),
            'videos' => array_values($videos)
        ]);
    }
}
