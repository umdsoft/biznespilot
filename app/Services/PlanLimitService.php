<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Plan;
use App\Models\Subscription;
use Illuminate\Support\Facades\Cache;

class PlanLimitService
{
    protected array $limitConfig;

    protected array $featureConfig;

    public function __construct()
    {
        $this->limitConfig = PlanConfig::limits();
        $this->featureConfig = PlanConfig::features();
    }

    /**
     * Get the active subscription for a business.
     */
    public function getActiveSubscription(Business $business): ?Subscription
    {
        return $business->subscriptions()->with('plan')->active()->first();
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
     * Counts queue_branches (service locations) for the business.
     * Always at least 1 (main business location).
     */
    protected function getBranchesCount(Business $business): int
    {
        $count = \App\Models\Bot\Queue\QueueBranch::where('business_id', $business->id)->count();

        return max($count, 1);
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
     * Get AI call minutes used this month (analyzed calls only).
     */
    protected function getAiCallMinutesUsed(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_ai_call_minutes_" . now()->format('Y_m'),
            3600,
            fn () => (int) ceil(
                \App\Models\CallLog::where('business_id', $business->id)
                    ->has('analysis')
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('duration') / 60
            )
        );
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
        return \App\Models\TelegramBot::where('business_id', $business->id)->count();
    }

    /**
     * Get AI requests count this month.
     */
    protected function getAiRequestsCount(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_ai_requests_" . now()->format('Y_m'),
            300,
            fn () => \App\Models\ContentGeneration::where('business_id', $business->id)
                ->where('status', 'completed')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count()
        );
    }

    /**
     * Get storage used in MB.
     * Calculates from feedback attachments file sizes.
     */
    protected function getStorageUsedMb(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_storage_mb",
            3600,
            function () use ($business) {
                $bytes = \App\Models\FeedbackAttachment::whereHas('feedbackReport', function ($q) use ($business) {
                    $q->where('business_id', $business->id);
                })->sum('file_size');

                return (int) ceil($bytes / (1024 * 1024));
            }
        );
    }

    /**
     * Bugungi AI Agent savollari sonini olish.
     */
    protected function getAgentQuestionsTodayCount(Business $business): int
    {
        return \App\Models\AgentMessage::where('business_id', $business->id)
            ->where('role', 'user')
            ->whereDate('created_at', now()->toDateString())
            ->count();
    }

    /**
     * Bu oydagi ovozli xabarlar sonini olish.
     */
    protected function getVoiceMessagesMonthlyCount(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_voice_messages_" . now()->format('Y_m'),
            300,
            fn () => \App\Models\VoiceInteraction::where('business_id', $business->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count()
        );
    }

    /**
     * Bu oydagi mashq sessiyalari sonini olish.
     */
    protected function getTrainingSessionsMonthlyCount(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_training_sessions_" . now()->format('Y_m'),
            300,
            fn () => \App\Models\TrainingSession::where('business_id', $business->id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count()
        );
    }

    /**
     * Bu oydagi chatbot xabarlari sonini olish.
     */
    protected function getChatMessagesMonthlyCount(Business $business): int
    {
        return (int) Cache::remember(
            "business_{$business->id}_chat_messages_" . now()->format('Y_m'),
            300,
            fn () => \App\Models\ChatbotMessage::whereHas('conversation', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count()
        );
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
