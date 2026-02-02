<?php

namespace App\Services;

use App\Exceptions\FeatureNotAvailableException;
use App\Exceptions\NoActiveSubscriptionException;
use App\Exceptions\QuotaExceededException;
use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

/**
 * SubscriptionGate - Markazlashtirilgan tarif tekshirish servisi
 *
 * Bu servis barcha limit va feature tekshirishlarini bir joyda boshqaradi.
 * Admin panelda o'zgartirilgan JSON sozlamalari kodda 100% ishlashini ta'minlaydi.
 *
 * Ishlatilishi:
 * - Middleware orqali: Route::middleware('feature:hr_tasks')
 * - Service orqali: app(SubscriptionGate::class)->checkFeature($business, 'hr_tasks')
 * - Facade orqali: SubscriptionGate::checkQuota($business, 'users')
 */
class SubscriptionGate
{
    /**
     * Limit konfiguratsiasi - hamma limitlar shu yerda aniqlanadi
     * Bu konfiguratsia Admin Panel bilan sinxronlashtirilgan
     */
    protected array $limitConfig = [
        'users' => [
            'label' => 'Foydalanuvchilar soni',
            'method' => 'getUsersCount',
            'icon' => 'users',
            'suffix' => 'ta',
        ],
        'branches' => [
            'label' => 'Filiallar soni',
            'method' => 'getBranchesCount',
            'icon' => 'building',
            'suffix' => 'ta',
        ],
        'instagram_accounts' => [
            'label' => 'Instagram akkauntlar',
            'method' => 'getInstagramAccountsCount',
            'icon' => 'instagram',
            'suffix' => 'ta',
        ],
        'monthly_leads' => [
            'label' => 'Oylik lidlar',
            'method' => 'getMonthlyLeadsCount',
            'icon' => 'user-plus',
            'suffix' => 'ta',
        ],
        'ai_call_minutes' => [
            'label' => 'Qo\'ng\'iroqlar AI tahlili',
            'method' => 'getAiCallMinutesUsed',
            'icon' => 'phone',
            'suffix' => 'daq',
        ],
        'chatbot_channels' => [
            'label' => 'Chatbot kanallari',
            'method' => 'getChatbotChannelsCount',
            'icon' => 'chat',
            'suffix' => 'ta',
        ],
        'telegram_bots' => [
            'label' => 'Telegram botlar',
            'method' => 'getTelegramBotsCount',
            'icon' => 'telegram',
            'suffix' => 'ta',
        ],
        'ai_requests' => [
            'label' => 'AI so\'rovlar',
            'method' => 'getAiRequestsCount',
            'icon' => 'sparkles',
            'suffix' => 'ta',
        ],
        'storage_mb' => [
            'label' => 'Saqlash hajmi',
            'method' => 'getStorageUsedMb',
            'icon' => 'database',
            'suffix' => 'MB',
        ],
    ];

    /**
     * Feature konfiguratsiasi - Admin Panel bilan sinxronlashtirilgan
     */
    protected array $featureConfig = [
        'hr_tasks' => [
            'label' => 'HR vazifalar',
            'description' => 'Vazifalar va loyihalar boshqaruvi',
        ],
        'hr_bot' => [
            'label' => 'Ishga olish boti',
            'description' => 'Avtomatlashtirilgan HR chatbot',
        ],
        'anti_fraud' => [
            'label' => 'SMS ogohlantirish',
            'description' => 'Fraud aniqlash va ogohlantirish',
        ],
    ];

    // ==================== CORE GATE METHODS ====================

    /**
     * Feature mavjudligini tekshirish.
     * Agar ruxsat bo'lmasa FeatureNotAvailableException otadi.
     *
     * @throws NoActiveSubscriptionException
     * @throws FeatureNotAvailableException
     */
    public function checkFeature(Business $business, string $featureKey): void
    {
        $plan = $this->getActivePlan($business);

        if (!$plan->hasFeature($featureKey)) {
            $config = $this->featureConfig[$featureKey] ?? null;
            $label = $config['label'] ?? $featureKey;

            throw new FeatureNotAvailableException($featureKey, $label);
        }
    }

    /**
     * Quota limitini tekshirish.
     * Agar limit to'lgan bo'lsa QuotaExceededException otadi.
     *
     * @param Business $business
     * @param string $limitKey
     * @param int|null $currentCount - Agar berilmasa avtomatik hisoblanadi
     * @param int $addCount - Qo'shilmoqchi bo'lgan miqdor (default: 1)
     *
     * @throws NoActiveSubscriptionException
     * @throws QuotaExceededException
     */
    public function checkQuota(
        Business $business,
        string $limitKey,
        ?int $currentCount = null,
        int $addCount = 1
    ): void {
        $plan = $this->getActivePlan($business);
        $limit = $this->getPlanLimit($plan, $limitKey);

        // -1 yoki null = cheksiz
        if ($limit === -1 || $limit === null) {
            return;
        }

        // Joriy foydalanishni hisoblash
        $currentUsage = $currentCount ?? $this->getCurrentUsage($business, $limitKey);

        // Qo'shilgandan keyin limitdan oshib ketadimi?
        if (($currentUsage + $addCount) > $limit) {
            $config = $this->limitConfig[$limitKey] ?? null;
            $label = $config['label'] ?? $limitKey;

            throw new QuotaExceededException($limitKey, $label, $limit, $currentUsage);
        }
    }

