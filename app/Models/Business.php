<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'instagram_account_id',
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
        'swot_data',
        'swot_updated_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'settings' => 'array',
        'main_goals' => 'array',
        'swot_data' => 'array',
        'founding_date' => 'date',
        'is_onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'launched_at' => 'datetime',
        'swot_updated_at' => 'datetime',
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
            if (! $business->maturityAssessment) {
                $business->maturityAssessment()->create([
                    'business_id' => $business->id,
                ]);
            }

            // Auto-detect industry_code from category/industry
            if (! $business->industry_code) {
                $business->industry_code = \App\Services\KPI\BusinessCategoryMapper::detectFromBusiness($business);
                $business->industry_detected_at = now();
                $business->saveQuietly(); // Save without triggering events again
            }

            // Create default pipeline stages for new business
            PipelineStage::createDefaultStages($business->id);
        });

        static::updating(function ($business) {
            // Re-detect industry_code if category or industry changes
            if ($business->isDirty(['category', 'industry', 'business_type']) && ! $business->isDirty('industry_code')) {
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

    // ==================== SALES KPI & GAMIFICATION RELATIONSHIPS ====================

    /**
     * Get the KPI settings for the business.
     */
    public function kpiSettings(): HasMany
    {
        return $this->hasMany(SalesKpiSetting::class);
    }

    /**
     * Get the bonus settings for the business.
     */
    public function bonusSettings(): HasMany
    {
        return $this->hasMany(SalesBonusSetting::class);
    }

    /**
     * Get the penalty rules for the business.
     */
    public function penaltyRules(): HasMany
    {
        return $this->hasMany(SalesPenaltyRule::class);
    }

    /**
     * Get the achievement definitions for the business.
     */
    public function achievementDefinitions(): HasMany
    {
        return $this->hasMany(SalesAchievementDefinition::class);
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
            ->where('status', 'trialing')
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
     * Uses new JSON-based features from Plan model
     */
    public function canUseFeature(string $feature): bool
    {
        $plan = $this->currentPlan();

        if (! $plan) {
            return false;
        }

        return $plan->hasFeature($feature);
    }

    /**
     * Check if business has reached a specific limit
     * Uses new JSON-based limits from Plan model via PlanLimitService
     */
    public function hasReachedLimit(string $limitKey): bool
    {
        return app(\App\Services\PlanLimitService::class)->hasReachedLimit($this, $limitKey);
    }

    /**
     * Check if business can add more items for a limit
     */
    public function canAdd(string $limitKey, int $count = 1): bool
    {
        return app(\App\Services\PlanLimitService::class)->canAdd($this, $limitKey, $count);
    }

    /**
     * Get remaining quota for a limit
     */
    public function getRemainingQuota(string $limitKey): ?int
    {
        return app(\App\Services\PlanLimitService::class)->getRemainingQuota($this, $limitKey);
    }

    /**
     * Get usage stats for the business
     * Uses new JSON-based limits via PlanLimitService
     */
    public function getUsageStats(): array
    {
        return app(\App\Services\PlanLimitService::class)->getUsageStats($this);
    }

    /**
     * Get all enabled features for the business
     */
    public function getEnabledFeatures(): array
    {
        return app(\App\Services\PlanLimitService::class)->getEnabledFeatures($this);
    }

    /**
     * Get days remaining in current subscription
     */
    public function subscriptionDaysRemaining(): ?int
    {
        $subscription = $this->activeSubscription();

        if (! $subscription) {
            return null;
        }

        if ($subscription->status === 'trialing') {
            return (int) ceil(now()->floatDiffInDays($subscription->trial_ends_at, false));
        }

        return (int) ceil(now()->floatDiffInDays($subscription->ends_at, false));
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
     * Get the selected Instagram account for the business.
     */
    public function instagramAccount(): BelongsTo
    {
        return $this->belongsTo(\App\Models\InstagramAccount::class, 'instagram_account_id');
    }

    /**
     * Get the AI diagnostics for the business.
     */
    public function aiDiagnostics(): HasMany
    {
        return $this->hasMany(AiDiagnostic::class);
    }

    /**
     * Alias for backward compatibility
     */
    public function diagnostics(): HasMany
    {
        return $this->aiDiagnostics();
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

    // ==================== REPORTS RELATIONSHIPS ====================

    /**
     * Get the report schedules for the business.
     */
    public function reportSchedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class);
    }

    /**
     * Get the report templates for the business.
     */
    public function reportTemplates(): HasMany
    {
        return $this->hasMany(ReportTemplate::class);
    }

    /**
     * Get the generated reports for the business.
     */
    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }

    /**
     * Get the active report schedules for the business.
     */
    public function activeReportSchedules(): HasMany
    {
        return $this->hasMany(ReportSchedule::class)->where('is_active', true);
    }

    /**
     * Get the content templates for the business.
     */
    public function contentTemplates(): HasMany
    {
        return $this->hasMany(ContentTemplate::class);
    }

    /**
     * Get the content style guide for the business.
     */
    public function contentStyleGuide(): HasOne
    {
        return $this->hasOne(ContentStyleGuide::class);
    }

    /**
     * Get the content generations for the business.
     */
    public function contentGenerations(): HasMany
    {
        return $this->hasMany(ContentGeneration::class);
    }

    /**
     * Get the orders for the business.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the tasks for the business.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the sales for the business.
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    // ==================== ACCESS CONTROL ====================

    /**
     * Check if a user has access to this business
     * User has access if they are: owner, team member, or admin
     *
     * @param  \App\Models\User|null  $user
     */
    public function userHasAccess($user = null): bool
    {
        if (! $user) {
            $user = auth()->user();
        }

        if (! $user) {
            return false;
        }

        // Business owner always has access
        if ($this->user_id === $user->id) {
            return true;
        }

        // Admins have access to all businesses
        if ($user->hasRole(['admin', 'super_admin'])) {
            return true;
        }

        // Team members have access (check business_user pivot table)
        return $this->users()
            ->where('users.id', $user->id)
            ->whereNotNull('business_user.accepted_at')
            ->exists();
    }
}
