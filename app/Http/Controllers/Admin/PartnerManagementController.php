<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Partner\Partner;
use App\Models\Partner\PartnerCommission;
use App\Models\Partner\PartnerPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PartnerManagementController extends Controller
{
    /**
     * GET /admin/partners
     */
    public function index(Request $request)
    {
        $query = Partner::with('user:id,name,email');

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($tier = $request->input('tier')) {
            $query->where('tier', $tier);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $partners = $query->latest()
            ->paginate(25)
            ->through(fn ($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'full_name' => $p->full_name,
                'email' => $p->user?->email,
                'status' => $p->status,
                'tier' => $p->tier,
                'partner_type' => $p->partner_type,
                'referrals_count' => $p->referrals_count_cached,
                'active_referrals' => $p->active_referrals_count_cached,
                'lifetime_earned' => (float) $p->lifetime_earned_cached,
                'available_balance' => (float) $p->available_balance_cached,
                'created_at' => $p->created_at?->format('d.m.Y'),
            ]);

        $stats = [
            'total_partners' => Partner::count(),
            'active_partners' => Partner::where('status', Partner::STATUS_ACTIVE)->count(),
            'pending_partners' => Partner::where('status', Partner::STATUS_PENDING)->count(),
            'total_payouts_pending' => (float) PartnerPayout::where('status', PartnerPayout::STATUS_PENDING)->sum('total_amount'),
            'total_commissions_paid' => (float) PartnerCommission::where('status', PartnerCommission::STATUS_PAID)->sum('commission_amount'),
        ];

        return Inertia::render('Admin/Partners/Index', [
            'partners' => $partners,
            'filters' => $request->only(['status', 'tier', 'search']),
            'stats' => $stats,
        ]);
    }

    /**
     * GET /admin/partners/{id}
     */
    public function show(string $id)
    {
        $partner = Partner::with('user:id,name,email')->findOrFail($id);

        $referrals = $partner->referrals()
            ->with('business:id,name')
            ->latest()
            ->limit(30)
            ->get();

        $commissions = $partner->commissions()
            ->with('business:id,name')
            ->latest()
            ->limit(30)
            ->get();

        $payouts = $partner->payouts()
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Partners/Show', [
            'partner' => $partner,
            'referrals' => $referrals,
            'commissions' => $commissions,
            'payouts' => $payouts,
        ]);
    }

    /**
     * PUT /admin/partners/{id}/status
     */
    public function updateStatus(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,active,suspended,terminated',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $partner = Partner::findOrFail($id);
        $partner->update($validated);

        return back()->with('success', 'Partner holati yangilandi.');
    }

    /**
     * PUT /admin/partners/{id}/tier
     */
    public function updateTier(Request $request, string $id)
    {
        $validated = $request->validate([
            'tier' => 'required|in:bronze,silver,gold,platinum',
            'custom_year_one_rate' => 'nullable|numeric|min:0|max:1',
            'custom_lifetime_rate' => 'nullable|numeric|min:0|max:1',
        ]);

        $partner = Partner::findOrFail($id);
        $partner->update($validated);

        return back()->with('success', 'Tier yangilandi.');
    }

    /**
     * GET /admin/partners/payouts
     */
    public function payoutsQueue(Request $request)
    {
        $query = PartnerPayout::with('partner:id,code,full_name,bank_name,bank_account');

        if ($status = $request->input('status', 'pending')) {
            $query->where('status', $status);
        }

        $payouts = $query->latest()
            ->paginate(25)
            ->through(fn ($p) => [
                'id' => $p->id,
                'partner_code' => $p->partner?->code,
                'partner_name' => $p->partner?->full_name,
                'bank_name' => $p->partner?->bank_name,
                'bank_account' => $p->partner?->bank_account,
                'total_amount' => (float) $p->total_amount,
                'commissions_count' => $p->commissions_count,
                'status' => $p->status,
                'payout_method' => $p->payout_method,
                'created_at' => $p->created_at?->format('d.m.Y H:i'),
            ]);

        return Inertia::render('Admin/Partners/Payouts', [
            'payouts' => $payouts,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * POST /admin/partners/payouts/{id}/approve
     */
    public function approvePayout(Request $request, string $id)
    {
        $payout = PartnerPayout::findOrFail($id);

        if ($payout->status !== PartnerPayout::STATUS_PENDING) {
            return back()->with('error', 'Faqat kutilayotgan payoutlarni tasdiqlash mumkin.');
        }

        $payout->update([
            'status' => PartnerPayout::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return back()->with('success', 'Payout tasdiqlandi.');
    }

    /**
     * POST /admin/partners/payouts/{id}/mark-paid
     */
    public function markPayoutPaid(Request $request, string $id)
    {
        $validated = $request->validate([
            'payout_reference' => 'required|string|max:100',
            'note' => 'nullable|string|max:500',
        ]);

        $payout = PartnerPayout::findOrFail($id);

        if (! in_array($payout->status, [PartnerPayout::STATUS_APPROVED, PartnerPayout::STATUS_PROCESSING])) {
            return back()->with('error', 'Payout avval tasdiqlanishi kerak.');
        }

        DB::transaction(function () use ($payout, $validated) {
            $payout->update([
                'status' => PartnerPayout::STATUS_PAID,
                'payout_reference' => $validated['payout_reference'],
                'note' => $validated['note'] ?? null,
                'paid_at' => now(),
            ]);

            // Bog'liq commissionlar ham 'paid' bo'ladi
            PartnerCommission::where('payout_id', $payout->id)
                ->update([
                    'status' => PartnerCommission::STATUS_PAID,
                    'paid_at' => now(),
                ]);
        });

        return back()->with('success', "Payout paid deb belgilandi. Ref: {$validated['payout_reference']}");
    }

    /**
     * POST /admin/partners/payouts/{id}/reject
     */
    public function rejectPayout(Request $request, string $id)
    {
        $validated = $request->validate([
            'failure_reason' => 'required|string|max:500',
        ]);

        $payout = PartnerPayout::findOrFail($id);

        DB::transaction(function () use ($payout, $validated) {
            $payout->update([
                'status' => PartnerPayout::STATUS_FAILED,
                'failure_reason' => $validated['failure_reason'],
            ]);

            // Commissionlarni 'available' holatiga qaytaradi, partner qayta so'rovni yuborishi mumkin
            $commissionIds = PartnerCommission::where('payout_id', $payout->id)->pluck('id');

            PartnerCommission::whereIn('id', $commissionIds)
                ->update(['payout_id' => null]);

            $total = (float) $payout->total_amount;
            $payout->partner->increment('available_balance_cached', $total);
        });

        return back()->with('success', 'Payout rad etildi. Commissionlar qayta available.');
    }
}