    /**
     * Limit qiymatini olish.
     * -1 qaytarsa cheksiz degan ma'no.
     *
     * @throws NoActiveSubscriptionException
     */
    public function getLimit(Business $business, string $limitKey): int
    {
        $plan = $this->getActivePlan($business);
        $limit = $this->getPlanLimit($plan, $limitKey);

        return $limit ?? -1;
    }

    /**
     * Feature yoqilganmi tekshirish (exception otmaydi).
     */
    public function hasFeature(Business $business, string $featureKey): bool
    {
        try {
            $plan = $this->getActivePlan($business);
            return $plan->hasFeature($featureKey);
        } catch (NoActiveSubscriptionException $e) {
            return false;
        }
    }

    /**
     * Limit to'lganmi tekshirish (exception otmaydi).
     */
    public function hasReachedLimit(Business $business, string $limitKey): bool
    {
        try {
            $this->checkQuota($business, $limitKey, null, 1);
            return false;
        } catch (QuotaExceededException $e) {
            return true;
        } catch (NoActiveSubscriptionException $e) {
            return true;
        }
    }

    /**
     * Qo'shish mumkinmi tekshirish (exception otmaydi).
     */
    public function canAdd(Business $business, string $limitKey, int $count = 1): bool
    {
        try {
            $this->checkQuota($business, $limitKey, null, $count);
            return true;
        } catch (QuotaExceededException|NoActiveSubscriptionException $e) {
            return false;
        }
    }

    // ==================== PLAN & SUBSCRIPTION HELPERS ====================

    /**
     * Aktiv obunani olish.
     *
     * @throws NoActiveSubscriptionException
     */
    public function getActiveSubscription(Business $business): Subscription
    {
        $subscription = $business->subscriptions()
            ->with('plan')
            ->whereIn('status', ['active', 'trialing'])
            ->where(function ($query) {
                $query->whereDate('ends_at', '>=', now())
                    ->orWhere(function ($q) {
                        $q->where('status', 'trialing')
                            ->whereDate('trial_ends_at', '>=', now());
                    });
            })
            ->first();

        if (!$subscription) {
            throw new NoActiveSubscriptionException();
        }

        return $subscription;
    }

    /**
     * Aktiv planini olish.
     *
     * @throws NoActiveSubscriptionException
     */
    public function getActivePlan(Business $business): Plan
    {
        $subscription = $this->getActiveSubscription($business);

        if (!$subscription->plan) {
            throw new NoActiveSubscriptionException();
        }

        return $subscription->plan;
    }

    /**
     * Plan dan limit qiymatini olish.
     */
    protected function getPlanLimit(Plan $plan, string $limitKey): ?int
    {
        $limits = $plan->limits ?? [];
        $value = $limits[$limitKey] ?? null;

        return $value !== null ? (int) $value : null;
    }

    // ==================== USAGE STATISTICS ====================

    /**
     * Joriy foydalanishni hisoblash.
     */
    public function getCurrentUsage(Business $business, string $limitKey): int
    {
        $config = $this->limitConfig[$limitKey] ?? null;

        if (!$config || !isset($config['method'])) {
            return 0;
        }

        $method = $config['method'];

        if (method_exists($this, $method)) {
            return $this->$method($business);
        }

        return 0;
    }

    /**
     * Qolgan kvotani hisoblash.
     * null qaytarsa cheksiz.
     */
    public function getRemainingQuota(Business $business, string $limitKey): ?int
    {
        try {
            $limit = $this->getLimit($business, $limitKey);

            // Cheksiz
            if ($limit === -1) {
                return null;
            }

            $currentUsage = $this->getCurrentUsage($business, $limitKey);
            return max(0, $limit - $currentUsage);
        } catch (NoActiveSubscriptionException $e) {
            return 0;
        }
    }

    /**
     * Barcha limitlar va foydalanish statistikasini olish.
     * Frontend uchun optimallashtirilgan format.
     */
    public function getUsageStats(Business $business): array
    {
        try {
            $plan = $this->getActivePlan($business);
        } catch (NoActiveSubscriptionException $e) {
            return [];
        }

        $stats = [];

        foreach ($this->limitConfig as $key => $config) {
            $limit = $this->getPlanLimit($plan, $key);
            $current = $this->getCurrentUsage($business, $key);
            $isUnlimited = $limit === -1 || $limit === null;

            $stats[$key] = [
                'label' => $config['label'],
                'icon' => $config['icon'] ?? null,
                'suffix' => $config['suffix'] ?? '',
                'current' => $current,
                'limit' => $isUnlimited ? -1 : $limit,
                'limit_display' => $isUnlimited ? 'Cheksiz' : $limit,
                'remaining' => $isUnlimited ? null : max(0, $limit - $current),
                'percentage' => (!$isUnlimited && $limit > 0) ? min(100, round(($current / $limit) * 100)) : 0,
                'is_unlimited' => $isUnlimited,
                'is_exceeded' => !$isUnlimited && $current >= $limit,
                'is_warning' => !$isUnlimited && $limit > 0 && ($current / $limit) >= 0.8,
            ];
        }

        return $stats;
    }

