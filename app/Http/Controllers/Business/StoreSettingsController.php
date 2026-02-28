<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StoreDeliveryZone;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class StoreSettingsController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

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
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    public function update(Request $request)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = [
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $store->description,
            'phone' => $validated['phone'] ?? $store->phone,
            'address' => $validated['address'] ?? $store->address,
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            if ($store->logo_url) {
                $oldPath = str_replace('/storage/', '', $store->logo_url);
                Storage::disk('public')->delete($oldPath);
            }
            $data['logo_url'] = '/storage/' . $request->file('logo')->store('store/logos', 'public');
        }

        // Handle banner upload
        if ($request->hasFile('banner')) {
            if ($store->banner_url) {
                $oldPath = str_replace('/storage/', '', $store->banner_url);
                Storage::disk('public')->delete($oldPath);
            }
            $data['banner_url'] = '/storage/' . $request->file('banner')->store('store/banners', 'public');
        }

        $store->update($data);

        return back()->with('success', 'Do\'kon sozlamalari saqlandi.');
    }

    public function updateTheme(Request $request)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'primary_color' => 'required|string|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        $currentTheme = $store->theme ?? [];

        $store->update([
            'theme' => array_merge($currentTheme, [
                'primary_color' => $validated['primary_color'],
                'secondary_color' => $validated['secondary_color'] ?? $currentTheme['secondary_color'] ?? '#64748b',
            ]),
        ]);

        return back()->with('success', 'Do\'kon dizayni yangilandi.');
    }

    public function updateInfo(Request $request)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'about_us' => 'nullable|string|max:5000',
            'working_hours' => 'nullable|string|max:255',
            'telegram' => 'nullable|string|max:100',
            'instagram' => 'nullable|string|max:100',
            'website' => 'nullable|string|max:255',
        ]);

        $settings = $store->settings ?? [];
        $settings['about_us'] = $validated['about_us'] ?? null;
        $settings['working_hours'] = $validated['working_hours'] ?? null;
        $settings['telegram'] = $validated['telegram'] ?? null;
        $settings['instagram'] = $validated['instagram'] ?? null;
        $settings['website'] = $validated['website'] ?? null;

        $store->update(['settings' => $settings]);

        return back()->with('success', 'Ma\'lumotlar saqlandi.');
    }

    public function storeDeliveryZone(Request $request)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|string|max:100',
        ]);

        StoreDeliveryZone::create([
            'store_id' => $store->id,
            'name' => $validated['name'],
            'delivery_fee' => $validated['delivery_fee'],
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Yetkazish zonasi qo\'shildi.');
    }

    public function updateDeliveryZone(Request $request, string $zoneId)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $zone = StoreDeliveryZone::where('store_id', $store->id)->findOrFail($zoneId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'delivery_fee' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'estimated_time' => 'nullable|string|max:100',
        ]);

        $zone->update([
            'name' => $validated['name'],
            'delivery_fee' => $validated['delivery_fee'],
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'estimated_time' => $validated['estimated_time'] ?? null,
        ]);

        return back()->with('success', 'Yetkazish zonasi yangilandi.');
    }

    public function destroyDeliveryZone(string $zoneId)
    {
        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $zone = StoreDeliveryZone::where('store_id', $store->id)->findOrFail($zoneId);
        $zone->delete();

        return back()->with('success', 'Yetkazish zonasi o\'chirildi.');
    }
}
