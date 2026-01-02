<?php

namespace App\Services\KPI;

/**
 * Maps Business category/industry to KPI industry codes
 *
 * Business modelidagi category yoki industry maydonidan
 * IndustryKpiConfiguration uchun to'g'ri industry_code ni aniqlaydi
 */
class BusinessCategoryMapper
{
    /**
     * Business kategoriyasidan KPI industry code ni aniqlash
     *
     * @param string|null $category Business category
     * @param string|null $industry Business industry
     * @param string|null $businessType Business type
     * @return string Industry code for KPI configuration
     */
    public static function getIndustryCode(
        ?string $category = null,
        ?string $industry = null,
        ?string $businessType = null
    ): string {
        // Priority order: category -> industry -> businessType -> default
        $searchTerm = strtolower($category ?? $industry ?? $businessType ?? '');

        // Direct mapping - aniq moslik
        $directMapping = [
            // E-commerce variants
            'ecommerce' => 'ecommerce',
            'e-commerce' => 'ecommerce',
            'online_store' => 'ecommerce',
            'online savdo' => 'ecommerce',
            'onlayn savdo' => 'ecommerce',
            'internet magazin' => 'ecommerce',
            'marketplace' => 'ecommerce',

            // Restaurant variants
            'restaurant' => 'restaurant',
            'restoran' => 'restaurant',
            'oshxona' => 'restaurant',
            'cafe' => 'restaurant',
            'kafe' => 'restaurant',
            'fast food' => 'restaurant',
            'food' => 'restaurant',
            'ovqat' => 'restaurant',

            // Retail variants
            'retail' => 'retail',
            'chakana savdo' => 'retail',
            'do\'kon' => 'retail',
            'dukon' => 'retail',
            'magazin' => 'retail',
            'store' => 'retail',
            'shop' => 'retail',

            // Service variants
            'service' => 'service',
            'xizmat' => 'service',
            'consulting' => 'service',
            'konsalting' => 'service',
            'agency' => 'service',
            'agentlik' => 'service',

            // SaaS variants
            'saas' => 'saas',
            'software' => 'saas',
            'dasturiy ta\'minot' => 'saas',
            'platform' => 'saas',
            'platforma' => 'saas',
            'app' => 'saas',
            'application' => 'saas',

            // Beauty variants
            'beauty' => 'beauty',
            'go\'zallik' => 'beauty',
            'gozallik' => 'beauty',
            'salon' => 'beauty',
            'barbershop' => 'beauty',
            'sartaroshxona' => 'beauty',
            'spa' => 'beauty',
            'cosmetics' => 'beauty',

            // Fitness variants
            'fitness' => 'fitness',
            'gym' => 'fitness',
            'sport zal' => 'fitness',
            'sport' => 'fitness',
            'yoga' => 'fitness',
            'studio' => 'fitness',
        ];

        // Check direct mapping
        if (isset($directMapping[$searchTerm])) {
            return $directMapping[$searchTerm];
        }

        // Partial matching - qisman moslik
        $partialMapping = [
            'e-comm' => 'ecommerce',
            'online' => 'ecommerce',
            'savdo' => 'retail',
            'rest' => 'restaurant',
            'osh' => 'restaurant',
            'kafe' => 'restaurant',
            'soft' => 'saas',
            'dastur' => 'saas',
            'xizmat' => 'service',
            'konsalt' => 'service',
            'go\'zal' => 'beauty',
            'salon' => 'beauty',
            'sport' => 'fitness',
            'fitnes' => 'fitness',
        ];

        foreach ($partialMapping as $keyword => $industryCode) {
            if (str_contains($searchTerm, $keyword)) {
                return $industryCode;
            }
        }

        // Default - agar hech narsa topilmasa
        return 'default';
    }

    /**
     * Get industry display name (UZ)
     *
     * @param string $industryCode
     * @return string
     */
    public static function getIndustryName(string $industryCode): string
    {
        $names = [
            'ecommerce' => 'E-commerce / Online Savdo',
            'restaurant' => 'Restoran / Ovqatlanish',
            'retail' => 'Chakana Savdo / Do\'kon',
            'service' => 'Xizmat Ko\'rsatish',
            'saas' => 'SaaS / Dasturiy Ta\'minot',
            'beauty' => 'Go\'zallik Saloni',
            'fitness' => 'Fitnes / Sport Zal',
            'default' => 'Umumiy Biznes',
        ];

        return $names[$industryCode] ?? 'Umumiy Biznes';
    }

    /**
     * Get all available industry codes
     *
     * @return array
     */
    public static function getAvailableIndustries(): array
    {
        return [
            [
                'code' => 'ecommerce',
                'name' => 'E-commerce / Online Savdo',
                'icon' => '🛒',
                'kpi_count' => 10,
            ],
            [
                'code' => 'restaurant',
                'name' => 'Restoran / Ovqatlanish',
                'icon' => '🍽️',
                'kpi_count' => 10,
            ],
            [
                'code' => 'retail',
                'name' => 'Chakana Savdo / Do\'kon',
                'icon' => '🏪',
                'kpi_count' => 10,
            ],
            [
                'code' => 'service',
                'name' => 'Xizmat Ko\'rsatish',
                'icon' => '💼',
                'kpi_count' => 9,
            ],
            [
                'code' => 'saas',
                'name' => 'SaaS / Dasturiy Ta\'minot',
                'icon' => '💻',
                'kpi_count' => 10,
            ],
            [
                'code' => 'beauty',
                'name' => 'Go\'zallik Saloni',
                'icon' => '💄',
                'kpi_count' => 9,
            ],
            [
                'code' => 'fitness',
                'name' => 'Fitnes / Sport Zal',
                'icon' => '💪',
                'kpi_count' => 9,
            ],
        ];
    }

    /**
     * Detect industry from Business model
     *
     * @param \App\Models\Business $business
     * @return string
     */
    public static function detectFromBusiness($business): string
    {
        return self::getIndustryCode(
            $business->category,
            $business->industry,
            $business->business_type
        );
    }

    /**
     * Validate industry code
     *
     * @param string $code
     * @return bool
     */
    public static function isValidIndustry(string $code): bool
    {
        $validCodes = array_column(self::getAvailableIndustries(), 'code');
        return in_array($code, $validCodes);
    }

    /**
     * Get industry icon
     *
     * @param string $industryCode
     * @return string
     */
    public static function getIndustryIcon(string $industryCode): string
    {
        $icons = [
            'ecommerce' => '🛒',
            'restaurant' => '🍽️',
            'retail' => '🏪',
            'service' => '💼',
            'saas' => '💻',
            'beauty' => '💄',
            'fitness' => '💪',
            'default' => '📊',
        ];

        return $icons[$industryCode] ?? '📊';
    }

    /**
     * Get KPI count for industry
     *
     * @param string $industryCode
     * @return int
     */
    public static function getKpiCount(string $industryCode): int
    {
        $kpis = IndustryKpiConfiguration::getIndustryKpis($industryCode);
        return count(array_filter($kpis)); // Filter out nulls
    }
}
