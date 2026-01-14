<?php

namespace App\Http\Middleware;

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
            'locale' => fn () => $this->getLocale($request),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }

    /**
     * Get authenticated user data with caching.
     */
    private function getAuthData($user): ?array
    {
        if (!$user) {
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
                'roles' => $user->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])->toArray(),
            ];
        });

        // Return with 'user' key for Vue compatibility ($page.props.auth.user.name)
        return [
            'user' => $userData,
        ];
    }

    /**
     * Get user's businesses with caching.
     */
    private function getUserBusinesses($user): array
    {
        if (!$user) {
            return [];
        }

        // Cache businesses for 5 minutes
        $cacheKey = "user_businesses_{$user->id}";

        return Cache::remember($cacheKey, 300, function () use ($user) {
            return $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo')
                ->get()
                ->map(fn($b) => [
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
        if (!$user) {
            return null;
        }

        $currentBusinessId = session('current_business_id');

        if (!$currentBusinessId) {
            // Get first business if no session
            $firstBusiness = $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo')
                ->first();

            if ($firstBusiness) {
                session(['current_business_id' => $firstBusiness->id]);
                return $this->formatBusiness($firstBusiness);
            }

            return null;
        }

        // Cache current business for 5 minutes
        $cacheKey = "current_business_{$currentBusinessId}";

        return Cache::remember($cacheKey, 300, function () use ($user, $currentBusinessId) {
            // Try owned businesses first
            $business = $user->businesses()
                ->select('id', 'name', 'slug', 'category', 'logo')
                ->find($currentBusinessId);

            // If not found, might be team member
            if (!$business) {
                $business = \App\Models\Business::select('id', 'name', 'slug', 'category', 'logo')
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
        return [
            'id' => $business->id,
            'name' => $business->name,
            'slug' => $business->slug,
            'category' => $business->category,
            'logo' => $business->logo,
        ];
    }

    /**
     * Clear user cache when needed (call this after user/business updates).
     */
    public static function clearUserCache(int $userId): void
    {
        Cache::forget("user_auth_data_{$userId}");
        Cache::forget("user_businesses_{$userId}");
    }

    /**
     * Clear business cache when needed.
     */
    public static function clearBusinessCache(int $businessId): void
    {
        Cache::forget("current_business_{$businessId}");
    }

    /**
     * Get current locale from cookie or default.
     */
    private function getLocale(Request $request): array
    {
        $allowedLocales = ['uz-latn', 'uz-cyrl', 'ru'];
        $locale = $request->cookie('locale', 'uz-latn');

        if (!in_array($locale, $allowedLocales)) {
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
}
