<?php

namespace App\Services\Partner;

use App\Models\Billing\BillingTransaction;
use App\Models\Business;
use App\Models\Partner\Partner;
use App\Models\Partner\PartnerCommission;
use App\Models\Partner\PartnerPayout;
use App\Models\Partner\PartnerReferral;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PartnerCommissionService — commission lifecycle boshqaruvi.
 *
 * Responsibilities:
 *  - recordForBillingTransaction(): har to'lovdan commission yozuvi yaratadi
 *  - promoteMaturedCommissions(): pending 30+ kun → available (daily cron)
 *  - reverseForRefund(): mijoz qaytarim olganda commission teskari qilinadi
 *  - createPayout(): partner so'rovi bo'yicha available commissions → payout
 */
class PartnerCommissionService
{
    /**
     * Billing transaction uchun commission yozish.
     *
     * Idempotent — bir xil billing_transaction_id uchun 2-marta chaqirilsa,
     * yangi yozuv yaratmaydi.
     */
    public function recordForBillingTransaction(BillingTransaction $txn): ?PartnerCommission
    {
        // Idempotency — bitta billing_txn faqat 1 commissionga ega bo'la oladi
        $existing = PartnerCommission::where('billing_transaction_id', $txn->id)->first();
        if ($existing) {
            return $existing;
        }

        $business = $txn->business;
        if (! $business || ! $business->referral_partner_id) {
            return null; // Referral bo'lmagan mijoz — commission yo'q
        }

        $referral = PartnerReferral::where('partner_id', $business->referral_partner_id)
            ->where('business_id', $business->id)
            ->first();

        if (! $referral) {
            Log::info('PartnerCommission: referral record missing', [
                'business_id' => $business->id,
                'partner_id' => $business->referral_partner_id,
            ]);
            return null;
        }

        // Cancelled/disputed referrallar uchun commission yozilmaydi
        if (in_array($referral->status, [PartnerReferral::STATUS_CANCELLED, PartnerReferral::STATUS_DISPUTED])) {
            return null;
        }

        $partner = $referral->partner;
        if (! $partner || ! $partner->isActive()) {
            return null;
        }

        return DB::transaction(function () use ($txn, $partner, $referral, $business) {
            // Birinchi to'lov ekanini ANIQLASH: shu referral uchun
            // allaqachon reverse/clawback bo'lmagan commission bormi?
            $hasExistingCommission = PartnerCommission::where('referral_id', $referral->id)
                ->whereNotIn('status', [
                    PartnerCommission::STATUS_REVERSED,
                    PartnerCommission::STATUS_CLAWBACK,
                ])
                ->exists();

            $isFirstPayment = ! $hasExistingCommission;

            // Birinchi to'lov bo'lsa — attribution timestamp o'rnatiladi
            if ($referral->first_payment_at === null) {
                $referral->forceFill([
                    'status' => PartnerReferral::STATUS_ATTRIBUTED,
                    'attributed_at' => now(),
                    'first_payment_at' => now(),
                ])->save();
            } else if ($referral->status === PartnerReferral::STATUS_ATTRIBUTED) {
                $referral->update(['status' => PartnerReferral::STATUS_ACTIVE]);
            }

            // Stavka tanlash:
            //  - birinchi to'lov → yuqori stavka (masalan Bronze: 10%)
            //  - keyingi har bir to'lov → lifetime stavka (Bronze: 5%)
            $rate = $isFirstPayment
                ? $partner->getEffectiveFirstPaymentRate()
                : $partner->getEffectiveLifetimeRate();

            $gross = (float) $txn->amount;
            $commission = round($gross * $rate, 2);

            $record = PartnerCommission::create([
                'partner_id' => $partner->id,
                'referral_id' => $referral->id,
                'business_id' => $business->id,
                'subscription_id' => $txn->subscription_id ?? null,
                'billing_transaction_id' => $txn->id,
                'gross_amount' => $gross,
                'rate_applied' => $rate,
                'commission_amount' => $commission,
                'rate_type' => $isFirstPayment
                    ? PartnerCommission::RATE_TYPE_FIRST_PAYMENT
                    : PartnerCommission::RATE_TYPE_LIFETIME,
                'status' => PartnerCommission::STATUS_PENDING,
                'available_at' => now()->copy()->addDays(PartnerCommission::CLAWBACK_BUFFER_DAYS),
            ]);

            // Referralning lifetime earnings counter
            $referral->increment('lifetime_commission_earned', $commission);
            $partner->increment('lifetime_earned_cached', $commission);

            Log::info('PartnerCommission: recorded', [
                'partner_id' => $partner->id,
                'business_id' => $business->id,
                'amount' => $commission,
                'rate' => $rate,
                'rate_type' => $record->rate_type,
            ]);

            return $record;
        });
    }

