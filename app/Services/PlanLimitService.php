<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

class PlanLimitService
{
    /**
     * Limit konfiguratsiasi - hamma limitlar shu yerda aniqlanadi
     */
    protected array $limitConfig = [
        'users' => [
            'label' => 'Foydalanuvchilar soni',
            'method' => 'getUsersCount',
            'unlimited_value' => -1,
            'legacy_column' => 'team_member_limit',
        ],
        'team_members' => [
            'label' => 'Team member limit',
            'method' => 'getTeamMembersCount',
            'unlimited_value' => -1,
            'legacy_column' => 'team_member_limit',
        ],
        'branches' => [
            'label' => 'Filiallar soni',
            'method' => 'getBranchesCount',
            'unlimited_value' => -1,
        ],
        'instagram_accounts' => [
            'label' => 'Instagram akkauntlar',
            'method' => 'getInstagramAccountsCount',
            'unlimited_value' => -1,
        ],
        'monthly_leads' => [
            'label' => 'Oylik lidlar',
            'method' => 'getMonthlyLeadsCount',
            'unlimited_value' => -1,
            'legacy_column' => 'lead_limit',
        ],
        'ai_call_minutes' => [
            'label' => 'Qo\'ng\'iroqlar AI tahlili',
            'method' => 'getAiCallMinutesUsed',
            'unlimited_value' => -1,
            'legacy_column' => 'audio_minutes_limit',
        ],
        'chatbot_channels' => [
            'label' => 'Chatbot kanallari',
            'method' => 'getChatbotChannelsCount',
            'unlimited_value' => -1,
            'legacy_column' => 'chatbot_channel_limit',
        ],
        'telegram_bots' => [
            'label' => 'Telegram botlar',
            'method' => 'getTelegramBotsCount',
            'unlimited_value' => -1,
            'legacy_column' => 'telegram_bot_limit',
        ],
        'ai_requests' => [
            'label' => 'AI so\'rovlar',
            'method' => 'getAiRequestsCount',
            'unlimited_value' => -1,
            'legacy_column' => 'ai_requests_limit',
        ],
        'storage_mb' => [
            'label' => 'Saqlash hajmi',
            'method' => 'getStorageUsedMb',
            'unlimited_value' => -1,
            'legacy_column' => 'storage_limit_mb',
        ],
    ];

    /**
     * Feature konfiguratsiasi
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

    /**
     * Get the active subscription for a business.
     */
    public function getActiveSubscription(Business $business): ?Subscription
    {
        return $business->subscriptions()
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
    }

    /**
     * Get the current plan for a business.
     */
    public function getCurrentPlan(Business $business): ?Plan
    {
        $subscription = $this->getActiveSubscription($business);
        return $subscription?->plan;
    }

    /**
     * Get a specific limit value from the plan.
     * Checks JSON-based limits first, then falls back to legacy columns.
     */
    public function getPlanLimit(Plan $plan, string $limitKey): ?int
    {
        // First check JSON-based limits
        $limits = $plan->limits ?? [];
        if (isset($limits[$limitKey])) {
            return (int) $limits[$limitKey];
        }

        // Fallback to legacy column if configured
        $config = $this->limitConfig[$limitKey] ?? null;
        if ($config && isset($config['legacy_column'])) {
            $legacyColumn = $config['legacy_column'];
            // Use getAttribute instead of isset() for Eloquent compatibility
            $value = $plan->getAttribute($legacyColumn);
            if ($value !== null) {
                return (int) $value;
            }
        }

        return null;
    }

