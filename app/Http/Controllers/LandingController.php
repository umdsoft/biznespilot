<?php

namespace App\Http\Controllers;

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

        // Get current locale
        $locale = $this->getCurrentLocale($request);
        App::setLocale($locale);

        // Load translations
        $translations = $this->getTranslations($locale);

        return view('landing.index', [
            'locale' => $locale,
            'translations' => $translations,
            'locales' => $this->getLocaleOptions(),
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

        // Store in cookie (30 days)
        Cookie::queue('landing_locale', $locale, 60 * 24 * 30);

        // Redirect back to landing page
        return redirect()->route('landing')->withCookie(
            cookie('landing_locale', $locale, 60 * 24 * 30)
        );
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

        // Check browser preference
        $browserLocale = $request->getPreferredLanguage(['uz', 'ru']);
        if ($browserLocale === 'ru') {
            return 'ru';
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

        if (! $user->businesses()->exists()) {
            return redirect()->route('welcome.index');
        }

        return redirect()->route('business.dashboard');
    }
}
