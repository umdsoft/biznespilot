<?php

namespace App\Http\Resources\Store;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Store information resource for Mini App.
 *
 * Returns public store info: name, logo, banner, theme, contact info, currency.
 */
class StoreInfoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo_url' => $this->logo_url,
            'banner_url' => $this->banner_url,
            'phone' => $this->phone,
            'address' => $this->address,
            'currency' => $this->currency ?? 'UZS',
            'is_active' => $this->is_active,
            'theme' => [
                'primary_color' => $this->getThemeColor('primary_color', '#2563eb'),
                'secondary_color' => $this->getThemeColor('secondary_color', '#64748b'),
                'accent_color' => $this->getThemeColor('accent_color', '#f59e0b'),
                'background_color' => $this->getThemeColor('background_color', '#ffffff'),
                'text_color' => $this->getThemeColor('text_color', '#1e293b'),
                'header_style' => data_get($this->theme, 'header_style', 'default'),
                'product_card_style' => data_get($this->theme, 'product_card_style', 'grid'),
            ],
            'settings' => [
                'min_order_amount' => $this->getSetting('min_order_amount', 0),
                'delivery_enabled' => $this->getSetting('delivery_enabled', true),
                'pickup_enabled' => $this->getSetting('pickup_enabled', false),
                'payment_methods' => $this->getSetting('payment_methods', ['cash']),
                'working_hours' => $this->getSetting('working_hours'),
            ],
            'stats' => [
                'products_count' => $this->when(
                    $this->relationLoaded('products'),
                    fn () => $this->products->where('is_active', true)->count(),
                    fn () => $this->getActiveProductsCount()
                ),
                'categories_count' => $this->when(
                    $this->relationLoaded('categories'),
                    fn () => $this->categories->where('is_active', true)->count()
                ),
            ],
            'mini_app_url' => $this->getMiniAppUrl(),
        ];
    }
}