    /**
     * Check if a specific limit has been reached.
     */
    public function hasReachedLimit(Business $business, string $limitKey): bool
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return true; // No plan = all limits reached
        }

        $limit = $this->getPlanLimit($plan, $limitKey);

        // -1 or null means unlimited
        if ($limit === null || $limit === -1) {
            return false;
        }

        $currentUsage = $this->getCurrentUsage($business, $limitKey);

        return $currentUsage >= $limit;
    }

    /**
     * Check if adding more items would exceed the limit.
     */
    public function canAdd(Business $business, string $limitKey, int $count = 1): bool
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return false;
        }

        $limit = $this->getPlanLimit($plan, $limitKey);

        // -1 or null means unlimited
        if ($limit === null || $limit === -1) {
            return true;
        }

        $currentUsage = $this->getCurrentUsage($business, $limitKey);

        return ($currentUsage + $count) <= $limit;
    }

    /**
     * Get current usage for a specific limit.
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
     * Check if a feature is enabled for the business.
     */
    public function hasFeature(Business $business, string $featureKey): bool
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return false;
        }

        return $plan->hasFeature($featureKey);
    }

    /**
     * Get all usage stats for a business.
     */
    public function getUsageStats(Business $business): array
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return [];
        }

        $stats = [];

        foreach ($this->limitConfig as $key => $config) {
            $limit = $this->getPlanLimit($plan, $key);
            $current = $this->getCurrentUsage($business, $key);

            $stats[$key] = [
                'label' => $config['label'],
                'current' => $current,
                'limit' => $limit === -1 || $limit === null ? 'Cheksiz' : $limit,
                'percentage' => ($limit && $limit > 0) ? min(100, round(($current / $limit) * 100)) : 0,
                'is_unlimited' => $limit === -1 || $limit === null,
                'is_exceeded' => $limit !== -1 && $limit !== null && $current >= $limit,
            ];
        }

        return $stats;
    }

    /**
     * Get all enabled features for a business.
     */
    public function getEnabledFeatures(Business $business): array
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return [];
        }

        $features = [];

        foreach ($this->featureConfig as $key => $config) {
            $features[$key] = [
                'label' => $config['label'],
                'description' => $config['description'],
                'enabled' => $plan->hasFeature($key),
            ];
        }

        return $features;
    }

    /**
     * Check if business can downgrade to a plan.
     */
    public function canDowngradeToPlan(Business $business, Plan $newPlan): array
    {
        $issues = [];

        foreach ($this->limitConfig as $key => $config) {
            $newLimit = $this->getPlanLimit($newPlan, $key);
            $currentUsage = $this->getCurrentUsage($business, $key);

            // Skip unlimited limits
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

    /**
     * Get remaining quota for a limit.
     */
    public function getRemainingQuota(Business $business, string $limitKey): ?int
    {
        $plan = $this->getCurrentPlan($business);

        if (!$plan) {
            return 0;
        }

        $limit = $this->getPlanLimit($plan, $limitKey);

        // Unlimited
        if ($limit === -1 || $limit === null) {
            return null; // null means unlimited
        }

        $currentUsage = $this->getCurrentUsage($business, $limitKey);

        return max(0, $limit - $currentUsage);
    }

    // ==================== USAGE COUNT METHODS ====================

    /**
     * Get users count for the business.
     */
    protected function getUsersCount(Business $business): int
    {
        return $business->teamMembers()->count() + 1; // +1 for owner
    }

    /**
     * Get team members count for the business (excluding owner).
     */
    protected function getTeamMembersCount(Business $business): int
    {
        return $business->users()->count();
    }

    /**
     * Get branches count for the business.
     * TODO: Implement when branches feature is added
     */
    protected function getBranchesCount(Business $business): int
    {
        // Return 1 as default (main business location)
        return 1;
    }

    /**
     * Get Instagram accounts count for the business.
     */
    protected function getInstagramAccountsCount(Business $business): int
    {
        return $business->instagramAccounts()->count();
    }

    /**
     * Get monthly leads count for the business.
     */
    protected function getMonthlyLeadsCount(Business $business): int
    {
        return $business->leads()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    /**
     * Get AI call minutes used this month.
     */
    protected function getAiCallMinutesUsed(Business $business): int
    {
        // TODO: Implement when call recording feature is added
        // For now return 0
        return 0;
    }

    /**
     * Get chatbot channels count for the business.
     */
    protected function getChatbotChannelsCount(Business $business): int
    {
        return $business->chatbotConfigs()->count();
    }

    /**
     * Get telegram bots count for the business.
     */
    protected function getTelegramBotsCount(Business $business): int
    {
        return $business->chatbotConfigs()
            ->where('platform', 'telegram')
            ->count();
    }

    /**
     * Get AI requests count this month.
     */
    protected function getAiRequestsCount(Business $business): int
    {
        // TODO: Implement when AI usage tracking is added
        // For now return 0
        return 0;
    }

    /**
     * Get storage used in MB.
     */
    protected function getStorageUsedMb(Business $business): int
    {
        // TODO: Implement actual storage calculation
        // For now return 0
        return 0;
    }

    // ==================== STATIC HELPERS ====================

    /**
     * Get all limit config keys.
     */
    public function getLimitKeys(): array
    {
        return array_keys($this->limitConfig);
    }

    /**
     * Get all feature config keys.
     */
    public function getFeatureKeys(): array
    {
        return array_keys($this->featureConfig);
    }

    /**
     * Get limit configuration.
     */
    public function getLimitConfig(): array
    {
        return $this->limitConfig;
    }

    /**
     * Get feature configuration.
     */
    public function getFeatureConfig(): array
    {
        return $this->featureConfig;
    }
}
