<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BusinessController extends Controller
{
    /**
     * Display a listing of the user's businesses.
     */
    public function index()
    {
        $businesses = Auth::user()->businesses()
            ->withCount('users')
            ->latest()
            ->get()
            ->map(function ($business) {
                return [
                    'id' => $business->id,
                    'name' => $business->name,
                    'industry' => $business->industry,
                    'description' => $business->description,
                    'website' => $business->website,
                    'phone' => $business->phone,
                    'email' => $business->email,
                    'address' => $business->address,
                    'users_count' => $business->users_count,
                    'created_at' => $business->created_at->format('d.m.Y'),
                    'role' => optional($business->pivot)->role ?? 'member',
                ];
            });

        return Inertia::render('Business/Profile/Index', [
            'businesses' => $businesses,
        ]);
    }

    /**
     * Show the form for creating a new business.
     */
    public function create()
    {
        return Inertia::render('Business/Profile/Create');
    }

    /**
     * Store a newly created business in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        // Create the business
        $business = Business::create($validated);

        // Attach the current user as owner
        $business->users()->attach(Auth::id(), [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Assign owner role using Spatie Permission
        Auth::user()->assignRole('owner');

        return redirect()->route('business.index')
            ->with('success', 'Biznes muvaffaqiyatli yaratildi!');
    }

    /**
     * Display the specified business.
     */
    public function show(Business $business)
    {
        // Check if user has access to this business
        if (!Auth::user()->businesses->contains($business)) {
            abort(403, 'Sizda ushbu biznesga kirish huquqi yo\'q.');
        }

        $business->load(['users' => function ($query) {
            $query->withPivot('role', 'joined_at');
        }]);

        return Inertia::render('Business/Profile/Show', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
                'description' => $business->description,
                'website' => $business->website,
                'phone' => $business->phone,
                'email' => $business->email,
                'address' => $business->address,
                'city' => $business->city,
                'country' => $business->country,
                'created_at' => $business->created_at->format('d.m.Y H:i'),
                'users' => $business->users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'login' => $user->login,
                        'email' => $user->email,
                        'role' => $user->pivot->role,
                        'joined_at' => $user->pivot->joined_at->format('d.m.Y'),
                    ];
                }),
            ],
        ]);
    }

    /**
     * Show the form for editing the specified business.
     */
    public function edit(Business $business)
    {
        // Check if user has access to this business
        if (!Auth::user()->businesses->contains($business)) {
            abort(403, 'Sizda ushbu biznesga kirish huquqi yo\'q.');
        }

        // Check if user has permission to edit
        $userRole = $business->users()->where('user_id', Auth::id())->first()->pivot->role;
        if (!in_array($userRole, ['owner', 'admin'])) {
            abort(403, 'Sizda ushbu biznesni tahrirlash huquqi yo\'q.');
        }

        return Inertia::render('Business/Profile/Edit', [
            'business' => [
                'id' => $business->id,
                'name' => $business->name,
                'industry' => $business->industry,
                'description' => $business->description,
                'website' => $business->website,
                'phone' => $business->phone,
                'email' => $business->email,
                'address' => $business->address,
                'city' => $business->city,
                'country' => $business->country,
            ],
        ]);
    }

    /**
     * Update the specified business in storage.
     */
    public function update(Request $request, Business $business)
    {
        // Check if user has access to this business
        if (!Auth::user()->businesses->contains($business)) {
            abort(403, 'Sizda ushbu biznesga kirish huquqi yo\'q.');
        }

        // Check if user has permission to edit
        $userRole = $business->users()->where('user_id', Auth::id())->first()->pivot->role;
        if (!in_array($userRole, ['owner', 'admin'])) {
            abort(403, 'Sizda ushbu biznesni tahrirlash huquqi yo\'q.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'industry' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
        ]);

        $business->update($validated);

        return redirect()->route('business.index')
            ->with('success', 'Biznes muvaffaqiyatli yangilandi!');
    }

    /**
     * Remove the specified business from storage.
     */
    public function destroy(Business $business)
    {
        // Check if user has access to this business
        if (!Auth::user()->businesses->contains($business)) {
            abort(403, 'Sizda ushbu biznesga kirish huquqi yo\'q.');
        }

        // Only owner can delete business
        $userRole = $business->users()->where('user_id', Auth::id())->first()->pivot->role;
        if ($userRole !== 'owner') {
            abort(403, 'Faqat biznes egasi biznesni o\'chira oladi.');
        }

        $business->delete();

        return redirect()->route('business.index')
            ->with('success', 'Biznes muvaffaqiyatli o\'chirildi!');
    }
}
