<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\Partner;
use App\Models\Partner\PartnerTierRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Partner bo'lishni hoxlagan userlar uchun apply flow.
 */
class PartnerApplyController extends Controller
{
    public function show()
    {
        $tiers = PartnerTierRule::ordered();

        return Inertia::render('Partner/Apply', [
            'tiers' => $tiers->map(fn ($t) => [
                'tier' => $t->tier,
                'name' => $t->name,
                'icon' => $t->icon,
                'first_payment_rate' => (float) $t->year_one_rate,
                'lifetime_rate' => (float) $t->lifetime_rate,
                'min_active_referrals' => $t->min_active_referrals,
                'perks' => $t->perks ?? [],
            ]),
        ]);
    }

    public function submit(Request $request)
    {
        $user = $request->user();
        abort_unless($user, 401);

        // Allaqachon partner bo'lsa — dashboard'ga
        if (Partner::where('user_id', $user->id)->exists()) {
            // Role'ni ta'minlaymiz (legacy account'lar uchun)
            if (! $user->hasRole('partner')) {
                $user->assignRole('partner');
            }
            return redirect()->route('partner.dashboard');
        }

        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'required|string|max:30',
            'telegram_id' => 'nullable|string|max:50',
            'partner_type' => 'required|in:individual,agency,influencer,integrator',
            'company_name' => 'nullable|string|max:150',
            'inn_stir' => 'nullable|string|max:30',
            'agreement_accepted' => 'accepted',
        ]);

        $partner = DB::transaction(function () use ($user, $validated) {
            $partner = Partner::create([
                'user_id' => $user->id,
                'full_name' => $validated['full_name'],
                'phone' => $validated['phone'],
                'telegram_id' => $validated['telegram_id'] ?? null,
                'partner_type' => $validated['partner_type'],
                'company_name' => $validated['company_name'] ?? null,
                'inn_stir' => $validated['inn_stir'] ?? null,
                'status' => Partner::STATUS_PENDING,
                'tier' => 'bronze',
                'agreement_signed_at' => now(),
                'agreement_version' => '1.0',
            ]);

            // Spatie Permission: 'partner' rolini tayinlash — bu middleware
            // orqali /partner/* sahifalariga kirish huquqini beradi.
            if (! $user->hasRole('partner')) {
                $user->assignRole('partner');
            }

            return $partner;
        });

        return redirect()->route('partner.dashboard')
            ->with('success', "Arizangiz qabul qilindi. Kod: {$partner->code}. Admin tasdiqlashini kuting yoki darhol havolangizdan foydalaning.");
    }
}
