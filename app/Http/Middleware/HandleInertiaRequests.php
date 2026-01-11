<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
        $currentBusiness = null;
        $businesses = [];

        if ($user) {
            // Get all user's businesses (owned)
            $businesses = $user->businesses()->select('id', 'name', 'slug', 'category', 'logo')->get()->map(fn($b) => [
                'id' => $b->id,
                'name' => $b->name,
                'slug' => $b->slug,
                'category' => $b->category,
                'logo' => $b->logo,
            ])->toArray();

            // Get current business from session
            $currentBusinessId = session('current_business_id');
            if ($currentBusinessId) {
                // First try to get from owned businesses
                $currentBusiness = $user->businesses()->find($currentBusinessId);

                // If not found, user might be a team member - get business directly
                if (!$currentBusiness) {
                    $currentBusiness = \App\Models\Business::find($currentBusinessId);
                }
            }

            // Fallback: get first owned business
            if (!$currentBusiness && count($businesses) > 0) {
                $currentBusiness = $user->businesses()->first();
                session(['current_business_id' => $currentBusiness?->id]);
            }
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'login' => $user->login,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'roles' => $user->roles->map(fn($role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                    ]),
                ] : null,
            ],
            'businesses' => $businesses,
            'currentBusiness' => $currentBusiness ? [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
                'slug' => $currentBusiness->slug,
                'category' => $currentBusiness->category,
                'logo' => $currentBusiness->logo,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'warning' => fn () => $request->session()->get('warning'),
                'info' => fn () => $request->session()->get('info'),
            ],
        ];
    }
}
