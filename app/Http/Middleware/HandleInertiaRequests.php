<?php

namespace App\Http\Middleware;

use App\Models\Store\TelegramStore;
use App\Services\SubscriptionGate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => fn () => $this->getAuthData($user),
            'businesses' => fn () => $this->getUserBusinesses($user),
            'currentBusiness' => fn () => $this->getCurrentBusiness($user),
            // subscription — to'liq ma'lumot faqat kerak bo'lganda (5KB lazy)
            'subscription' => \Inertia\Inertia::lazy(fn () => $this->getSubscriptionData($user)),
            // subscriptionStatus — TrialBanner uchun minimal (har doim, ~200 bayt)
            'subscriptionStatus' => fn () => $this->getMinimalSubscriptionStatus($user),
            'activeStore' => fn () => $this->getActiveStore($user),
            'integrations' => fn () => $this->getIntegrationStatus($user),
            'locale' => fn () => $this->getLocale($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
                'upgrade_required' => fn () => $request->session()->get('upgrade_required'),
                'invite_success' => fn () => $request->session()->get('invite_success'),
            ],
        ];
    }

    /**
     * Get authenticated user data with caching.
     */
    private function getAuthData($user): ?array
    {
        if (! $user) {
            return null;
        }

        // Cache user auth data for 5 minutes
        $cacheKey = "user_auth_data_{$user->id}";

        $userData = Cache::remember($cacheKey, 300, function () use ($user) {
            // Eager load roles to prevent N+1
            $user->loadMissing('roles:id,name');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'login' => $user->login,
                'email' => $user->email,
                'phone' => $user->phone,
                'roles' => $user->roles->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->toArray(),
                // Telegram System Bot status
                'has_telegram_linked' => $user->hasTelegramLinked(),
                'receive_daily_reports' => (bool) $user->receive_daily_reports,
            ];
        });

        // auth.business — TrialBanner va boshqa komponentlar uchun
        $businessData = null;
        try {
            $businessId = session('current_business_id') ?: $user->businesses()->first()?->id;
            if ($businessId) {
                $businessData = Cache::remember("auth_business_{$businessId}", 300, function () use ($businessId) {
                    $biz = \App\Models\Business::find($businessId);
                    if (!$biz) return null;
                    return [
                        'id' => $biz->id,
                        'name' => $biz->name,
                        'category' => $biz->category ?? null,
                        'industry_code' => $biz->industry_code ?? null,
                    ];
                });
            }
        } catch (\Exception $e) {}

        // Return with 'user' key for Vue compatibility ($page.props.auth.user.name)
        return [
            'user' => $userData,
            'business' => $businessData,
        ];
    }

    /**
     * Get user's businesses with caching.
     */
    private function getUserBusinesses($user): array
    {
        if (! $user) {
            return [];
        }

        // Cache businesses for 5 minutes
        $cacheKey = "user_businesses_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo', 'user_id')
                ->get()
                ->map(fn ($b) => [
                    'id' => $b->id,
                    'name' => $b->name,
                    'slug' => $b->slug,
                    'category' => $b->category,
                    'logo' => $b->logo,
                ])
                ->toArray();
        });
    }

    /**
     * Get current business with optimized queries.
     */
    private function getCurrentBusiness($user): ?array
    {
        if (! $user) {
            return null;
        }

        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId) {
            // Get first business if no session
            $firstBusiness = $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo', 'user_id')
                ->first();

            if ($firstBusiness) {
                session(['current_business_id' => $firstBusiness->id]);

                return $this->formatBusiness($firstBusiness);
            }

            return null;
        }

        // Cache current business for 5 minutes (per user — is_owner farqlanadi)
        $cacheKey = "current_business_{$currentBusinessId}_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user, $currentBusinessId) {
            // Try owned businesses first
            $business = $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo', 'user_id')
                ->find($currentBusinessId);

            // If not found, might be team member
            if (! $business) {
                $business = \App\Models\Business::select('id', 'name', 'slug', 'category', 'logo', 'user_id')
                    ->find($currentBusinessId);
            }

            return $business ? $this->formatBusiness($business) : null;
        });
    }

    /**
     * Format business data for response.
     */
    private function formatBusiness($business): array
    {
        $user = request()->user();

        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            'category' => $business->category,
            'logo' => $business->logo,
            'is_owner' => $user && $business->user_id === $user->id,
        ];
    }

    /**
     * Clear user cache when needed (call this after user/business updates).
     */
    public static function clearUserCache(string|int $userId): void
    {
        Cache::forget("user_auth_data_{$userId}");
        Cache::forget("user_businesses_{$userId}");
    }

    /**
     * Clear business cache when needed.
     */
    public static function clearBusinessCache(string|int $businessId): void
    {
        Cache::forget("current_business_{$businessId}");
    }

    /**
     * Get active store for sidebar and bot-type-aware navigation.
     */
    private function getActiveStore($user): ?array
    {
        if (! $user) {
            return null;
        }

        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId) {
            return null;
        }

        $activeStoreId = session('active_store_id');

        $cacheKey = $activeStoreId
            ? "active_store_{$activeStoreId}"
            : "first_store_{$currentBusinessId}";

        return Cache::remember($cacheKey, 300, function () use ($currentBusinessId, $activeStoreId) {
            $query = TelegramStore::where('business_id', $currentBusinessId);

            if ($activeStoreId) {
                $store = $query->where('id', $activeStoreId)->first();

                if (! $store) {
                    session()->forget('active_store_id');

                    return null;
                }
            } else {
                return null;
            }

            return [
                'id' => $store->id,
                'name' => $store->name,
                'slug' => $store->slug,
                'store_type' => $store->store_type,
                'is_active' => $store->is_active,
                'store_type_label' => $store->getBotTypeEnum()?->label(),
                'store_type_icon' => $store->getBotTypeEnum()?->icon(),
                'store_type_color' => $store->getBotTypeEnum()?->color(),
                'store_type_bg_color' => $store->getBotTypeEnum()?->bgColor(),
                'store_type_action' => $store->getBotTypeEnum()?->primaryActionLabel(),
                'sidebar_menu' => $store->getBotTypeEnum()?->sidebarMenu() ?? [],
            ];
        });
    }

    /**
     * Clear active store cache.
     */
    public static function clearActiveStoreCache(string|int $storeId): void
    {
        Cache::forget("active_store_{$storeId}");
    }

    /**
     * Get current locale from cookie or default.
     */
    private function getLocale(Request $request): array
    {
        $allowedLocales = ['uz-latn', 'uz-cyrl', 'ru'];
        $locale = $request->cookie('locale', 'uz-latn');

        if (! in_array($locale, $allowedLocales)) {
            $locale = 'uz-latn';
        }

        return [
            'current' => $locale,
            'available' => [
                'uz-latn' => ['code' => 'uz-latn', 'name' => "O'zbekcha", 'flag' => '🇺🇿'],
                'uz-cyrl' => ['code' => 'uz-cyrl', 'name' => 'Ўзбекча', 'flag' => '🇺🇿'],
                'ru' => ['code' => 'ru', 'name' => 'Русский', 'flag' => '🇷🇺'],
            ],
        ];
    }

    /**
     * Minimal subscription status — TrialBanner uchun (har sahifada yuboriladi, 200 bayt)
     */
    private function getMinimalSubscriptionStatus($user): array
    {
        if (!$user) return ['has_subscription' => false, 'days_remaining' => 0];

        try {
            $businessId = session('current_business_id') ?: $user->businesses()->first()?->id;
            if (!$businessId) return ['has_subscription' => false, 'days_remaining' => 0];

            $cacheKey = "sub_minimal_{$businessId}";
            return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($businessId) {
                $sub = \App\Models\Subscription::where('business_id', $businessId)
                    ->whereIn('status', ['active', 'trialing'])
                    ->latest()
                    ->first();

                if (!$sub) return ['has_subscription' => false, 'days_remaining' => 0];

                $endDate = ($sub->status === 'trialing' && $sub->trial_ends_at)
                    ? $sub->trial_ends_at
                    : $sub->ends_at;

                $daysRemaining = $endDate ? max(0, (int) now()->diffInDays($endDate, false)) : 999;

                return [
                    'has_subscription' => true,
                    'is_trial' => $sub->status === 'trialing',
                    'status' => $sub->status,
                    'days_remaining' => $daysRemaining,
                    'plan_name' => $sub->plan?->name ?? 'Plan',
                ];
            });
        } catch (\Exception $e) {
            return ['has_subscription' => false, 'days_remaining' => 0];
        }
    }

    /**
     * Get subscription data with limits and features.
     * Frontend uchun v-if="features.hr_tasks" formatida ishlatiladi.
     */
    private function getSubscriptionData($user): ?array
    {
        if (! $user) {
            return null;
        }

        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId) {
            return null;
        }

        // Cache subscription data for 5 minutes
        $cacheKey = "subscription_data_{$currentBusinessId}";

        return Cache::remember($cacheKey, 300, function () use ($currentBusinessId) {
            $business = \App\Models\Business::find($currentBusinessId);

            if (! $business) {
                return null;
            }

            $gate = app(SubscriptionGate::class);

            try {
                $subscription = $gate->getActiveSubscription($business);
                $plan = $subscription->plan;

                // Features - v-if="features.hr_tasks" formatida
                $featuresRaw = $gate->getEnabledFeatures($business);
                $features = [];
                foreach ($featuresRaw as $key => $data) {
                    $features[$key] = $data['enabled'];
                }

                // Limits - usage stats bilan
                $limits = $gate->getUsageStats($business);

                // Plan ma'lumotlari
                $planData = [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'slug' => $plan->slug,
                ];

                // Subscription ma'lumotlari
                // Trial uchun trial_ends_at dan, pullik uchun ends_at dan hisoblash
                $effectiveEndDate = ($subscription->status === 'trialing' && $subscription->trial_ends_at)
                    ? $subscription->trial_ends_at
                    : $subscription->ends_at;

                $subscriptionData = [
                    'status' => $subscription->status,
                    'ends_at' => $subscription->ends_at?->toISOString(),
                    'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                    'days_remaining' => (int) max(0, ceil(now()->floatDiffInDays($effectiveEndDate, false))),
                    'is_trial' => $subscription->status === 'trialing',
                ];

                return [
                    'has_subscription' => true,
                    'plan' => $planData,
                    'subscription' => $subscriptionData,
                    'features' => $features,
                    'limits' => $limits,
                    'features_detail' => $featuresRaw,
                ];

            } catch (\App\Exceptions\NoActiveSubscriptionException $e) {
                return [
                    'has_subscription' => false,
                    'plan' => null,
                    'subscription' => null,
                    'features' => [],
                    'limits' => [],
                    'features_detail' => [],
                ];
            }
        });
    }

    /**
     * Clear subscription cache when plan changes.
     * Clears both Redis cache and session cache.
     */
    public static function clearSubscriptionCache(string|int $businessId): void
    {
        Cache::forget("subscription_data_{$businessId}");

        // Also clear session cache if available (not available in queue workers)
        if (session()->isStarted()) {
            session()->forget("sub_active_{$businessId}");
        }
    }

    /**
     * Get integration status for current business.
     */
    private function getIntegrationStatus($user): ?array
    {
        if (! $user) {
            return null;
        }

        $currentBusinessId = session('current_business_id');

        if (! $currentBusinessId) {
            return null;
        }

        $cacheKey = "integrations_status_{$currentBusinessId}";

        return Cache::remember($cacheKey, 300, function () use ($currentBusinessId) {
            $check = function (string $model) use ($currentBusinessId) {
                try {
                    return $model::where('business_id', $currentBusinessId)->exists();
                } catch (\Throwable) {
                    return false;
                }
            };

            return [
                'instagram' => $check(\App\Models\InstagramAccount::class),
                'facebook' => $check(\App\Models\FacebookPage::class),
                'meta_ads' => $check(\App\Models\MetaAdAccount::class),
                'telegram' => $check(\App\Models\TelegramBot::class),
            ];
        });
    }

    /**
     * Clear integration status cache.
     */
    public static function clearIntegrationCache(string|int $businessId): void
    {
        Cache::forget("integrations_status_{$businessId}");
    }
}
