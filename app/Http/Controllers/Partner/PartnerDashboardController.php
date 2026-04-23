<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use App\Models\Partner\Partner;
use App\Models\Partner\PartnerCommission;
use App\Models\Partner\PartnerPayout;
use App\Models\Partner\PartnerReferral;
use App\Models\Partner\PartnerTierRule;
use App\Services\Partner\PartnerCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PartnerDashboardController extends Controller
{
    public function __construct(
        protected PartnerCommissionService $commissionService,
        protected \App\Services\Partner\PartnerReferralTracker $referralTracker
    ) {}

    /**
     * Partnerni joriy user'dan topadi — yoki null.
     */
    protected function currentPartner(Request $request): ?Partner
    {
        return Partner::where('user_id', $request->user()->id)->first();
    }

    /**
     * GET /partner — dashboard overview
     */
    public function dashboard(Request $request)
    {
        $partner = $this->currentPartner($request);

        if (! $partner) {
            return Inertia::render('Partner/Apply');
        }

        // KPI totals
        $kpi = [
            'lifetime_earned' => (float) $partner->lifetime_earned_cached,
            'available_balance' => (float) $partner->available_balance_cached,
            'pending_balance' => (float) PartnerCommission::where('partner_id', $partner->id)
                ->where('status', PartnerCommission::STATUS_PENDING)
                ->sum('commission_amount'),
            'paid_total' => (float) PartnerCommission::where('partner_id', $partner->id)
                ->where('status', PartnerCommission::STATUS_PAID)
                ->sum('commission_amount'),
            'referrals_count' => (int) $partner->referrals_count_cached,
            'active_referrals' => (int) PartnerReferral::where('partner_id', $partner->id)
                ->whereIn('status', [PartnerReferral::STATUS_ATTRIBUTED, PartnerReferral::STATUS_ACTIVE])
                ->count(),
            'this_month' => (float) PartnerCommission::where('partner_id', $partner->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission_amount'),
        ];

        // Earnings chart (oxirgi 12 oy)
        $chart = DB::table('partner_commissions')
            ->where('partner_id', $partner->id)
            ->where('created_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(commission_amount) AS total")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Recent referrals (10 ta)
        $recentReferrals = PartnerReferral::where('partner_id', $partner->id)
            ->with('business:id,name,created_at')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'business_name' => $r->business?->name ?? '—',
                'status' => $r->status,
                'via' => $r->referred_via,
                'attributed_at' => $r->attributed_at?->format('d.m.Y'),
                'lifetime_earned' => (float) $r->lifetime_commission_earned,
            ]);

        // Tier info
        $tierRule = PartnerTierRule::where('tier', $partner->tier)->first();
        $allTiers = PartnerTierRule::ordered();

        return Inertia::render('Partner/Dashboard', [
            'partner' => [
                'id' => $partner->id,
                'code' => $partner->code,
                'referral_link' => $partner->getReferralLink(),
                'status' => $partner->status,
                'tier' => $partner->tier,
                'tier_name' => $tierRule?->name,
                'tier_icon' => $tierRule?->icon,
                'first_payment_rate' => $partner->getEffectiveFirstPaymentRate(),
                'lifetime_rate' => $partner->getEffectiveLifetimeRate(),
                'full_name' => $partner->full_name,
                'partner_type' => $partner->partner_type,
            ],
            'kpi' => $kpi,
            'chart' => $chart,
            'recent_referrals' => $recentReferrals,
            'tiers' => $allTiers->map(fn ($t) => [
                'tier' => $t->tier,
                'name' => $t->name,
                'icon' => $t->icon,
                'first_payment_rate' => (float) $t->year_one_rate,
                'lifetime_rate' => (float) $t->lifetime_rate,
                'min_active_referrals' => $t->min_active_referrals,
                'min_monthly_volume_uzs' => (float) $t->min_monthly_volume_uzs,
                'perks' => $t->perks ?? [],
                'is_current' => $t->tier === $partner->tier,
            ]),
            'min_payout' => PartnerPayout::MIN_PAYOUT_UZS,
        ]);
    }

    /**
     * GET /partner/referrals
     */
    public function referrals(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        $referrals = PartnerReferral::where('partner_id', $partner->id)
            ->with('business:id,name,created_at,status')
            ->latest()
            ->paginate(25)
            ->through(fn ($r) => [
                'id' => $r->id,
                'business_name' => $r->business?->name ?? '—',
                'business_status' => $r->business?->status,
                'status' => $r->status,
                'referred_via' => $r->referred_via,
                'utm_source' => $r->utm_source,
                'attributed_at' => $r->attributed_at?->format('d.m.Y H:i'),
                'first_payment_at' => $r->first_payment_at?->format('d.m.Y'),
                'lifetime_earned' => (float) $r->lifetime_commission_earned,
                'created_at' => $r->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('Partner/Referrals', [
            'partner_code' => $partner->code,
            'referral_link' => $partner->getReferralLink(),
            'referrals' => $referrals,
        ]);
    }

    /**
     * POST /partner/referrals/invite — partner to'g'ridan-to'g'ri mijoz yaratadi.
     *
     * Rate-limit: 10 client/kun/partner (fraud himoyasi).
     */
    public function inviteReferral(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        if (! $partner->isActive()) {
            return back()->with('error', "Partner hisobingiz hali active emas.");
        }

        // Rate limit — kunlik 10 ta
        $todayCount = \App\Models\Partner\PartnerReferral::where('partner_id', $partner->id)
            ->where('referred_via', 'manual')
            ->whereDate('created_at', now())
            ->count();

        if ($todayCount >= 10) {
            return back()->with('error', "Kunlik limit to'ldi (10 ta). Ertaga qayta urinib ko'ring.");
        }

        $validated = $request->validate([
            'full_name' => 'required|string|min:2|max:150',
            'email' => 'nullable|email|max:255',
            'phone' => 'required|string|max:30',
            'password' => 'nullable|string|min:8|max:100',
            'business_name' => 'required|string|min:2|max:255',
            'category' => 'required|string|max:100',
            'region' => 'nullable|string|max:100',
        ], [
            'full_name.required' => "Mijoz ismini kiriting",
            'phone.required' => "Telefon raqam majburiy",
            'password.min' => "Parol kamida 8 ta belgidan iborat bo'lishi kerak",
            'business_name.required' => "Biznes nomini kiriting",
            'category.required' => "Biznes kategoriyasini tanlang",
        ]);

        // Kamida email yoki phone bo'lishi kerak
        if (empty($validated['email']) && empty($validated['phone'])) {
            return back()->withErrors(['email' => 'Email yoki telefon kiritilishi kerak']);
        }

        try {
            $result = $this->referralTracker->inviteClient($partner, $validated);

            // Flash temp_password session'ga — Inertia response'ga qaytariladi (bir marta ko'rsatiladi)
            return back()->with([
                'invite_success' => [
                    'login' => $result['user']->login,
                    'email' => $result['user']->email,
                    'phone' => $result['user']->phone,
                    'temp_password' => $result['temp_password'],
                    'business_name' => $result['business']->name,
                    'login_url' => route('login'),
                ],
            ]);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            \Log::error('Partner invite failed', [
                'partner_id' => $partner->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', "Xatolik yuz berdi. Qaytadan urinib ko'ring.");
        }
    }

    /**
     * GET /partner/commissions
     */
    public function commissions(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        $query = PartnerCommission::where('partner_id', $partner->id)
            ->with('business:id,name', 'billingTransaction:id,amount,paid_at');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $commissions = $query->latest()
            ->paginate(25)
            ->through(fn ($c) => [
                'id' => $c->id,
                'business_name' => $c->business?->name ?? '—',
                'gross_amount' => (float) $c->gross_amount,
                'rate_applied' => (float) $c->rate_applied,
                'rate_type' => $c->rate_type,
                'commission_amount' => (float) $c->commission_amount,
                'status' => $c->status,
                'available_at' => $c->available_at?->format('d.m.Y'),
                'paid_at' => $c->paid_at?->format('d.m.Y'),
                'created_at' => $c->created_at?->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Partner/Commissions', [
            'commissions' => $commissions,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * GET /partner/payouts
     */
    public function payouts(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        $payouts = PartnerPayout::where('partner_id', $partner->id)
            ->latest()
            ->paginate(20)
            ->through(fn ($p) => [
                'id' => $p->id,
                'total_amount' => (float) $p->total_amount,
                'commissions_count' => $p->commissions_count,
                'status' => $p->status,
                'payout_method' => $p->payout_method,
                'payout_reference' => $p->payout_reference,
                'requested_at' => $p->created_at?->format('d.m.Y H:i'),
                'approved_at' => $p->approved_at?->format('d.m.Y H:i'),
                'paid_at' => $p->paid_at?->format('d.m.Y H:i'),
                'failure_reason' => $p->failure_reason,
            ]);

        return Inertia::render('Partner/Payouts', [
            'payouts' => $payouts,
            'available_balance' => (float) $partner->available_balance_cached,
            'min_payout' => PartnerPayout::MIN_PAYOUT_UZS,
            'payout_method' => $partner->preferred_payout_method,
            'bank_configured' => ! empty($partner->bank_account),
        ]);
    }

    /**
     * POST /partner/payouts/request
     */
    public function requestPayout(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        if (empty($partner->bank_account)) {
            return back()->with('error', "Avval bank ma'lumotlaringizni to'ldiring.");
        }

        try {
            $payout = $this->commissionService->requestPayout($partner);
            return back()->with('success',
                "Payout so'rovi yuborildi. Summa: " . number_format($payout->total_amount, 0, '', ' ') . " so'm."
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * GET /partner/settings
     */
    public function settings(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        return Inertia::render('Partner/Settings', [
            'partner' => [
                'id' => $partner->id,
                'code' => $partner->code,
                'full_name' => $partner->full_name,
                'phone' => $partner->phone,
                'telegram_id' => $partner->telegram_id,
                'company_name' => $partner->company_name,
                'inn_stir' => $partner->inn_stir,
                'bank_name' => $partner->bank_name,
                'bank_account' => $partner->bank_account,
                'preferred_payout_method' => $partner->preferred_payout_method,
                'partner_type' => $partner->partner_type,
            ],
        ]);
    }

    /**
     * PUT /partner/settings
     */
    public function updateSettings(Request $request)
    {
        $partner = $this->currentPartner($request);
        abort_unless($partner, 403);

        $validated = $request->validate([
            'full_name' => 'required|string|max:150',
            'phone' => 'nullable|string|max:30',
            'telegram_id' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:150',
            'inn_stir' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:100',
            'bank_account' => 'nullable|string|max:50',
            'preferred_payout_method' => 'required|in:bank_transfer,humo,uzcard,payme,click,cash',
        ]);

        $partner->update($validated);

        return back()->with('success', "Ma'lumotlar saqlandi.");
    }
}