    /**
     * Barcha yoqilgan xususiyatlarni olish.
     * Frontend uchun optimallashtirilgan format.
     */
    public function getEnabledFeatures(Business $business): array
    {
        try {
            $plan = $this->getActivePlan($business);
        } catch (NoActiveSubscriptionException $e) {
            return [];
        }

        $features = [];

        foreach ($this->featureConfig as $key => $config) {
            $features[$key] = [
                'label' => $config['label'],
                'description' => $config['description'] ?? '',
                'enabled' => $plan->hasFeature($key),
            ];
        }

        return $features;
    }

    /**
     * Tarif pastlashtirishga ruxsat bormi tekshirish.
     */
    public function canDowngradeToPlan(Business $business, Plan $newPlan): array
    {
        $issues = [];

        foreach ($this->limitConfig as $key => $config) {
            $newLimit = $this->getPlanLimit($newPlan, $key);
            $currentUsage = $this->getCurrentUsage($business, $key);

            // Cheksiz limitlarni o'tkazib yuborish
            if ($newLimit === -1 || $newLimit === null) {
                continue;
            }

            if ($currentUsage > $newLimit) {
                $issues[] = [
                    'key' => $key,
                    'label' => $config['label'],
                    'current' => $currentUsage,
                    'new_limit' => $newLimit,
                    'message' => "{$config['label']} soni ({$currentUsage}) yangi limitdan ({$newLimit}) oshib ketgan",
                ];
            }
        }

        return [
            'can_downgrade' => empty($issues),
            'issues' => $issues,
            'message' => empty($issues)
                ? null
                : 'Downgrade uchun quyidagi limitlarni kamaytiring: ' . collect($issues)->pluck('message')->join(', '),
        ];
    }

    // ==================== USAGE COUNT METHODS ====================

    /**
     * Foydalanuvchilar soni (owner + team members).
     */
    protected function getUsersCount(Business $business): int
    {
        return $business->teamMembers()->count() + 1; // +1 for owner
    }

    /**
     * Filiallar soni.
     */
    protected function getBranchesCount(Business $business): int
    {
        // TODO: Filiallar jadvali qo'shilganda yangilash
        return 1;
    }

    /**
     * Instagram akkauntlar soni.
     */
    protected function getInstagramAccountsCount(Business $business): int
    {
        return $business->instagramAccounts()->count();
    }

    /**
     * Joriy oydagi lidlar soni.
     */
    protected function getMonthlyLeadsCount(Business $business): int
    {
        return $business->leads()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Joriy oydagi AI qo'ng'iroq tahlili daqiqalari.
     */
    protected function getAiCallMinutesUsed(Business $business): int
    {
        // CallAnalysis modelidan hisoblash
        // TODO: usage_tracking jadvali qo'shilganda yangilash
        return (int) Cache::remember(
            "business_{$business->id}_ai_call_minutes_" . now()->format('Y_m'),
            3600,
            fn () => 0
        );
    }

    /**
     * Chatbot kanallari soni.
     */
    protected function getChatbotChannelsCount(Business $business): int
    {
        return $business->chatbotConfigs()->count();
    }

    /**
     * Telegram botlar soni.
     */
    protected function getTelegramBotsCount(Business $business): int
    {
        return $business->chatbotConfigs()
            ->where('platform', 'telegram')
            ->count();
    }

    /**
     * Joriy oydagi AI so'rovlar soni.
     */
    protected function getAiRequestsCount(Business $business): int
    {
        // TODO: ai_usage_logs jadvali qo'shilganda yangilash
        return (int) Cache::remember(
            "business_{$business->id}_ai_requests_" . now()->format('Y_m'),
            3600,
            fn () => 0
        );
    }

    /**
     * Ishlatilgan saqlash hajmi (MB).
     */
    protected function getStorageUsedMb(Business $business): int
    {
        // TODO: media jadvali orqali hisoblash
        return 0;
    }

    // ==================== CONFIG GETTERS ====================

    public function getLimitConfig(): array
    {
        return $this->limitConfig;
    }

    public function getFeatureConfig(): array
    {
        return $this->featureConfig;
    }

    public function getLimitKeys(): array
    {
        return array_keys($this->limitConfig);
    }

    public function getFeatureKeys(): array
    {
        return array_keys($this->featureConfig);
    }
}