    /**
     * Pending commissionlarni available holatiga o'tkazish (30 kun o'tgandan keyin).
     *
     * Daily cron tomonidan chaqiriladi: `php artisan partner:promote-commissions`
     */
    public function promoteMaturedCommissions(): int
    {
        $promoted = 0;

        PartnerCommission::readyForPromotion()
            ->chunkById(200, function ($batch) use (&$promoted) {
                foreach ($batch as $commission) {
                    DB::transaction(function () use ($commission, &$promoted) {
                        $commission->update(['status' => PartnerCommission::STATUS_AVAILABLE]);
                        $commission->partner->increment('available_balance_cached', $commission->commission_amount);
                        $promoted++;
                    });
                }
            });

        return $promoted;
    }

    /**
     * Refund bo'lganda commission teskari qilish.
     */
    public function reverseForRefund(BillingTransaction $txn, string $reason = 'refund'): int
    {
        $reversed = 0;

        PartnerCommission::where('billing_transaction_id', $txn->id)
            ->whereNotIn('status', [PartnerCommission::STATUS_PAID, PartnerCommission::STATUS_REVERSED])
            ->get()
            ->each(function ($commission) use ($reason, &$reversed) {
                DB::transaction(function () use ($commission, $reason, &$reversed) {
                    $wasAvailable = $commission->status === PartnerCommission::STATUS_AVAILABLE;

                    $commission->update([
                        'status' => PartnerCommission::STATUS_REVERSED,
                        'clawback_reason' => $reason,
                    ]);

                    // Cached counterlarni tuzatish
                    $commission->partner->decrement('lifetime_earned_cached', $commission->commission_amount);
                    if ($wasAvailable) {
                        $commission->partner->decrement('available_balance_cached', $commission->commission_amount);
                    }
                    $commission->referral?->decrement('lifetime_commission_earned', $commission->commission_amount);

                    $reversed++;
                });
            });

        return $reversed;
    }

    /**
     * Partner tomonidan so'ralgan payout yaratish — barcha AVAILABLE
     * commissionlarni bir payoutga birlashtiradi.
     *
     * @throws \RuntimeException agar summa minimumga yetmasa
     */
    public function requestPayout(Partner $partner): PartnerPayout
    {
        return DB::transaction(function () use ($partner) {
            $commissions = PartnerCommission::where('partner_id', $partner->id)
                ->where('status', PartnerCommission::STATUS_AVAILABLE)
                ->lockForUpdate()
                ->get();

            $total = (float) $commissions->sum('commission_amount');

            if ($total < PartnerPayout::MIN_PAYOUT_UZS) {
                throw new \RuntimeException(
                    "Minimum payout summasi " . number_format(PartnerPayout::MIN_PAYOUT_UZS, 0, '', ' ')
                    . " so'm. Joriy: " . number_format($total, 0, '', ' ') . " so'm."
                );
            }

            $payout = PartnerPayout::create([
                'partner_id' => $partner->id,
                'total_amount' => $total,
                'commissions_count' => $commissions->count(),
                'status' => PartnerPayout::STATUS_PENDING,
                'payout_method' => $partner->preferred_payout_method,
                'payout_details' => [
                    'bank_name' => $partner->bank_name,
                    'bank_account' => $partner->bank_account,
                    'inn_stir' => $partner->inn_stir,
                    'full_name' => $partner->full_name,
                    'company_name' => $partner->company_name,
                ],
            ]);

            // Commissionlarni ushbu payoutga bog'lash — hali 'paid' emas
            PartnerCommission::whereIn('id', $commissions->pluck('id'))
                ->update(['payout_id' => $payout->id]);

            // Partner cached balance tozalanadi
            $partner->decrement('available_balance_cached', $total);

            return $payout;
        });
    }
}
