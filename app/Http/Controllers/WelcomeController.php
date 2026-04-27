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

        // Owner bo'lmagan foydalanuvchilar uchun guard:
        // team member, role bilan xodim, partner — hech qaysi "biznes yarat" formasiga tushmasin
        if ($redirect = $this->nonOwnerRedirect($user)) {
            return $redirect;
        }

        // If user already has a business, redirect to dashboard
        if ($user->businesses()->exists()) {
            return redirect()->route('business.dashboard');
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

        // Owner bo'lmagan foydalanuvchilar uchun guard
        if ($redirect = $this->nonOwnerRedirect($user)) {
            return $redirect;
        }

        // If user already has a business, redirect to new-business route
        if ($user->businesses()->exists()) {
            return redirect()->route('new-business');
        }

        return Inertia::render('Welcome/CreateBusiness');
    }

    /**
     * Faqat owner bo'lishi kerak bo'lgan flow ichida non-owner userlarni
     * o'z dashboard'iga yo'naltiradi. "Biznes yarat" formasi faqat hali biznesi yo'q,
     * roli yo'q, partner emas yangi foydalanuvchilar uchun.
     *
     * Owner bo'lmasa redirect qaytaradi, owner yoki to'liq yangi user bo'lsa null.
     */
    protected function nonOwnerRedirect($user)
    {
        // 1. Team member (business_user.department) — eng aniq signal
        $membership = BusinessUser::where('user_id', $user->id)
            ->whereNotNull('department')
            ->first();

        if ($membership) {
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

        // 2. Spatie role bilan biriktirilgan xodim (business_user yo'q bo'lsa ham)
        $roleRoutes = [
            'sales_head' => 'sales-head.dashboard',
            'sales_operator' => 'operator.dashboard',
            'operator' => 'operator.dashboard',
            'marketing' => 'marketing.hub',
            'finance' => 'finance.dashboard',
            'hr' => 'hr.dashboard',
        ];

        foreach ($roleRoutes as $role => $route) {
            if ($user->hasRole($role)) {
                return redirect()->route($route);
            }
        }

        // 3. Partner — alohida panel
        if ($user->hasRole('partner')) {
            return redirect()->route('partner.dashboard');
        }

        // 4. Admin/super_admin — admin panel
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Owner yoki yangi user — biznes yaratish flow'iga ruxsat
        return null;
    }

    /**
     * Backward compatibility — eski chaqiruvlar uchun
     *
     * @deprecated nonOwnerRedirect() ishlating
     */
    protected function teamMemberRedirect($user)
    {
        return $this->nonOwnerRedirect($user);
    }

    /**
     * Show the create business form (for users who already have businesses)
     */
    public function newBusiness()
    {
        $user = Auth::user();

        // Faqat hozirgi owner (kamida bitta biznesi bor) qo'shimcha biznes yarata oladi
        if (! $user->businesses()->exists()) {
            if ($redirect = $this->nonOwnerRedirect($user)) {
                return $redirect;
            }

            return redirect()->route('welcome.create-business');
        }

        return Inertia::render('Welcome/CreateBusiness', [
            'isAdditionalBusiness' => true,
        ]);
    }

    /**
     * Store a new business (first time)
     */
    public function storeBusiness(Request $request)
    {
        $user = Auth::user();

        // Server-side guard — POST ham himoyalanadi (UI bypass qilinsa ham)
        if ($redirect = $this->nonOwnerRedirect($user)) {
            return $redirect;
        }

        return $this->createAndStoreBusiness($request);
    }

    /**
     * Store a new business (additional)
     */
    public function storeNewBusiness(Request $request)
    {
        $user = Auth::user();

        // Faqat hozirgi owner qo'shimcha biznes yarata oladi
        if (! $user->businesses()->exists()) {
            if ($redirect = $this->nonOwnerRedirect($user)) {
                return $redirect;
            }
        }

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
