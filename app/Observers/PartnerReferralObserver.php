<?php

namespace App\Observers;

use App\Models\Partner\PartnerReferral;

/**
 * PartnerReferral observer — partnerning cached counterlari realtime yangilanadi.
 *
 *  - referrals_count_cached          — jami referral (pending + active + churned)
 *  - active_referrals_count_cached   — faqat ACTIVE/ATTRIBUTED holatdagilar
 */
class PartnerReferralObserver
{
    public function created(PartnerReferral $referral): void
    {
        $this->recount($referral);
    }

    public function updated(PartnerReferral $referral): void
    {
        // Status o'zgargan bo'lsa faqat qayta hisoblaymiz
        if ($referral->wasChanged('status')) {
            $this->recount($referral);
        }
    }

    public function deleted(PartnerReferral $referral): void
    {
        $this->recount($referral);
    }

    protected function recount(PartnerReferral $referral): void
    {
        $partner = $referral->partner;
        if (! $partner) {
            return;
        }

        $partner->referrals_count_cached = PartnerReferral::where('partner_id', $partner->id)->count();
        $partner->active_referrals_count_cached = PartnerReferral::where('partner_id', $partner->id)
            ->whereIn('status', [
                PartnerReferral::STATUS_ATTRIBUTED,
                PartnerReferral::STATUS_ACTIVE,
            ])
            ->count();
        $partner->saveQuietly();
    }
}
