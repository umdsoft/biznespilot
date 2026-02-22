<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreDeliveryZone;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StoreSettingsController extends Controller
{
    use HasCurrentBusiness, HasStorePanelType;

    /**
     * Get the store for the current business
     */
    protected function getStore(): ?TelegramStore
    {
        $business = $this->getCurrentBusiness();

        return TelegramStore::where('business_id', $business->id)->first();
    }

    /**
     * Show store settings page
     */
    public function index()
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        $store->load('telegramBot');

        // Get delivery zones
        $deliveryZones = StoreDeliveryZone::where('store_id', $store->id)
            ->orderBy('name')
            ->get()
            ->map(fn ($zone) => [
                'id' => $zone->id,
                'name' => $zone->name,
                'delivery_fee' => $zone->delivery_fee,
                'min_order_amount' => $zone->min_order_amount,
                'estimated_time' => $zone->estimated_time,
                'is_active' => $zone->is_active,
            ]);

        return Inertia::render('Business/Store/Settings/Index', [
            'store' => [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'description' => $store->description,
                'logo_url' => $store->logo_url,
                'banner_url' => $store->banner_url,
                'phone' => $store->phone,
                'address' => $store->address,
                'currency' => $store->currency,
                'is_active' => $store->is_active,
                'settings' => $store->settings,
                'theme' => $store->theme,
                'mini_app_url' => $store->getMiniAppUrl(),
                'telegram_bot' => $store->telegramBot ? [
                    'id' => $store->telegramBot->id,
                    'username' => $store->telegramBot->bot_username,
                    'first_name' => $store->telegramBot->bot_first_name,
                    'is_active' => $store->telegramBot->is_active,
                ] : null,
            ],
            'deliveryZones' => $deliveryZones,
            'currencyOptions' => [
                ['value' => 'UZS', 'label' => 'UZS - O\'zbek so\'mi'],
                ['value' => 'USD', 'label' => 'USD - AQSH dollari'],
                ['value' => 'RUB', 'label' => 'RUB - Rossiya rubli'],
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Update general store settings
     */
    public function update(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'currency' => 'required|string|in:UZS,USD,RUB',
            'logo_url' => 'nullable|string|url|max:500',
            'banner_url' => 'nullable|string|url|max:500',
            'is_active' => 'boolean',
            // Settings sub-fields
            'settings.order_confirmation_message' => 'nullable|string|max:1000',
            'settings.min_order_amount' => 'nullable|numeric|min:0',
            'settings.max_order_items' => 'nullable|integer|min:1|max:100',
            'settings.working_hours_start' => 'nullable|string|max:5',
            'settings.working_hours_end' => 'nullable|string|max:5',
            'settings.auto_confirm_orders' => 'nullable|boolean',
            'settings.enable_reviews' => 'nullable|boolean',
            'settings.enable_promo_codes' => 'nullable|boolean',
            'settings.notification_chat_id' => 'nullable|string|max:50',
        ]);

        // Merge settings with existing
        $currentSettings = $store->settings ?? [];
        $newSettings = $validated['settings'] ?? [];

        foreach ($newSettings as $key => $value) {
            $currentSettings[$key] = $value;
        }

        $store->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $store->description,
            'phone' => $validated['phone'] ?? $store->phone,
            'address' => $validated['address'] ?? $store->address,
            'currency' => $validated['currency'],
            'logo_url' => $validated['logo_url'] ?? $store->logo_url,
            'banner_url' => $validated['banner_url'] ?? $store->banner_url,
            'is_active' => $validated['is_active'] ?? $store->is_active,
            'settings' => $currentSettings,
        ]);

        return $this->storeRedirect('settings')
            ->with('success', 'Do\'kon sozlamalari saqlandi.');
    }

    /**
     * Update store theme/appearance settings
     */
    public function updateTheme(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'background_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'text_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
            'button_style' => 'nullable|string|in:rounded,square,pill',
            'header_style' => 'nullable|string|in:default,compact,minimal',
            'product_card_style' => 'nullable|string|in:grid,list,compact',
            'show_banner' => 'nullable|boolean',
            'show_categories_bar' => 'nullable|boolean',
            'show_search' => 'nullable|boolean',
            'products_per_row' => 'nullable|integer|in:1,2,3',
        ]);

        $theme = [
            'primary_color' => $validated['primary_color'],
            'secondary_color' => $validated['secondary_color'] ?? '#64748b',
            'background_color' => $validated['background_color'] ?? '#ffffff',
            'text_color' => $validated['text_color'] ?? '#1e293b',
            'button_style' => $validated['button_style'] ?? 'rounded',
            'header_style' => $validated['header_style'] ?? 'default',
            'product_card_style' => $validated['product_card_style'] ?? 'grid',
            'show_banner' => $validated['show_banner'] ?? true,
            'show_categories_bar' => $validated['show_categories_bar'] ?? true,
            'show_search' => $validated['show_search'] ?? true,
            'products_per_row' => $validated['products_per_row'] ?? 2,
        ];

        $store->update(['theme' => $theme]);

        return $this->storeRedirect('settings')
            ->with('success', 'Do\'kon dizayni yangilandi.');
    }

    /**
     * Update delivery zones (bulk create/update/delete)
     */
    public function updateDeliveryZones(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'zones' => 'required|array',
            'zones.*.id' => 'nullable|string|exists:store_delivery_zones,id',
            'zones.*.name' => 'required|string|max:255',
            'zones.*.delivery_fee' => 'required|numeric|min:0',
            'zones.*.min_order_amount' => 'nullable|numeric|min:0',
            'zones.*.estimated_time' => 'nullable|string|max:100',
            'zones.*.is_active' => 'boolean',
        ]);

        $existingZoneIds = StoreDeliveryZone::where('store_id', $store->id)->pluck('id')->toArray();
        $incomingZoneIds = [];

        foreach ($validated['zones'] as $zoneData) {
            if (! empty($zoneData['id'])) {
                // Update existing zone (verify it belongs to this store)
                $zone = StoreDeliveryZone::where('store_id', $store->id)
                    ->where('id', $zoneData['id'])
                    ->first();

                if ($zone) {
                    $zone->update([
                        'name' => $zoneData['name'],
                        'delivery_fee' => $zoneData['delivery_fee'],
                        'min_order_amount' => $zoneData['min_order_amount'] ?? null,
                        'estimated_time' => $zoneData['estimated_time'] ?? null,
                        'is_active' => $zoneData['is_active'] ?? true,
                    ]);

                    $incomingZoneIds[] = $zone->id;
                }
            } else {
                // Create new zone
                $newZone = StoreDeliveryZone::create([
                    'store_id' => $store->id,
                    'name' => $zoneData['name'],
                    'delivery_fee' => $zoneData['delivery_fee'],
                    'min_order_amount' => $zoneData['min_order_amount'] ?? null,
                    'estimated_time' => $zoneData['estimated_time'] ?? null,
                    'is_active' => $zoneData['is_active'] ?? true,
                ]);

                $incomingZoneIds[] = $newZone->id;
            }
        }

        // Delete zones that were removed from the list
        $zonesToDelete = array_diff($existingZoneIds, $incomingZoneIds);
        if (! empty($zonesToDelete)) {
            StoreDeliveryZone::where('store_id', $store->id)
                ->whereIn('id', $zonesToDelete)
                ->delete();
        }

        return $this->storeRedirect('settings')
            ->with('success', 'Yetkazish hududlari yangilandi.');
    }
}
