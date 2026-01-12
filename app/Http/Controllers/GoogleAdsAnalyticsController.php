<?php

namespace App\Http\Controllers;

use App\Models\AdIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class GoogleAdsAnalyticsController extends Controller
{
    /**
     * Determine panel type from request
     */
    protected function getPanelType(Request $request): string
    {
        $prefix = $request->route()->getPrefix();

        if (str_contains($prefix, 'marketing')) {
            return 'marketing';
        }
        if (str_contains($prefix, 'finance')) {
            return 'finance';
        }
        if (str_contains($prefix, 'operator')) {
            return 'operator';
        }
        if (str_contains($prefix, 'saleshead')) {
            return 'saleshead';
        }

        // For /integrations route, check referer
        $referer = $request->headers->get('referer', '');
        if (str_contains($referer, '/marketing')) {
            return 'marketing';
        }
        if (str_contains($referer, '/finance')) {
            return 'finance';
        }
        if (str_contains($referer, '/operator')) {
            return 'operator';
        }
        if (str_contains($referer, '/saleshead')) {
            return 'saleshead';
        }

        return 'business';
    }

    /**
     * Get current business
     */
    protected function getCurrentBusiness()
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }
        return session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();
    }

    /**
     * Get Google Ads OAuth URL
     */
    public function getAuthUrl(Request $request)
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json(['error' => 'Foydalanuvchi topilmadi'], 401);
            }

            $business = $this->getCurrentBusiness();

            if (!$business) {
                return response()->json(['error' => 'Biznes topilmadi'], 404);
            }

            // Store panel type in session for callback
            $referer = $request->headers->get('referer', '');
            $panelType = 'business';
            if (str_contains($referer, '/marketing')) {
                $panelType = 'marketing';
            } elseif (str_contains($referer, '/finance')) {
                $panelType = 'finance';
            } elseif (str_contains($referer, '/operator')) {
                $panelType = 'operator';
            } elseif (str_contains($referer, '/saleshead')) {
                $panelType = 'saleshead';
            }

            session(['google_ads_oauth_panel_type' => $panelType]);

            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');

            // Detailed validation
            if (empty($clientId) || empty($clientSecret)) {
                \Log::warning('Google Ads OAuth not configured', [
                    'has_client_id' => !empty($clientId),
                    'has_client_secret' => !empty($clientSecret),
                ]);
                return response()->json([
                    'error' => 'Google OAuth sozlanmagan. .env faylida GOOGLE_CLIENT_ID va GOOGLE_CLIENT_SECRET qo\'shing.',
                    'setup_required' => true,
                ], 500);
            }

            $redirectUri = route('integrations.google-ads.callback');

            $url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/adwords',
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);

            \Log::info('Google Ads OAuth URL generated', ['redirect_uri' => $redirectUri]);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            \Log::error('Google Ads getAuthUrl error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Xatolik yuz berdi: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle Google Ads OAuth callback
     */
    public function handleCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');
        $panelType = session('google_ads_oauth_panel_type', 'business');

        // Redirect to integrations page
        $getRedirectRoute = function () {
            return redirect()->route('integrations.google-ads.index');
        };

        if ($error || !$code) {
            return $getRedirectRoute()->with('error', 'Google Ads bilan ulanish bekor qilindi: ' . ($error ?? 'kod topilmadi'));
        }

        $business = $this->getCurrentBusiness();

        if (!$business) {
            return $getRedirectRoute()->with('error', 'Biznes topilmadi.');
        }

        try {
            // Exchange code for tokens
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('integrations.google-ads.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (!$tokenResponse->successful()) {
                \Log::error('Google Ads token exchange failed', ['response' => $tokenResponse->body()]);
                return $getRedirectRoute()->with('error', 'Token olishda xatolik: ' . $tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 3600;

            if (!$accessToken) {
                return $getRedirectRoute()->with('error', 'Access token olinmadi.');
            }

            // Get Google user info for account name
            $userInfoResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/oauth2/v2/userinfo');

            $accountName = null;
            $accountId = null;

            if ($userInfoResponse->successful()) {
                $userInfo = $userInfoResponse->json();
                $accountName = $userInfo['name'] ?? $userInfo['email'] ?? null;
                $accountId = $userInfo['id'] ?? null;
            }

            // Save or update integration
            $business->adIntegrations()->updateOrCreate(
                ['platform' => 'google_ads'],
                [
                    'account_id' => $accountId,
                    'account_name' => $accountName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'sync_status' => 'completed',
                ]
            );

            return $getRedirectRoute()->with('success', 'Google Ads muvaffaqiyatli ulandi!' . ($accountName ? " Hisob: {$accountName}" : ''));

        } catch (\Exception $e) {
            \Log::error('Google Ads callback error', ['error' => $e->getMessage()]);
            return $getRedirectRoute()->with('error', 'Xatolik yuz berdi: ' . $e->getMessage());
        }
    }

    /**
     * Disconnect Google Ads integration
     */
    public function disconnect(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if ($business) {
            $business->adIntegrations()
                ->where('platform', 'google_ads')
                ->delete();
        }

        return redirect()->back()->with('success', 'Google Ads integratsiyasi o\'chirildi!');
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (!$currentBusiness) {
            return redirect()->route('business.create');
        }

        // Get Google Ads integration
        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'google_ads')
            ->first();

        $accountData = null;
        $campaignsData = [];
        $analyticsData = null;
        $previousPeriodData = null;
        $insights = [];
        $recommendations = [];
        $apiErrors = [];

        if ($integration && $integration->is_active) {
            // Check if token is expired and refresh if needed
            if ($integration->isTokenExpired()) {
                if ($integration->refresh_token) {
                    $refreshResult = $this->refreshToken($integration);
                    if (!$refreshResult) {
                        $apiErrors[] = 'Token yangilanmadi. Iltimos, qaytadan ulaning.';
                    } else {
                        $integration->refresh();
                    }
                } else {
                    $apiErrors[] = 'Token muddati tugagan va refresh token yo\'q. Qaytadan ulaning.';
                }
            }

            // Note: Google Ads API requires developer token and complex setup
            // For now, we'll show mock/demo data structure
            // Real implementation would use Google Ads API client library

            $analyticsData = $this->getMockAnalyticsData();
            $previousPeriodData = $this->getMockPreviousPeriodData();
            $campaignsData = $this->getMockCampaignsData();

            // Generate insights and recommendations
            $insights = $this->generateInsights($analyticsData, $previousPeriodData, $campaignsData);
            $recommendations = $this->generateRecommendations($analyticsData, $previousPeriodData, $campaignsData);

            // Add notice about API setup
            if (!config('services.google_ads.developer_token')) {
                $apiErrors[] = 'Google Ads Developer Token sozlanmagan. To\'liq ma\'lumotlar uchun developer token kerak.';
            }
        }

        return Inertia::render('Shared/GoogleAdsAnalytics/Index', [
            'panelType' => $this->getPanelType($request),
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
            'integration' => $integration ? [
                'is_active' => $integration->is_active,
                'account_name' => $integration->account_name,
                'account_id' => $integration->account_id,
                'last_synced_at' => $integration->last_synced_at,
                'token_expires_at' => $integration->token_expires_at,
                'is_token_expired' => $integration->isTokenExpired(),
            ] : null,
            'analyticsData' => $analyticsData,
            'previousPeriodData' => $previousPeriodData,
            'campaignsData' => $campaignsData,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'apiErrors' => $apiErrors,
        ]);
    }

    /**
     * Refresh Google access token
     */
    private function refreshToken(AdIntegration $integration): bool
    {
        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'refresh_token' => $integration->refresh_token,
                'grant_type' => 'refresh_token',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $integration->update([
                    'access_token' => $data['access_token'],
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                ]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            \Log::error('Google Ads token refresh failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get mock analytics data (placeholder for real API)
     */
    private function getMockAnalyticsData(): array
    {
        return [
            'totals' => [
                'impressions' => 0,
                'clicks' => 0,
                'cost' => 0,
                'conversions' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'conversionRate' => 0,
                'costPerConversion' => 0,
            ],
            'period' => [
                'start' => now()->subDays(28)->format('Y-m-d'),
                'end' => now()->format('Y-m-d'),
            ],
        ];
    }

    /**
     * Get mock previous period data
     */
    private function getMockPreviousPeriodData(): array
    {
        return [
            'totals' => [
                'impressions' => 0,
                'clicks' => 0,
                'cost' => 0,
                'conversions' => 0,
                'ctr' => 0,
                'cpc' => 0,
                'conversionRate' => 0,
                'costPerConversion' => 0,
            ],
        ];
    }

    /**
     * Get mock campaigns data
     */
    private function getMockCampaignsData(): array
    {
        return [];
    }

    /**
     * Generate insights based on analytics data
     */
    private function generateInsights($current, $previous, $campaigns): array
    {
        $insights = [];

        if (!$current || !isset($current['totals'])) {
            return $insights;
        }

        $currentTotals = $current['totals'];
        $previousTotals = $previous['totals'] ?? null;

        // Calculate growth rates
        $impressionsGrowth = $this->calculateGrowth($currentTotals['impressions'] ?? 0, $previousTotals['impressions'] ?? 0);
        $clicksGrowth = $this->calculateGrowth($currentTotals['clicks'] ?? 0, $previousTotals['clicks'] ?? 0);
        $costGrowth = $this->calculateGrowth($currentTotals['cost'] ?? 0, $previousTotals['cost'] ?? 0);
        $conversionsGrowth = $this->calculateGrowth($currentTotals['conversions'] ?? 0, $previousTotals['conversions'] ?? 0);

        // Impressions insight
        $insights[] = [
            'type' => 'impressions',
            'title' => "Ko'rishlar",
            'value' => $currentTotals['impressions'] ?? 0,
            'previousValue' => $previousTotals['impressions'] ?? 0,
            'change' => $impressionsGrowth,
            'trend' => $impressionsGrowth > 0 ? 'up' : ($impressionsGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'eye',
            'color' => $impressionsGrowth >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('impressions', $impressionsGrowth),
        ];

        // Clicks insight
        $insights[] = [
            'type' => 'clicks',
            'title' => 'Kliklar',
            'value' => $currentTotals['clicks'] ?? 0,
            'previousValue' => $previousTotals['clicks'] ?? 0,
            'change' => $clicksGrowth,
            'trend' => $clicksGrowth > 0 ? 'up' : ($clicksGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'cursor',
            'color' => $clicksGrowth >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('clicks', $clicksGrowth),
        ];

        // Cost insight
        $insights[] = [
            'type' => 'cost',
            'title' => 'Xarajat',
            'value' => $currentTotals['cost'] ?? 0,
            'unit' => "so'm",
            'previousValue' => $previousTotals['cost'] ?? 0,
            'change' => $costGrowth,
            'trend' => $costGrowth > 0 ? 'up' : ($costGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'currency',
            'color' => $costGrowth <= 0 ? 'green' : 'yellow',
            'description' => $this->getInsightDescription('cost', $costGrowth),
        ];

        // CTR insight
        $ctr = $currentTotals['ctr'] ?? 0;
        $insights[] = [
            'type' => 'ctr',
            'title' => 'CTR',
            'value' => $ctr,
            'unit' => '%',
            'icon' => 'percentage',
            'color' => $ctr >= 2 ? 'green' : ($ctr >= 1 ? 'yellow' : 'red'),
            'description' => $this->getCTRDescription($ctr),
        ];

        // Conversions insight
        $insights[] = [
            'type' => 'conversions',
            'title' => 'Konversiyalar',
            'value' => $currentTotals['conversions'] ?? 0,
            'previousValue' => $previousTotals['conversions'] ?? 0,
            'change' => $conversionsGrowth,
            'trend' => $conversionsGrowth > 0 ? 'up' : ($conversionsGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'check',
            'color' => $conversionsGrowth >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('conversions', $conversionsGrowth),
        ];

        return $insights;
    }

    /**
     * Generate recommendations based on analytics
     */
    private function generateRecommendations($current, $previous, $campaigns): array
    {
        $recommendations = [];

        // No data recommendation
        if (!$current || ($current['totals']['impressions'] ?? 0) == 0) {
            $recommendations[] = [
                'priority' => 'high',
                'icon' => 'alert',
                'color' => 'red',
                'title' => "Ma'lumotlar yo'q",
                'description' => "Google Ads hisobingizda faol kampaniyalar yo'q yoki API to'liq sozlanmagan.",
                'actions' => [
                    'Google Ads hisobingizni tekshiring',
                    'Kamida bitta kampaniya yarating',
                    'Developer token sozlang',
                ],
            ];
            return $recommendations;
        }

        $currentTotals = $current['totals'];
        $previousTotals = $previous['totals'] ?? null;

        // CTR based recommendation
        $ctr = $currentTotals['ctr'] ?? 0;
        if ($ctr < 1) {
            $recommendations[] = [
                'priority' => 'high',
                'icon' => 'trending-down',
                'color' => 'red',
                'title' => "CTR past",
                'description' => "Click-through rate {$ctr}% - bu juda past. Reklama matnlari va targetingni yaxshilash kerak.",
                'actions' => [
                    'Reklama sarlavhalarini yangilang',
                    'Aniqroq kalit so\'zlar tanlang',
                    'Salbiy kalit so\'zlar qo\'shing',
                    'Auditoriya targetingini yaxshilang',
                ],
            ];
        } elseif ($ctr >= 3) {
            $recommendations[] = [
                'priority' => 'info',
                'icon' => 'trending-up',
                'color' => 'green',
                'title' => "Ajoyib CTR!",
                'description' => "CTR {$ctr}% - bu juda yaxshi natija. Bu strategiyani davom ettiring!",
                'actions' => [
                    'Muvaffaqiyatli reklamalarni ko\'paytiring',
                    'A/B testlarni davom ettiring',
                ],
            ];
        }

        // Cost per conversion recommendation
        $costPerConversion = $currentTotals['costPerConversion'] ?? 0;
        if ($costPerConversion > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'icon' => 'currency',
                'color' => 'yellow',
                'title' => "Konversiya narxini optimallashtiring",
                'description' => "Har bir konversiya uchun " . number_format($costPerConversion) . " so'm sarflanyapti.",
                'actions' => [
                    'Past samarali kalit so\'zlarni o\'chiring',
                    'Bid strategiyani optimallashtiring',
                    'Landing page-ni yaxshilang',
                ],
            ];
        }

        // Budget recommendation
        $cost = $currentTotals['cost'] ?? 0;
        $conversions = $currentTotals['conversions'] ?? 0;
        if ($cost > 0 && $conversions == 0) {
            $recommendations[] = [
                'priority' => 'high',
                'icon' => 'alert',
                'color' => 'red',
                'title' => "Konversiya yo'q",
                'description' => "Byudjet sarflanyapti lekin konversiya yo'q. Strategiyani qayta ko'rib chiqing.",
                'actions' => [
                    'Conversion tracking to\'g\'ri ishlayotganini tekshiring',
                    'Landing page-ni optimallashtiring',
                    'Target auditoriyani qayta ko\'rib chiqing',
                    'Reklama matnlarini yangilang',
                ],
            ];
        }

        // Campaigns recommendation
        if (empty($campaigns)) {
            $recommendations[] = [
                'priority' => 'medium',
                'icon' => 'plus',
                'color' => 'blue',
                'title' => "Kampaniyalar yarating",
                'description' => "Faol kampaniyalar topilmadi. Yangi kampaniya yaratib, reklamani boshlang.",
                'actions' => [
                    'Search kampaniya yarating',
                    'Display kampaniya sinab ko\'ring',
                    'Remarketing kampaniya qo\'shing',
                ],
            ];
        }

        // Sort by priority
        $priorityOrder = ['high' => 0, 'medium' => 1, 'info' => 2];
        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 3) <=> ($priorityOrder[$b['priority']] ?? 3);
        });

        return array_slice($recommendations, 0, 5);
    }

    /**
     * Calculate growth percentage
     */
    private function calculateGrowth($current, $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }
        return round((($current - $previous) / $previous) * 100, 1);
    }

    /**
     * Get insight description
     */
    private function getInsightDescription(string $type, float $change): string
    {
        $direction = $change > 0 ? 'oshdi' : ($change < 0 ? 'kamaydi' : "o'zgarmadi");
        $absChange = abs(round($change));

        switch ($type) {
            case 'impressions':
                if ($change > 20) return "Ajoyib! Ko'rishlar {$absChange}% ga oshdi";
                if ($change > 0) return "Yaxshi! Ko'rishlar {$absChange}% ga oshdi";
                if ($change < -20) return "Diqqat! Ko'rishlar {$absChange}% ga kamaydi";
                if ($change < 0) return "Ko'rishlar {$absChange}% ga kamaydi";
                return "Ko'rishlar barqaror";

            case 'clicks':
                if ($change > 0) return "Kliklar {$absChange}% ga oshdi";
                if ($change < 0) return "Kliklar {$absChange}% ga kamaydi";
                return "Kliklar barqaror";

            case 'cost':
                if ($change > 0) return "Xarajat {$absChange}% ga oshdi";
                if ($change < 0) return "Xarajat {$absChange}% ga kamaydi - yaxshi!";
                return "Xarajat barqaror";

            case 'conversions':
                if ($change > 0) return "Konversiyalar {$absChange}% ga oshdi";
                if ($change < 0) return "Konversiyalar {$absChange}% ga kamaydi";
                return "Konversiyalar barqaror";

            default:
                return "O'tgan davrga nisbatan {$absChange}% {$direction}";
        }
    }

    /**
     * Get CTR description
     */
    private function getCTRDescription(float $ctr): string
    {
        if ($ctr >= 5) return "Ajoyib CTR! Reklamalar juda samarali";
        if ($ctr >= 3) return "Yaxshi CTR darajasi";
        if ($ctr >= 2) return "O'rtacha CTR. Yaxshilash mumkin";
        if ($ctr >= 1) return "CTR pastroq. Optimizatsiya kerak";
        return "CTR juda past. Reklamalarni qayta ko'rib chiqing";
    }
}
