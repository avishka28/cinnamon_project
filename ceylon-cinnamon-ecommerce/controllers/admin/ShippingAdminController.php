<?php
/**
 * Shipping Admin Controller
 * Handles shipping zone and method management for admin
 * 
 * Requirements:
 * - 14.1: Display shipping rates by country and weight brackets
 * - 14.5: Admin shipping rule management
 */

declare(strict_types=1);

class ShippingAdminController extends Controller
{
    private SessionManager $sessionManager;
    private ShippingZone $zoneModel;
    private ShippingMethod $methodModel;
    private ShippingCalculator $calculator;

    public function __construct()
    {
        parent::__construct();
        $this->sessionManager = new SessionManager();
        $this->zoneModel = new ShippingZone();
        $this->methodModel = new ShippingMethod();
        $this->calculator = new ShippingCalculator();
    }

    /**
     * Display shipping zones list
     * Requirement 14.5: Admin shipping rule management
     */
    public function index(): void
    {
        $this->sessionManager->start();
        
        $zones = $this->calculator->getAllZonesWithMethods();
        $countries = ShippingZone::getSupportedCountries();

        $this->adminView('shipping/index', [
            'title' => 'Shipping Management - Admin',
            'zones' => $zones,
            'countries' => $countries,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Show create zone form
     */
    public function createZone(): void
    {
        $this->sessionManager->start();
        
        $countries = ShippingZone::getSupportedCountries();

        $this->adminView('shipping/create_zone', [
            'title' => 'Add Shipping Zone - Admin',
            'countries' => $countries,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new shipping zone
     */
    public function storeZone(): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/shipping/zones/create');
            return;
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/shipping/zones/create');
            return;
        }

        $data = [
            'name' => $this->sanitize($this->input('name', '')),
            'countries' => $this->input('countries', []),
            'is_active' => $this->input('is_active', '1') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        try {
            $this->zoneModel->createZone($data);
            $this->sessionManager->flash('success', 'Shipping zone created successfully.');
            $this->redirect('/admin/shipping');
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), '/admin/shipping/zones/create');
        }
    }

    /**
     * Show edit zone form
     */
    public function editZone($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();
        
        $zone = $this->zoneModel->find($id);
        
        if (!$zone) {
            $this->sessionManager->flash('error', 'Shipping zone not found.');
            $this->redirect('/admin/shipping');
            return;
        }

        $zone['countries_array'] = $this->zoneModel->parseCountries($zone['countries']);
        $countries = ShippingZone::getSupportedCountries();

        $this->adminView('shipping/edit_zone', [
            'title' => 'Edit Shipping Zone - Admin',
            'zone' => $zone,
            'countries' => $countries,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update shipping zone
     */
    public function updateZone($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/shipping/zones/' . $id . '/edit');
            return;
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/shipping/zones/' . $id . '/edit');
            return;
        }

        $data = [
            'name' => $this->sanitize($this->input('name', '')),
            'countries' => $this->input('countries', []),
            'is_active' => $this->input('is_active', '1') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0)
        ];

        try {
            $this->zoneModel->updateZone($id, $data);
            $this->sessionManager->flash('success', 'Shipping zone updated successfully.');
            $this->redirect('/admin/shipping');
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), '/admin/shipping/zones/' . $id . '/edit');
        }
    }

    /**
     * Delete shipping zone
     */
    public function destroyZone($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->json(['success' => false, 'error' => 'Invalid security token'], 400);
            return;
        }

        try {
            $this->zoneModel->delete($id);
            
            if ($this->isAjax()) {
                $this->json(['success' => true]);
            } else {
                $this->sessionManager->flash('success', 'Shipping zone deleted successfully.');
                $this->redirect('/admin/shipping');
            }
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 400);
            } else {
                $this->handleError($e->getMessage(), '/admin/shipping');
            }
        }
    }

    /**
     * Show create method form
     */
    public function createMethod($zoneId): void
    {
        $zoneId = (int) $zoneId;
        $this->sessionManager->start();
        
        $zone = $this->zoneModel->find($zoneId);
        
        if (!$zone) {
            $this->sessionManager->flash('error', 'Shipping zone not found.');
            $this->redirect('/admin/shipping');
            return;
        }

        $this->adminView('shipping/create_method', [
            'title' => 'Add Shipping Method - Admin',
            'zone' => $zone,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Store new shipping method
     */
    public function storeMethod($zoneId): void
    {
        $zoneId = (int) $zoneId;
        if (!$this->isPost()) {
            $this->redirect('/admin/shipping/zones/' . $zoneId . '/methods/create');
            return;
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/shipping/zones/' . $zoneId . '/methods/create');
            return;
        }

        $data = $this->collectMethodData($zoneId);

        try {
            $methodId = $this->methodModel->createMethod($data);
            
            // Add weight brackets if provided
            $this->saveWeightBrackets($methodId);
            
            $this->sessionManager->flash('success', 'Shipping method created successfully.');
            $this->redirect('/admin/shipping');
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), '/admin/shipping/zones/' . $zoneId . '/methods/create');
        }
    }

    /**
     * Show edit method form
     */
    public function editMethod($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();
        
        $method = $this->methodModel->getWithBrackets($id);
        
        if (!$method) {
            $this->sessionManager->flash('error', 'Shipping method not found.');
            $this->redirect('/admin/shipping');
            return;
        }

        $zone = $this->zoneModel->find((int) $method['zone_id']);

        $this->adminView('shipping/edit_method', [
            'title' => 'Edit Shipping Method - Admin',
            'method' => $method,
            'zone' => $zone,
            'csrf_token' => $this->sessionManager->getCsrfToken()
        ]);
    }

    /**
     * Update shipping method
     */
    public function updateMethod($id): void
    {
        $id = (int) $id;
        if (!$this->isPost()) {
            $this->redirect('/admin/shipping/methods/' . $id . '/edit');
            return;
        }

        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->handleError('Invalid security token.', '/admin/shipping/methods/' . $id . '/edit');
            return;
        }

        $method = $this->methodModel->find($id);
        
        if (!$method) {
            $this->handleError('Shipping method not found.', '/admin/shipping');
            return;
        }

        $data = $this->collectMethodData((int) $method['zone_id']);
        unset($data['zone_id']); // Don't update zone_id

        try {
            $this->methodModel->updateMethod($id, $data);
            
            // Update weight brackets
            $this->methodModel->clearWeightBrackets($id);
            $this->saveWeightBrackets($id);
            
            $this->sessionManager->flash('success', 'Shipping method updated successfully.');
            $this->redirect('/admin/shipping');
        } catch (Exception $e) {
            $this->handleError($e->getMessage(), '/admin/shipping/methods/' . $id . '/edit');
        }
    }

    /**
     * Delete shipping method
     */
    public function destroyMethod($id): void
    {
        $id = (int) $id;
        $this->sessionManager->start();

        if (!$this->sessionManager->validateCsrfToken($this->input('csrf_token', ''))) {
            $this->json(['success' => false, 'error' => 'Invalid security token'], 400);
            return;
        }

        try {
            $this->methodModel->delete($id);
            
            if ($this->isAjax()) {
                $this->json(['success' => true]);
            } else {
                $this->sessionManager->flash('success', 'Shipping method deleted successfully.');
                $this->redirect('/admin/shipping');
            }
        } catch (Exception $e) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'error' => $e->getMessage()], 400);
            } else {
                $this->handleError($e->getMessage(), '/admin/shipping');
            }
        }
    }

    /**
     * Collect method data from form
     */
    private function collectMethodData(int $zoneId): array
    {
        return [
            'zone_id' => $zoneId,
            'name' => $this->sanitize($this->input('name', '')),
            'description' => $this->sanitize($this->input('description', '')),
            'base_cost' => (float) $this->input('base_cost', 0),
            'cost_per_kg' => (float) $this->input('cost_per_kg', 0),
            'min_weight' => $this->input('min_weight', '') !== '' ? (float) $this->input('min_weight') : null,
            'max_weight' => $this->input('max_weight', '') !== '' ? (float) $this->input('max_weight') : null,
            'min_order_amount' => $this->input('min_order_amount', '') !== '' ? (float) $this->input('min_order_amount') : null,
            'free_shipping_threshold' => $this->input('free_shipping_threshold', '') !== '' ? (float) $this->input('free_shipping_threshold') : null,
            'estimated_days_min' => $this->input('estimated_days_min', '') !== '' ? (int) $this->input('estimated_days_min') : null,
            'estimated_days_max' => $this->input('estimated_days_max', '') !== '' ? (int) $this->input('estimated_days_max') : null,
            'is_active' => $this->input('is_active', '1') === '1' ? 1 : 0,
            'sort_order' => (int) $this->input('sort_order', 0)
        ];
    }

    /**
     * Save weight brackets from form
     */
    private function saveWeightBrackets(int $methodId): void
    {
        $minWeights = $this->input('bracket_min_weight', []);
        $maxWeights = $this->input('bracket_max_weight', []);
        $costs = $this->input('bracket_cost', []);

        if (!is_array($minWeights)) {
            return;
        }

        foreach ($minWeights as $index => $minWeight) {
            if ($minWeight === '' || !isset($costs[$index]) || $costs[$index] === '') {
                continue;
            }

            $this->methodModel->addWeightBracket($methodId, [
                'min_weight' => (float) $minWeight,
                'max_weight' => isset($maxWeights[$index]) && $maxWeights[$index] !== '' 
                    ? (float) $maxWeights[$index] 
                    : null,
                'cost' => (float) $costs[$index]
            ]);
        }
    }

    /**
     * Handle error and redirect
     */
    private function handleError(string $message, string $redirect): void
    {
        $this->sessionManager->flash('error', $message);
        $this->redirect($redirect);
    }

    /**
     * Render admin view with layout
     */
    protected function adminView(string $view, array $data = []): void
    {
        extract($data);
        
        $viewFile = VIEWS_PATH . '/admin/' . $view . '.php';
        
        if (!file_exists($viewFile)) {
            throw new RuntimeException("Admin view '{$view}' not found.");
        }

        include $viewFile;
    }
}
