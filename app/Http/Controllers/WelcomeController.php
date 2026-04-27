<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Plan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Http\Middleware\HandleInertiaRequests;

class WelcomeController extends Controller
{
    /**
     * Show the welcome/instruction page for new users
     */
    public function index()
    {
        $user = Auth::user();

        // Team member (xodim) — o'z department panelidan boshlasin
        if ($redirect = $this->teamMemberRedirect($user)) {
            return $redirect;
        }

        // If user already has a business, redirect to dashboard
        if ($user->businesses()->exists()) {
            return redirect()->route('business.dashboard');
        }

        // Partner — biznes yaratish shart emas, partner dashboard'iga
        if ($user->hasRole('partner')) {
            return redirect()->route('partner.dashboard');
        }

        // Redirect to create business page directly
        return redirect()->route('welcome.create-business');
    }

    /**
     * Show the create business form (for first-time users)
     */
    public function createBusiness()
    {
        $user = Auth::user();

        // Team member (xodim) hech qachon "biznes yarat" formasiga tushmasligi kerak
        if ($redirect = $this->teamMemberRedirect($user)) {
            return $redirect;
        }

        // If user already has a business, redirect to new-business route
        if ($user->businesses()->exists()) {
            return redirect()->route('new-business');
        }

        // Partner — biznes yaratishga kerak emas
        if ($user->hasRole('partner')) {
            return redirect()->route('partner.dashboard');
        }

        return Inertia::render('Welcome/CreateBusiness');
    }

    /**
     * Agar user xodim (team member) bo'lsa — o'z department dashboard'iga yo'naltiradi.
     * Aks holda null qaytaradi (chaqiruvchi default oqimini davom ettiradi).
     */
    protected function teamMemberRedirect($user)
    {
        $membership = BusinessUser::where('user_id', $user->id)
            ->whereNotNull('department')
            ->first();

        if (! $membership) {
            return null;
        }

        session(['current_business_id' => $membership->business_id]);

        return match ($membership->department) {
            'sales_head' => redirect()->route('sales-head.dashboard'),
            'sales_operator', 'operator' => redirect()->route('operator.dashboard'),
            'marketing' => redirect()->route('marketing.hub'),
            'finance' => redirect()->route('finance.dashboard'),
            'hr' => redirect()->route('hr.dashboard'),
            default => redirect()->route('business.dashboard'),
        };
    }

    /**
     * Show the create business form (for users who already have businesses)
     */
    public function newBusiness()
    {
        return Inertia::render('Welcome/CreateBusiness', [
            'isAdditionalBusiness' => true,
        ]);
    }

    /**
     * Store a new business (first time)
     */
    public function storeBusiness(Request $request)
    {
        return $this->createAndStoreBusiness($request);
    }

    /**
     * Store a new business (additional)
     */
    public function storeNewBusiness(Request $request)
    {
        return $this->createAndStoreBusiness($request);
    }

    /**
     * Show the welcome/start page after business creation
     */
    public function start()
    {
        $user = Auth::user();
        $business = $user->businesses()->latest()->first();

        // If no business exists, redirect to create business
        if (! $business) {
            return redirect()->route('welcome.create-business');
        }

        return Inertia::render('Welcome/Start', [
            'business' => $business,
        ]);
    }

    /**
     * Common method to create and store a business
     */
    private function createAndStoreBusiness(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'website' => ['nullable', 'url', 'max:255'],
            'region' => ['required', 'string', 'max:100'],
            'employee_count' => ['nullable', 'string', 'max:50'],
            'monthly_revenue' => ['nullable', 'string', 'max:50'],
            'target_audience' => ['nullable', 'string', 'max:500'],
            'main_goals' => ['required', 'array', 'min:1'],
        ], [
            'category.required' => 'Biznes kategoriyasini tanlang',
            'region.required' => 'Viloyatni tanlang',
            'main_goals.required' => 'Kamida bitta maqsad tanlang',
            'main_goals.min' => 'Kamida bitta maqsad tanlang',
        ]);

        // Generate unique slug from business name
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Business::where('slug', $slug)->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
        }

        // Create the business (UUID is auto-generated by HasUuid trait)
        $business = Business::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'slug' => $slug,
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'website' => $validated['website'] ?? null,
            'city' => $validated['region'],
            'region' => $validated['region'],
            'country' => "O'zbekiston",
            'employee_count' => $validated['employee_count'] ?? null,
            'monthly_revenue' => $validated['monthly_revenue'] ?? null,
            'target_audience' => $validated['target_audience'] ?? null,
            'main_goals' => $validated['main_goals'],
            'onboarding_status' => 'in_progress',
            'onboarding_current_step' => 'basic_info',
        ]);

        // Attach the current user as owner
        $business->users()->attach(Auth::id(), [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        // Partner referral attribution — cookie'dan yoki query'dan keladi
        $tracker = app(\App\Services\Partner\PartnerReferralTracker::class);
        $refCode = $tracker->getCodeFromRequest($request);
        if ($refCode) {
            $tracker->attachToBusiness($business, $refCode, 'link');
        }

        // Assign owner role using Spatie Permission (if not already)
        if (! Auth::user()->hasRole('owner')) {
            Auth::user()->assignRole('owner');
        }

        // Set the new business as current
        session(['current_business_id' => $business->id]);

        // Clear user context cache to force refresh
        session()->forget("user_context_{$business->user_id}");

        // Mark onboarding as completed (skip onboarding for now)
        $business->update([
            'onboarding_status' => 'completed',
            'onboarding_completed_at' => now(),
        ]);

        // Avtomatik 14 kunlik trial subscription yaratish
        try {
            $trialPlan = Plan::where('slug', 'trial-pack')->first();
            if ($trialPlan) {
                app(SubscriptionService::class)->create($business, $trialPlan, 'monthly', 14);
                Log::info('Trial subscription yaratildi', ['business_id' => $business->id]);
            }
        } catch (\Exception $e) {
            Log::warning('Trial subscription yaratishda xatolik', [
                'business_id' => $business->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Cache ni tozalash (yangi subscription ko'rinishi uchun)
        HandleInertiaRequests::clearSubscriptionCache($business->id);
        HandleInertiaRequests::clearUserCache(Auth::id());
        HandleInertiaRequests::clearBusinessCache($business->id);

        // Biznes yaratilgandan keyin tarif sahifasini ko'rsatish
        return redirect()->route('business.subscription.index')
            ->with('success', 'Biznes muvaffaqiyatli yaratildi! 14 kunlik bepul sinov davri berildi.');
    }

    /**
     * Switch to a different business
     */
    public function switchBusiness(Business $business)
    {
        $user = Auth::user();

        // Check if user has access to this business
        if (! $user->businesses()->where('businesses.id', $business->id)->exists()) {
            abort(403, 'Bu biznesga kirish huquqingiz yo\'q');
        }

        // Set the business as current
        session(['current_business_id' => $business->id]);

        // Skip onboarding check for now - go directly to dashboard
        return redirect()->route('business.dashboard')
            ->with('success', "{$business->name} biznesiga o'tdingiz.");
    }
}
