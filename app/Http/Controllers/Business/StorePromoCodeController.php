<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasActiveStore;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Http\Controllers\Traits\HasStorePanelType;
use App\Models\Store\StorePromoCode;
use App\Models\Store\TelegramStore;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StorePromoCodeController extends Controller
{
    use HasActiveStore, HasCurrentBusiness, HasStorePanelType;

    /**
     * List all promo codes for the store
     */
    public function index(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return $this->redirectToStoreSetup('Avval do\'kon yarating.');
        }

        $query = StorePromoCode::where('store_id', $store->id);

        // Filter by status
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->where('is_active', true),
                'inactive' => $query->where('is_active', false),
                'expired' => $query->where('expires_at', '<', now()),
                'valid' => $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                            ->orWhere('expires_at', '>', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('max_uses')
                            ->orWhereColumn('used_count', '<', 'max_uses');
                    }),
                default => null,
            };
        }

        $promoCodes = $query->latest()
            ->paginate(30)
            ->through(fn ($promo) => [
                'id' => $promo->id,
                'code' => $promo->code,
                'type' => $promo->type,
                'type_label' => $promo->type === StorePromoCode::TYPE_PERCENT ? 'Foiz' : 'Belgilangan summa',
                'value' => $promo->value,
                'display_value' => $promo->type === StorePromoCode::TYPE_PERCENT
                    ? $promo->value . '%'
                    : number_format($promo->value, 0, '.', ' ') . ' so\'m',
                'min_order_amount' => $promo->min_order_amount,
                'max_uses' => $promo->max_uses,
                'used_count' => $promo->used_count,
                'starts_at' => $promo->starts_at?->format('d.m.Y H:i'),
                'expires_at' => $promo->expires_at?->format('d.m.Y H:i'),
                'is_active' => $promo->is_active,
                'is_valid' => $promo->isValid(),
                'is_expired' => $promo->expires_at && $promo->expires_at->isPast(),
                'is_exhausted' => $promo->max_uses && $promo->used_count >= $promo->max_uses,
                'created_at' => $promo->created_at?->format('d.m.Y'),
            ]);

        // Single-query stats — 3 serial aggregates → 1 SELECT
        $statsRow = \DB::table('store_promo_codes')
            ->where('store_id', $store->id)
            ->selectRaw(
                'COUNT(*) AS total_codes, '
                . 'SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_codes, '
                . 'COALESCE(SUM(used_count), 0) AS total_used'
            )
            ->first();

        return Inertia::render('Business/Store/PromoCodes/Index', [
            'promoCodes' => $promoCodes,
            'filters' => $request->only(['status']),
            'stats' => [
                'total_codes' => (int) ($statsRow->total_codes ?? 0),
                'active_codes' => (int) ($statsRow->active_codes ?? 0),
                'total_used' => (int) ($statsRow->total_used ?? 0),
            ],
            'panelType' => $this->getStorePanelTypeForInertia(),
        ]);
    }

    /**
     * Create a new promo code
     */
    public function store(Request $request)
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
            'code' => 'nullable|string|max:50',
            'type' => 'required|string|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        // Validate percent value <= 100
        if ($validated['type'] === StorePromoCode::TYPE_PERCENT && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Foiz 100 dan oshmasligi kerak.']);
        }

        // Generate code if not provided
        $code = ! empty($validated['code'])
            ? strtoupper(trim($validated['code']))
            : strtoupper(Str::random(8));

        // Check for duplicate code in this store
        $duplicateExists = StorePromoCode::where('store_id', $store->id)
            ->where('code', $code)
            ->exists();

        if ($duplicateExists) {
            return back()->withErrors(['code' => 'Bu kod allaqachon mavjud.']);
        }

        StorePromoCode::create([
            'store_id' => $store->id,
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'used_count' => 0,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return $this->storeRedirect('promo-codes.index')
            ->with('success', "Promo kod \"{$code}\" muvaffaqiyatli yaratildi.");
    }

    /**
     * Update an existing promo code
     */
    public function update(Request $request, string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $promoCode = StorePromoCode::where('store_id', $store->id)->findOrFail($id);

        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'type' => 'required|string|in:fixed,percent',
            'value' => 'required|numeric|min:0.01',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'boolean',
        ]);

        // Validate percent value <= 100
        if ($validated['type'] === StorePromoCode::TYPE_PERCENT && $validated['value'] > 100) {
            return back()->withErrors(['value' => 'Foiz 100 dan oshmasligi kerak.']);
        }

        $code = ! empty($validated['code'])
            ? strtoupper(trim($validated['code']))
            : $promoCode->code;

        // Check for duplicate code (excluding current)
        if ($code !== $promoCode->code) {
            $duplicateExists = StorePromoCode::where('store_id', $store->id)
                ->where('code', $code)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicateExists) {
                return back()->withErrors(['code' => 'Bu kod allaqachon mavjud.']);
            }
        }

        $promoCode->update([
            'code' => $code,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'min_order_amount' => $validated['min_order_amount'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'starts_at' => $validated['starts_at'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => $validated['is_active'] ?? $promoCode->is_active,
        ]);

        return $this->storeRedirect('promo-codes.index')
            ->with('success', 'Promo kod yangilandi.');
    }

    /**
     * Delete a promo code
     */
    public function destroy(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $promoCode = StorePromoCode::where('store_id', $store->id)->findOrFail($id);

        // Allow deletion even if used, but warn in UI
        $promoCode->delete();

        return $this->storeRedirect('promo-codes.index')
            ->with('success', 'Promo kod o\'chirildi.');
    }

    /**
     * Toggle promo code active status
     */
    public function toggleStatus(string $id)
    {
        $business = $this->getCurrentBusiness();

        if (! $business) {
            return redirect()->route('login');
        }

        $store = $this->getStore();

        if (! $store) {
            return back()->with('error', 'Do\'kon topilmadi.');
        }

        $promoCode = StorePromoCode::where('store_id', $store->id)->findOrFail($id);

        $promoCode->update(['is_active' => ! $promoCode->is_active]);

        $statusText = $promoCode->is_active ? 'faollashtirildi' : 'o\'chirildi';

        return back()->with('success', "Promo kod {$statusText}.");
    }
}
