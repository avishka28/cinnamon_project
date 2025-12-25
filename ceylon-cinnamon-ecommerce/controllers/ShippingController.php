<?php
/**
 * Shipping Controller
 * Handles public shipping information display
 * 
 * Requirements:
 * - 14.1: Display shipping rates by country and weight brackets
 * - 14.4: Display estimated delivery dates based on shipping method and destination
 */

declare(strict_types=1);

class ShippingController extends Controller
{
    private ShippingCalculator $calculator;

    public function __construct()
    {
        parent::__construct();
        $this->calculator = new ShippingCalculator();
    }

    /**
     * Display shipping information page
     * Requirement 14.1: Display shipping rates by country
     */
    public function index(): void
    {
        $countries = ShippingZone::getSupportedCountries();

        $this->view('pages.shipping', [
            'title' => 'Shipping Information',
            'countries' => $countries
        ]);
    }

    /**
     * Get shipping rates for a country (API endpoint)
     * Requirement 14.1: Display shipping rates by country and weight brackets
     */
    public function getRates(): void
    {
        $countryCode = $this->input('country', '');
        
        if (empty($countryCode)) {
            $this->json(['error' => 'Country is required'], 400);
            return;
        }

        $rates = $this->calculator->getShippingRatesDisplay($countryCode);
        $this->json($rates);
    }

    /**
     * Get delivery estimate for a method (API endpoint)
     * Requirement 14.4: Display estimated delivery dates
     */
    public function getDeliveryEstimate(): void
    {
        $methodId = (int) $this->input('method_id', 0);
        
        if ($methodId <= 0) {
            $this->json(['error' => 'Invalid method ID'], 400);
            return;
        }

        $estimate = $this->calculator->getDeliveryEstimate($methodId);
        
        if ($estimate) {
            $this->json([
                'success' => true,
                'estimate' => $estimate
            ]);
        } else {
            $this->json([
                'success' => false,
                'error' => 'Delivery estimate not available'
            ]);
        }
    }
}
