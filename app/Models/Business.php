<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use SoftDeletes, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'category',
        'industry',
        'industry_code',
        'industry_detected_at',
        'industry_id',
        'sub_industry_id',
        'business_type',
        'business_model',
        'business_stage',
        'founding_date',
        'team_size',
        'employee_count',
        'monthly_revenue',
        'target_audience',
        'main_goals',
        'maturity_score',
        'maturity_level',
        'is_onboarding_completed',
        'onboarding_completed_at',
        'onboarding_status',
        'onboarding_current_step',
        'launched_at',
        'description',
        'logo',
        'website',
        'phone',
        'email',
        'address',
        'city',
        'region',
        'country',
        'status',
        'settings',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'main_goals' => 'array',
        'founding_date' => 'date',
        'is_onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'launched_at' => 'datetime',
    ];

    /**
     * Get onboarding progress percent as accessor (Phase 1 completion)
     * Access via $business->onboarding_percent
     * This returns phase_1_completion_percent which is what users see on onboarding page
     */
    public function getOnboardingPercentAttribute(): int
    {
        $progress = $this->onboardingProgress()->first();
        return $progress ? (int) $progress->phase_1_completion_percent : 0;
    }

    /**
     * Boot method - Business yaratilganda OnboardingProgress ham yaratilsin
     */
    protected static function boot()
    {
        parent::boot();

        static::created(function ($business) {
            // OnboardingProgress yaratish
            $business->onboardingProgress()->create([
                'current_phase' => 1,
                'phase_1_status' => 'in_progress',
                'phase_1_completion_percent' => 0,
            ]);

            // BusinessMaturityAssessment yaratish
            if (!$business->maturityAssessment) {
                $business->maturityAssessment()->create([
                    'business_id' => $business->id,
                ]);
            }

            // Auto-detect industry_code from category/industry
            if (!$business->industry_code) {
                $business->industry_code = \App\Services\KPI\BusinessCategoryMapper::detectFromBusiness($business);
                $business->industry_detected_at = now();
                $business->saveQuietly(); // Save without triggering events again
            }
        });

        static::updating(function ($business) {
            // Re-detect industry_code if category or industry changes
            if ($business->isDirty(['category', 'industry', 'business_type']) && !$business->isDirty('industry_code')) {
                $business->industry_code = \App\Services\KPI\BusinessCategoryMapper::detectFromBusiness($business);
                $business->industry_detected_at = now();
            }
        });
    }

    /**
     * Get the owner of the business.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the users (team members) of the business.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->using(BusinessUser::class)
            ->withPivot('id', 'role', 'joined_at')
            ->withTimestamps();
    }

    /**
     * Get the subscriptions for the business.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the dream buyers for the business.
     */
    public function dreamBuyers(): HasMany
    {
        return $this->hasMany(DreamBuyer::class);
    }

    /**
     * Get the leads for the business.
     */
    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    /**
     * Get the customers for the business.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Get the products for the business.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Get the marketing channels for the business.
     */
    public function marketingChannels(): HasMany
    {
        return $this->hasMany(MarketingChannel::class);
    }

    /**
     * Get the competitors for the business.
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }

    /**
     * Get the offers for the business.
     */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    /**
     * Get the integrations for the business.
     */
    public function integrations(): HasMany
    {
        return $this->hasMany(Integration::class);
    }

    /**
     * Check if business has a specific integration type connected.
     */
    public function hasIntegration(string $type): bool
    {
        return $this->integrations()
            ->where('type', $type)
            ->where('status', 'connected')
            ->exists();
    }

    /**
     * Get the chatbot configs for the business.
     */
    public function chatbotConfigs(): HasMany
    {
        return $this->hasMany(ChatbotConfig::class);
    }

    /**
     * Get the chatbot conversations for the business.
     */
    public function chatbot_conversations(): HasMany
    {
        return $this->hasMany(ChatbotConversation::class);
    }

    /**
     * Get the campaigns for the business.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    // ==================== ONBOARDING RELATIONSHIPS ====================

    /**
     * Get the industry for the business.
     */
    public function industryRelation(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    /**
     * Get the sub-industry for the business.
     */
    public function subIndustry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'sub_industry_id');
    }

    /**
     * Get the onboarding progress for the business.
     */
    public function onboardingProgress(): HasOne
    {
        return $this->hasOne(OnboardingProgress::class);
    }

    /**
     * Get the onboarding steps for the business.
     */
    public function onboardingSteps(): HasMany
    {
        return $this->hasMany(OnboardingStep::class);
    }

    /**
     * Get the maturity assessment for the business.
     */
    public function maturityAssessment(): HasOne
    {
        return $this->hasOne(BusinessMaturityAssessment::class);
    }

    /**
     * Get the business problems.
     */
    public function problems(): HasMany
    {
        return $this->hasMany(BusinessProblem::class);
    }

    /**
     * Get the market research for the business.
     */
    public function marketResearch(): HasMany
    {
        return $this->hasMany(MarketResearch::class);
    }

    /**
     * Get the marketing hypotheses for the business.
     */
    public function hypotheses(): HasMany
    {
        return $this->hasMany(MarketingHypothesis::class);
    }

    /**
     * Get the sales metrics for the business.
     */
    public function salesMetrics(): HasOne
    {
        return $this->hasOne(SalesMetrics::class);
    }

    /**
     * Get the marketing metrics for the business.
     */
    public function marketingMetrics(): HasOne
    {
        return $this->hasOne(MarketingMetrics::class);
    }

    /**
     * Get team members (excluding owner)
     */
    public function teamMembers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_user')
            ->using(BusinessUser::class)
            ->withPivot('id', 'role', 'permissions', 'invited_at', 'accepted_at')
            ->withTimestamps();
    }

    // ==================== SUBSCRIPTION HELPERS ====================

    /**
     * Get the active subscription
     */
    public function activeSubscription()
    {
        return $this->subscriptions()
            ->with('plan')
            ->where(function ($query) {
                $query->where('status', 'active')
                    ->whereDate('ends_at', '>=', now())
                    ->orWhere(function ($q) {
                        $q->where('status', 'trial')
                            ->whereDate('trial_ends_at', '>=', now());
                    });
            })
            ->first();
    }

    /**
     * Check if business has an active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription() !== null;
    }

    /**
     * Check if business is on trial
     */
    public function isOnTrial(): bool
    {
        return $this->subscriptions()
            ->where('status', 'trial')
            ->whereDate('trial_ends_at', '>=', now())
            ->exists();
    }

    /**
     * Get current plan
     */
    public function currentPlan()
    {
        $subscription = $this->activeSubscription();
        return $subscription ? $subscription->plan : null;
    }

    /**
     * Check if business can use a feature
     */
    public function canUseFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        if (!$plan) {
            return false;
        }

        // Check specific features
        switch ($feature) {
            case 'amocrm':
                return $plan->has_amocrm;
            default:
                return in_array($feature, $plan->features ?? []);
        }
    }

    /**
     * Check if business has reached a specific limit
     */
    public function hasReachedLimit(string $limitType): bool
    {
        $plan = $this->currentPlan();

        if (!$plan) {
            return true; // No plan = reached all limits
        }

        switch ($limitType) {
            case 'leads':
                $limit = $plan->lead_limit;
                $current = $this->leads()->count();
                return $limit && $current >= $limit;

            case 'team_members':
                $limit = $plan->team_member_limit;
                $current = $this->teamMembers()->count();
                return $limit && $current >= $limit;

            case 'chatbot_channels':
                $limit = $plan->chatbot_channel_limit;
                $current = $this->chatbotConfigs()->count();
                return $limit && $current >= $limit;

            case 'businesses':
                $limit = $plan->business_limit;
                // This would be checked at user level, not business level
                return false;

            default:
                return false;
        }
    }

    /**
     * Get usage stats for the business
     */
    public function getUsageStats(): array
    {
        $plan = $this->currentPlan();

        if (!$plan) {
            return [];
        }

        return [
            'leads' => [
                'current' => $this->leads()->count(),
                'limit' => $plan->lead_limit ?? 'unlimited',
                'percentage' => $plan->lead_limit ? ($this->leads()->count() / $plan->lead_limit) * 100 : 0,
            ],
            'team_members' => [
                'current' => $this->teamMembers()->count(),
                'limit' => $plan->team_member_limit ?? 'unlimited',
                'percentage' => $plan->team_member_limit ? ($this->teamMembers()->count() / $plan->team_member_limit) * 100 : 0,
            ],
            'chatbot_channels' => [
                'current' => $this->chatbotConfigs()->count(),
                'limit' => $plan->chatbot_channel_limit ?? 'unlimited',
                'percentage' => $plan->chatbot_channel_limit ? ($this->chatbotConfigs()->count() / $plan->chatbot_channel_limit) * 100 : 0,
            ],
        ];
    }

    /**
     * Get days remaining in current subscription
     */
    public function subscriptionDaysRemaining(): ?int
    {
        $subscription = $this->activeSubscription();

        if (!$subscription) {
            return null;
        }

        if ($subscription->status === 'trial') {
            return now()->diffInDays($subscription->trial_ends_at, false);
        }

        return now()->diffInDays($subscription->ends_at, false);
    }

    // ==================== ALGORITHM RELATIONSHIPS ====================

    /**
     * Get the ad platform integrations for the business (Google Ads, Yandex Direct, YouTube, etc.)
     */
    public function adIntegrations(): HasMany
    {
        return $this->hasMany(\App\Models\AdIntegration::class);
    }

    /**
     * Get the Instagram accounts for the business.
     */
    public function instagramAccounts(): HasMany
    {
        return $this->hasMany(\App\Models\InstagramAccount::class);
    }

    /**
     * Get the AI diagnostics for the business.
     */
    public function aiDiagnostics(): HasMany
    {
        return $this->hasMany(\App\Models\AiDiagnostic::class);
    }

    // ==================== KPI RELATIONSHIPS ====================

    /**
     * Get the KPI configuration for the business.
     */
    public function kpiConfiguration(): HasOne
    {
        return $this->hasOne(BusinessKpiConfiguration::class);
    }

    /**
     * Get all KPI daily actuals for the business.
     */
    public function kpiDailyActuals(): HasMany
    {
        return $this->hasMany(KpiDailyActual::class);
    }

    /**
     * Get all KPI weekly summaries for the business.
     */
    public function kpiWeeklySummaries(): HasMany
    {
        return $this->hasMany(KpiWeeklySummary::class);
    }

    /**
     * Get all KPI monthly summaries for the business.
     */
    public function kpiMonthlySummaries(): HasMany
    {
        return $this->hasMany(KpiMonthlySummary::class);
    }
}
