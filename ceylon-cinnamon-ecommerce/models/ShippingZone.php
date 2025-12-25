<?php
/**
 * Shipping Zone Model
 * Handles shipping zones with country assignments
 * 
 * Requirements:
 * - 14.1: Display shipping rates by country
 * - 14.5: Admin shipping rule management
 */

declare(strict_types=1);

class ShippingZone extends Model
{
    protected string $table = 'shipping_zones';

    /**
     * Get all active shipping zones
     * 
     * @return array Active zones
     */
    public function getActive(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get all zones with their shipping methods
     * 
     * @return array Zones with methods
     */
    public function getAllWithMethods(): array
    {
        $zones = $this->all(100, 0);
        
        foreach ($zones as &$zone) {
            $zone['countries_array'] = $this->parseCountries($zone['countries']);
            $zone['methods'] = $this->getMethods((int) $zone['id']);
        }
        
        return $zones;
    }

    /**
     * Find zone by country code
     * Requirement 14.1: Shipping rates by country
     * 
     * @param string $countryCode ISO country code (e.g., 'US', 'LK')
     * @return array|null Zone data or null
     */
    public function findByCountry(string $countryCode): ?array
    {
        $countryCode = strtoupper(trim($countryCode));
        
        $sql = "SELECT * FROM {$this->table} 
                WHERE is_active = 1 
                AND (
                    countries LIKE :exact 
                    OR countries LIKE :start 
                    OR countries LIKE :middle 
                    OR countries LIKE :end
                )
                ORDER BY sort_order ASC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'exact' => "[\"$countryCode\"]",
            'start' => "[\"$countryCode\",%",
            'middle' => "%,\"$countryCode\",%",
            'end' => "%,\"$countryCode\"]"
        ]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get shipping methods for a zone
     * 
     * @param int $zoneId Zone ID
     * @param bool $activeOnly Only return active methods
     * @return array Shipping methods
     */
    public function getMethods(int $zoneId, bool $activeOnly = true): array
    {
        $sql = "SELECT * FROM shipping_methods WHERE zone_id = :zone_id";
        
        if ($activeOnly) {
            $sql .= " AND is_active = 1";
        }
        
        $sql .= " ORDER BY sort_order ASC, name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['zone_id' => $zoneId]);
        return $stmt->fetchAll();
    }

    /**
     * Create a new shipping zone
     * 
     * @param array $data Zone data
     * @return int New zone ID
     */
    public function createZone(array $data): int
    {
        $this->validateZoneData($data);
        
        return $this->create([
            'name' => $data['name'],
            'countries' => $this->formatCountries($data['countries']),
            'is_active' => $data['is_active'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0
        ]);
    }

    /**
     * Update a shipping zone
     * 
     * @param int $id Zone ID
     * @param array $data Zone data
     * @return bool Success
     */
    public function updateZone(int $id, array $data): bool
    {
        $updateData = [];
        
        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }
        
        if (isset($data['countries'])) {
            $updateData['countries'] = $this->formatCountries($data['countries']);
        }
        
        if (isset($data['is_active'])) {
            $updateData['is_active'] = (int) $data['is_active'];
        }
        
        if (isset($data['sort_order'])) {
            $updateData['sort_order'] = (int) $data['sort_order'];
        }
        
        return $this->update($id, $updateData);
    }

    /**
     * Parse countries JSON string to array
     * 
     * @param string $countries JSON string
     * @return array Country codes
     */
    public function parseCountries(string $countries): array
    {
        $decoded = json_decode($countries, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Format countries array to JSON string
     * 
     * @param array|string $countries Countries
     * @return string JSON string
     */
    private function formatCountries(array|string $countries): string
    {
        if (is_string($countries)) {
            // Already JSON or comma-separated
            $decoded = json_decode($countries, true);
            if (is_array($decoded)) {
                return $countries;
            }
            // Comma-separated string
            $countries = array_map('trim', explode(',', $countries));
        }
        
        // Normalize country codes to uppercase
        $countries = array_map('strtoupper', array_filter($countries));
        
        return json_encode(array_values($countries));
    }

    /**
     * Validate zone data
     * 
     * @param array $data Zone data
     * @throws InvalidArgumentException If validation fails
     */
    private function validateZoneData(array $data): void
    {
        if (empty($data['name'])) {
            throw new InvalidArgumentException("Zone name is required");
        }
        
        if (empty($data['countries'])) {
            throw new InvalidArgumentException("At least one country is required");
        }
    }

    /**
     * Get list of all supported countries
     * 
     * @return array Country list with codes and names
     */
    public static function getSupportedCountries(): array
    {
        return [
            'AF' => 'Afghanistan', 'AL' => 'Albania', 'DZ' => 'Algeria',
            'AR' => 'Argentina', 'AU' => 'Australia', 'AT' => 'Austria',
            'BD' => 'Bangladesh', 'BE' => 'Belgium', 'BR' => 'Brazil',
            'CA' => 'Canada', 'CN' => 'China', 'CO' => 'Colombia',
            'DK' => 'Denmark', 'EG' => 'Egypt', 'FI' => 'Finland',
            'FR' => 'France', 'DE' => 'Germany', 'GR' => 'Greece',
            'HK' => 'Hong Kong', 'IN' => 'India', 'ID' => 'Indonesia',
            'IE' => 'Ireland', 'IL' => 'Israel', 'IT' => 'Italy',
            'JP' => 'Japan', 'KE' => 'Kenya', 'KR' => 'South Korea',
            'LK' => 'Sri Lanka', 'MY' => 'Malaysia', 'MV' => 'Maldives',
            'MX' => 'Mexico', 'NL' => 'Netherlands', 'NZ' => 'New Zealand',
            'NO' => 'Norway', 'PK' => 'Pakistan', 'PH' => 'Philippines',
            'PL' => 'Poland', 'PT' => 'Portugal', 'QA' => 'Qatar',
            'RU' => 'Russia', 'SA' => 'Saudi Arabia', 'SG' => 'Singapore',
            'ZA' => 'South Africa', 'ES' => 'Spain', 'SE' => 'Sweden',
            'CH' => 'Switzerland', 'TW' => 'Taiwan', 'TH' => 'Thailand',
            'TR' => 'Turkey', 'AE' => 'United Arab Emirates',
            'GB' => 'United Kingdom', 'US' => 'United States',
            'VN' => 'Vietnam'
        ];
    }
}
