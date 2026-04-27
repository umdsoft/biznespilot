<?php

namespace App\Http\Controllers;

use App\Models\BusinessUser;
use App\Services\PlanDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LandingController extends Controller
{
    /**
     * Supported locales for landing page
     */
    protected array $supportedLocales = ['uz-latn', 'ru'];

    /**
     * Default locale
     */
    protected string $defaultLocale = 'uz-latn';

    /**
     * Show the landing page
     */
    public function index(Request $request)
    {
        // If user is authenticated, redirect to appropriate dashboard
        if (auth()->check()) {
            return $this->redirectAuthenticatedUser();
        }

        return inertia('LandingPage', [
            'plans' => app(PlanDataService::class)->getPublicPlans(),
        ])->withViewData([
            'seoTitle' => "BiznesPilot AI — O'zbekistondagi #1 biznes boshqaruv platformasi",
            'seoDescription' => "Marketing, Sotuv, Moliya, HR — hammasi bir joyda. CRM tizimi, AI yordamchi, Telegram bot integratsiya. 14 kun bepul sinab ko'ring.",
        ]);
    }

    /**
     * Set language preference
     */
    public function setLanguage(Request $request, string $locale)
    {
        // Validate locale
        if (! in_array($locale, $this->supportedLocales)) {
            $locale = $this->defaultLocale;
        }

        // Set cookie with httpOnly: false so JavaScript can read it via document.cookie
        return redirect()->back()->withCookie(
            cookie('landing_locale', $locale, 60 * 24 * 30, '/', null, null, false)
        );
    }

    /**
     * Show the privacy policy page
     */
    public function privacy(Request $request)
    {
        return inertia('PrivacyPolicy');
    }

    /**
     * Show the terms of service page
     */
    public function terms(Request $request)
    {
        return inertia('TermsOfService');
    }

    /**
     * Show the about us page
     */
    public function about(Request $request)
    {
        return inertia('AboutUs')->withViewData([
            'seoTitle' => "Biz haqimizda — BiznesPilot biznes boshqaruv platformasi",
            'seoDescription' => "BiznesPilot — O'zbekiston bizneslari uchun yaratilgan biznes operatsion tizimi. Jamoamiz, missiyamiz va platformamiz haqida batafsil.",
        ]);
    }

    /**
     * Show the data deletion instructions page (Meta requirement)
     */
    public function dataDeletion(Request $request)
    {
        return inertia('DataDeletion');
    }

    /**
     * Show the pricing page
     */
    public function pricing(Request $request)
    {
        return inertia('Pricing', [
            'plans' => app(PlanDataService::class)->getPublicPlans(),
        ])->withViewData([
            'seoTitle' => "Narxlar — BiznesPilot CRM va biznes boshqaruv platformasi",
            'seoDescription' => "BiznesPilot tarif rejalari: Starter, Professional, Enterprise. CRM, marketing, moliya, HR — barchasi bitta platformada. 14 kun bepul.",
        ]);
    }

    /**
     * Show the new landing page (Inertia)
     */
    public function landingPage()
    {
        return inertia('LandingPage', [
            'plans' => app(PlanDataService::class)->getPublicPlans(),
        ]);
    }

    /**
     * Get current locale from cookie or default
     */
    protected function getCurrentLocale(Request $request): string
    {
        $locale = $request->cookie('landing_locale');

        if ($locale && in_array($locale, $this->supportedLocales)) {
            return $locale;
        }

        return $this->defaultLocale;
    }

    /**
     * Get translations for landing page
     */
    protected function getTranslations(string $locale): array
    {
        $path = lang_path("{$locale}/landing.php");

        if (file_exists($path)) {
            return require $path;
        }

        // Fallback to default locale
        $fallbackPath = lang_path("{$this->defaultLocale}/landing.php");
        if (file_exists($fallbackPath)) {
            return require $fallbackPath;
        }

        return [];
    }

    /**
     * Get available locale options
     */
    protected function getLocaleOptions(): array
    {
        return [
            'uz-latn' => [
                'code' => 'uz-latn',
                'name' => "O'zbekcha",
                'flag' => '🇺🇿',
            ],
            'ru' => [
                'code' => 'ru',
                'name' => 'Русский',
                'flag' => '🇷🇺',
            ],
        ];
    }

    /**
     * Redirect authenticated user to appropriate dashboard
     */
    protected function redirectAuthenticatedUser()
    {
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return redirect()->route('admin.dashboard');
        }

        // Team member (xodim) — o'z department panelidan boshlasin
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

        // Partner (hamkor) — alohida panel, biznes yaratish shart emas.
        // Agar user partner roliga ega bo'lsa VA biznes yo'q bo'lsa → partner dashboard.
        // Biznes bor bo'lsa → business dashboard (ikki rolni birga olib boradi).
        if ($user->hasRole('partner') && ! $user->businesses()->exists()) {
            return redirect()->route('partner.dashboard');
        }

        if (! $user->businesses()->exists()) {
            return redirect()->route('welcome.index');
        }

        return redirect()->route('business.dashboard');
    }
}
