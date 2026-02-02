<?php

namespace App\Services;

use App\Exceptions\IntegrationAbuseException;
use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\Integration;
use App\Models\Subscription;
use App\Models\TelegramBot;

class IntegrationGuardService
{
    /**
     * Instagram akkauntni ulashdan oldin tekshirish.
     *
     * Qoidalar:
     * - Topilmasa → OK
     * - O'sha biznesniki → OK
     * - Boshqa aktiv biznesdagi → BLOCK (already_connected)
     * - Eski trial biznesdagi VA joriy ham trial → BLOCK (trial_abuse)
     * - Eski trial biznesdagi VA joriy pullik → OK (ruxsat)
     *
     * @throws IntegrationAbuseException
     */
    public function checkInstagramAccount(string $instagramId, Business $business): void
    {
        $existingAccounts = InstagramAccount::where('instagram_id', $instagramId)
            ->with(['integration' => function ($q) {
                $q->withTrashed();
            }])
            ->get();

        if ($existingAccounts->isEmpty()) {
            return;
        }

        foreach ($existingAccounts as $account) {
            // O'sha biznesniki - OK
            if ($account->business_id === $business->id) {
                continue;
            }

            $integration = $account->integration;

            // Integratsiya hali aktiv (connected) va biznes boshqa
            if ($integration && !$integration->trashed() && $integration->status === 'connected') {
                $otherBusiness = Business::find($account->business_id);
                throw new IntegrationAbuseException(
                    $instagramId,
                    $otherBusiness?->name,
                    'already_connected'
                );
            }

            // Eski/o'chirilgan integratsiya - trial abuse tekshiruvi
            if ($this->wasPreviouslyTrialBusiness($account->business_id)) {
                if ($this->isTrialBusiness($business)) {
                    $otherBusiness = Business::find($account->business_id);
                    throw new IntegrationAbuseException(
                        $instagramId,
                        $otherBusiness?->name,
                        'trial_abuse'
                    );
                }
                // Pullik biznes - ruxsat
            }
        }
    }

    /**
     * Telegram botni ulashdan oldin tekshirish.
     *
     * @throws IntegrationAbuseException
     */
    public function checkTelegramBot(string $botUsername, Business $business): void
    {
        $existingBots = TelegramBot::where('bot_username', $botUsername)->get();

        if ($existingBots->isEmpty()) {
            return;
        }

        foreach ($existingBots as $bot) {
            // O'sha biznesniki - OK
            if ($bot->business_id === $business->id) {
                continue;
            }

            // Bot hali aktiv va boshqa biznesdagi
            if ($bot->is_active) {
                $otherBusiness = Business::find($bot->business_id);
                throw new IntegrationAbuseException(
                    $botUsername,
                    $otherBusiness?->name,
                    'already_connected'
                );
            }

            // Eski/noaktiv bot - trial abuse tekshiruvi
            if ($this->wasPreviouslyTrialBusiness($bot->business_id)) {
                if ($this->isTrialBusiness($business)) {
                    $otherBusiness = Business::find($bot->business_id);
                    throw new IntegrationAbuseException(
                        $botUsername,
                        $otherBusiness?->name,
                        'trial_abuse'
                    );
                }
                // Pullik biznes - ruxsat
            }
        }
    }

    /**
     * Joriy biznes trial da ekanligini tekshirish.
     */
    protected function isTrialBusiness(Business $business): bool
    {
        return Subscription::where('business_id', $business->id)
            ->where('status', 'trialing')
            ->whereHas('plan', fn ($q) => $q->where('slug', 'trial-pack'))
            ->exists();
    }

    /**
     * Biznes avval trial ishlatganligini tekshirish.
     */
    protected function wasPreviouslyTrialBusiness(string $businessId): bool
    {
        return Subscription::withTrashed()
            ->where('business_id', $businessId)
            ->whereHas('plan', fn ($q) => $q->where('slug', 'trial-pack'))
            ->exists();
    }
}
