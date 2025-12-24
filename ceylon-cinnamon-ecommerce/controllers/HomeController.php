<?php
/**
 * Home Controller
 * Handles the main landing page
 */

declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('pages/home', [
            'title' => 'Welcome to Ceylon Cinnamon'
        ]);
    }
}
