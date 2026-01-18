<?php

namespace App\Http\Controllers;

use App\Models\AdIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class YouTubeAnalyticsController extends Controller
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
        if (! $user) {
            return null;
        }

        return session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();
    }

    /**
     * Get YouTube OAuth URL
     */
    public function getAuthUrl(Request $request)
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return response()->json(['error' => 'Foydalanuvchi topilmadi'], 401);
            }

            $business = $this->getCurrentBusiness();

            if (! $business) {
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

            session(['youtube_oauth_panel_type' => $panelType]);

            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');

            // Detailed validation
            if (empty($clientId) || empty($clientSecret)) {
                \Log::warning('YouTube OAuth not configured', [
                    'has_client_id' => ! empty($clientId),
                    'has_client_secret' => ! empty($clientSecret),
                ]);

                return response()->json([
                    'error' => 'Google OAuth sozlanmagan. .env faylida GOOGLE_CLIENT_ID va GOOGLE_CLIENT_SECRET qo\'shing.',
                    'setup_required' => true,
                ], 500);
            }

            $redirectUri = route('integrations.youtube.callback');

            $url = 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
                'client_id' => $clientId,
                'redirect_uri' => $redirectUri,
                'response_type' => 'code',
                'scope' => 'https://www.googleapis.com/auth/youtube.readonly https://www.googleapis.com/auth/yt-analytics.readonly',
                'access_type' => 'offline',
                'prompt' => 'consent',
            ]);

            \Log::info('YouTube OAuth URL generated', ['redirect_uri' => $redirectUri]);

            return response()->json(['url' => $url]);
        } catch (\Exception $e) {
            \Log::error('YouTube getAuthUrl error', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Xatolik yuz berdi: '.$e->getMessage()], 500);
        }
    }

    /**
     * Handle YouTube OAuth callback
     */
    public function handleCallback(Request $request)
    {
        $code = $request->get('code');
        $error = $request->get('error');
        $panelType = session('youtube_oauth_panel_type', 'business');

        // Determine redirect route based on panel type
        $getRedirectRoute = function ($suffix = '', $params = []) {
            return redirect()->route('integrations.youtube.index', $params);
        };

        if ($error || ! $code) {
            return $getRedirectRoute()->with('error', 'YouTube bilan ulanish bekor qilindi: '.($error ?? 'kod topilmadi'));
        }

        $business = $this->getCurrentBusiness();

        if (! $business) {
            return $getRedirectRoute()->with('error', 'Biznes topilmadi.');
        }

        try {
            // Exchange code for tokens
            $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'redirect_uri' => route('integrations.youtube.callback'),
                'grant_type' => 'authorization_code',
            ]);

            if (! $tokenResponse->successful()) {
                \Log::error('YouTube token exchange failed', ['response' => $tokenResponse->body()]);

                return $getRedirectRoute()->with('error', 'Token olishda xatolik: '.$tokenResponse->body());
            }

            $tokens = $tokenResponse->json();
            $accessToken = $tokens['access_token'] ?? null;
            $refreshToken = $tokens['refresh_token'] ?? null;
            $expiresIn = $tokens['expires_in'] ?? 3600;

            if (! $accessToken) {
                return $getRedirectRoute()->with('error', 'Access token olinmadi.');
            }

            // Get YouTube channel info
            $channelResponse = Http::withToken($accessToken)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics',
                    'mine' => 'true',
                ]);

            $channelName = null;
            $channelId = null;

            if ($channelResponse->successful()) {
                $channelData = $channelResponse->json();
                if (! empty($channelData['items'][0])) {
                    $channel = $channelData['items'][0];
                    $channelId = $channel['id'] ?? null;
                    $channelName = $channel['snippet']['title'] ?? null;
                }
            }

            // Save or update integration
            $business->adIntegrations()->updateOrCreate(
                ['platform' => 'youtube'],
                [
                    'account_id' => $channelId,
                    'account_name' => $channelName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expiresIn),
                    'is_active' => true,
                    'last_synced_at' => now(),
                    'sync_status' => 'completed',
                ]
            );

            return $getRedirectRoute()->with('success', 'YouTube muvaffaqiyatli ulandi!'.($channelName ? " Kanal: {$channelName}" : ''));

        } catch (\Exception $e) {
            \Log::error('YouTube callback error', ['error' => $e->getMessage()]);

            return $getRedirectRoute()->with('error', 'Xatolik yuz berdi: '.$e->getMessage());
        }
    }

    /**
     * Disconnect YouTube integration
     */
    public function disconnect(Request $request)
    {
        $business = $this->getCurrentBusiness();

        if ($business) {
            $business->adIntegrations()
                ->where('platform', 'youtube')
                ->delete();
        }

        return redirect()->back()->with('success', 'YouTube integratsiyasi o\'chirildi!');
    }

    /**
     * Display YouTube Analytics dashboard
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes topilmadi.');
        }

        // Get YouTube integration
        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'youtube')
            ->first();

        $channelData = null;
        $analyticsData = null;
        $previousPeriodData = null;
        $recentVideos = [];
        $trafficSources = [];
        $demographics = [];
        $countries = [];
        $insights = [];
        $recommendations = [];
        $apiErrors = [];

        if ($integration && $integration->is_active) {
            // Check if token is expired and refresh if needed
            if ($integration->isTokenExpired()) {
                if ($integration->refresh_token) {
                    $refreshResult = $this->refreshToken($integration);
                    if (! $refreshResult) {
                        $apiErrors[] = 'Token yangilanmadi. Iltimos, qaytadan ulaning.';
                    } else {
                        $integration->refresh();
                    }
                } else {
                    $apiErrors[] = 'Token muddati tugagan va refresh token yo\'q. Qaytadan ulaning.';
                }
            }

            // Fetch channel data
            $channelResult = $this->fetchChannelData($integration);
            if (is_array($channelResult) && isset($channelResult['error'])) {
                $apiErrors[] = 'Kanal ma\'lumotlari: '.$channelResult['error'];
            } else {
                $channelData = $channelResult;
            }

            // Fetch current period analytics (28 days)
            $analyticsResult = $this->fetchAnalyticsData($integration);
            if (is_array($analyticsResult) && isset($analyticsResult['error'])) {
                $apiErrors[] = 'Analitika: '.$analyticsResult['error'];
            } else {
                $analyticsData = $analyticsResult;
            }

            // Fetch previous period analytics (28 days before) for comparison
            $previousResult = $this->fetchAnalyticsData($integration, 56, 29);
            if (! is_array($previousResult) || ! isset($previousResult['error'])) {
                $previousPeriodData = $previousResult;
            }

            // Fetch recent videos with more details
            $videosResult = $this->fetchRecentVideos($integration, 20);
            if (is_array($videosResult) && isset($videosResult['error'])) {
                $apiErrors[] = 'Videolar: '.$videosResult['error'];
            } else {
                $recentVideos = $videosResult;
            }

            // Fetch channel-level traffic sources
            $trafficResult = $this->fetchChannelTrafficSources($integration);
            if (! is_array($trafficResult) || ! isset($trafficResult['error'])) {
                $trafficSources = $trafficResult;
            }

            // Fetch channel-level demographics
            $demographicsResult = $this->fetchChannelDemographics($integration);
            if (! is_array($demographicsResult) || ! isset($demographicsResult['error'])) {
                $demographics = $demographicsResult;
            }

            // Fetch channel-level countries
            $countriesResult = $this->fetchChannelCountries($integration);
            if (! is_array($countriesResult) || ! isset($countriesResult['error'])) {
                $countries = $countriesResult;
            }

            // Generate insights and recommendations
            $insights = $this->generateInsights($analyticsData, $previousPeriodData, $recentVideos, $channelData);
            $recommendations = $this->generateRecommendations($analyticsData, $previousPeriodData, $recentVideos, $trafficSources, $demographics);
        }

        return Inertia::render('Shared/YouTubeAnalytics/Index', [
            'panelType' => $this->getPanelType($request),
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
            'integration' => $integration ? [
                'id' => $integration->id,
                'is_connected' => $integration->is_active,
                'channel_name' => $integration->account_name,
                'channel_id' => $integration->account_id,
                'last_synced_at' => $integration->last_synced_at,
                'token_expires_at' => $integration->token_expires_at,
                'is_token_expired' => $integration->isTokenExpired(),
            ] : null,
            'channelData' => $channelData,
            'analyticsData' => $analyticsData,
            'previousPeriodData' => $previousPeriodData,
            'recentVideos' => $recentVideos,
            'trafficSources' => $trafficSources,
            'demographics' => $demographics,
            'countries' => $countries,
            'insights' => $insights,
            'recommendations' => $recommendations,
            'apiErrors' => $apiErrors,
        ]);
    }

    /**
     * Refresh YouTube access token
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
                $tokens = $response->json();
                $integration->update([
                    'access_token' => $tokens['access_token'],
                    'token_expires_at' => now()->addSeconds($tokens['expires_in'] ?? 3600),
                ]);

                return true;
            }

            \Log::error('YouTube token refresh failed: '.$response->body());

            return false;
        } catch (\Exception $e) {
            \Log::error('YouTube token refresh failed: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Fetch YouTube channel data
     */
    private function fetchChannelData(AdIntegration $integration)
    {
        try {
            $response = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'snippet,statistics,brandingSettings',
                    'mine' => 'true',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['items'][0])) {
                    $channel = $data['items'][0];

                    return [
                        'id' => $channel['id'],
                        'title' => $channel['snippet']['title'] ?? null,
                        'description' => $channel['snippet']['description'] ?? null,
                        'thumbnail' => $channel['snippet']['thumbnails']['default']['url'] ?? null,
                        'thumbnail_medium' => $channel['snippet']['thumbnails']['medium']['url'] ?? null,
                        'subscribers' => (int) ($channel['statistics']['subscriberCount'] ?? 0),
                        'views' => (int) ($channel['statistics']['viewCount'] ?? 0),
                        'videos' => (int) ($channel['statistics']['videoCount'] ?? 0),
                        'country' => $channel['snippet']['country'] ?? null,
                        'created_at' => $channel['snippet']['publishedAt'] ?? null,
                    ];
                }

                return ['error' => 'Kanal topilmadi'];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();
            \Log::error('YouTube channel data fetch failed: '.$errorMessage);

            return ['error' => $errorMessage];
        } catch (\Exception $e) {
            \Log::error('YouTube channel data fetch failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch YouTube Analytics data
     */
    private function fetchAnalyticsData(AdIntegration $integration, int $daysAgo = 28, int $daysAgoEnd = 0)
    {
        try {
            // Get analytics for specified period
            $endDate = now()->subDays($daysAgoEnd)->format('Y-m-d');
            $startDate = now()->subDays($daysAgo)->format('Y-m-d');

            $response = Http::withToken($integration->access_token)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==MINE',
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,subscribersGained,subscribersLost,likes,dislikes,comments,shares',
                    'dimensions' => 'day',
                    'sort' => 'day',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['rows'] ?? [];

                // Calculate totals and daily data
                $totals = [
                    'views' => 0,
                    'watchTime' => 0,
                    'avgViewDuration' => 0,
                    'subscribersGained' => 0,
                    'subscribersLost' => 0,
                    'likes' => 0,
                    'dislikes' => 0,
                    'comments' => 0,
                    'shares' => 0,
                ];

                $dailyData = [];

                foreach ($rows as $row) {
                    $totals['views'] += $row[1] ?? 0;
                    $totals['watchTime'] += $row[2] ?? 0;
                    $totals['subscribersGained'] += $row[4] ?? 0;
                    $totals['subscribersLost'] += $row[5] ?? 0;
                    $totals['likes'] += $row[6] ?? 0;
                    $totals['dislikes'] += $row[7] ?? 0;
                    $totals['comments'] += $row[8] ?? 0;
                    $totals['shares'] += $row[9] ?? 0;

                    $dailyData[] = [
                        'date' => $row[0],
                        'views' => $row[1] ?? 0,
                        'watchTime' => $row[2] ?? 0,
                        'avgViewDuration' => $row[3] ?? 0,
                        'subscribersGained' => $row[4] ?? 0,
                        'likes' => $row[6] ?? 0,
                        'comments' => $row[8] ?? 0,
                    ];
                }

                // Calculate average view duration
                if (count($rows) > 0) {
                    $totals['avgViewDuration'] = array_sum(array_column($rows, 3)) / count($rows);
                }

                return [
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                    'totals' => $totals,
                    'daily' => $dailyData,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();
            \Log::error('YouTube analytics fetch failed: '.$errorMessage);

            return ['error' => $errorMessage];
        } catch (\Exception $e) {
            \Log::error('YouTube analytics fetch failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch recent videos
     */
    private function fetchRecentVideos(AdIntegration $integration, int $maxResults = 10)
    {
        try {
            // Get uploads playlist ID first
            $channelResponse = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/youtube/v3/channels', [
                    'part' => 'contentDetails',
                    'mine' => 'true',
                ]);

            if (! $channelResponse->successful()) {
                $errorData = $channelResponse->json();
                $errorMessage = $errorData['error']['message'] ?? $channelResponse->body();

                return ['error' => $errorMessage];
            }

            $channelData = $channelResponse->json();
            $uploadsPlaylistId = $channelData['items'][0]['contentDetails']['relatedPlaylists']['uploads'] ?? null;

            if (! $uploadsPlaylistId) {
                return []; // No videos yet
            }

            // Get recent videos from uploads playlist
            $videosResponse = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/youtube/v3/playlistItems', [
                    'part' => 'snippet,contentDetails',
                    'playlistId' => $uploadsPlaylistId,
                    'maxResults' => min($maxResults, 50),
                ]);

            if (! $videosResponse->successful()) {
                $errorData = $videosResponse->json();
                $errorMessage = $errorData['error']['message'] ?? $videosResponse->body();

                return ['error' => $errorMessage];
            }

            $videosData = $videosResponse->json();
            $videoIds = array_map(function ($item) {
                return $item['contentDetails']['videoId'] ?? null;
            }, $videosData['items'] ?? []);

            $videoIds = array_filter($videoIds);

            if (empty($videoIds)) {
                return []; // No videos
            }

            // Get video statistics and content details (for duration)
            $statsResponse = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'statistics,snippet,contentDetails',
                    'id' => implode(',', $videoIds),
                ]);

            if (! $statsResponse->successful()) {
                $errorData = $statsResponse->json();
                $errorMessage = $errorData['error']['message'] ?? $statsResponse->body();

                return ['error' => $errorMessage];
            }

            $statsData = $statsResponse->json();
            $videos = [];

            foreach ($statsData['items'] ?? [] as $video) {
                // Parse duration
                $duration = $video['contentDetails']['duration'] ?? 'PT0S';
                $durationSeconds = 0;
                try {
                    $interval = new \DateInterval($duration);
                    $durationSeconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
                } catch (\Exception $e) {
                    $durationSeconds = 0;
                }

                $videos[] = [
                    'id' => $video['id'],
                    'title' => $video['snippet']['title'] ?? '',
                    'description' => \Str::limit($video['snippet']['description'] ?? '', 100),
                    'thumbnail' => $video['snippet']['thumbnails']['medium']['url'] ?? null,
                    'published_at' => $video['snippet']['publishedAt'] ?? null,
                    'views' => (int) ($video['statistics']['viewCount'] ?? 0),
                    'likes' => (int) ($video['statistics']['likeCount'] ?? 0),
                    'comments' => (int) ($video['statistics']['commentCount'] ?? 0),
                    'duration' => $durationSeconds,
                    'duration_formatted' => $this->formatDuration($durationSeconds),
                ];
            }

            return $videos;
        } catch (\Exception $e) {
            \Log::error('YouTube videos fetch failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Sync YouTube data manually
     */
    public function sync()
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->back()->with('error', 'Biznes topilmadi.');
        }

        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'youtube')
            ->first();

        if (! $integration) {
            return redirect()->back()->with('error', 'YouTube integratsiyasi topilmadi.');
        }

        // Update last synced time
        $integration->update(['last_synced_at' => now()]);

        return redirect()->back()->with('success', 'YouTube ma\'lumotlari yangilandi!');
    }

    /**
     * Display individual video analytics
     */
    public function videoDetail(Request $request, $videoId)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        if (! $currentBusiness) {
            return redirect()->route('business.index')
                ->with('error', 'Biznes topilmadi.');
        }

        $integration = $currentBusiness->adIntegrations()
            ->where('platform', 'youtube')
            ->first();

        if (! $integration || ! $integration->is_active) {
            return redirect()->route('business.youtube-analytics.index')
                ->with('error', 'YouTube integratsiyasi topilmadi.');
        }

        // Refresh token if needed
        if ($integration->isTokenExpired() && $integration->refresh_token) {
            $this->refreshToken($integration);
            $integration->refresh();
        }

        $videoData = null;
        $videoAnalytics = null;
        $apiErrors = [];

        // Fetch video details
        $videoResult = $this->fetchVideoDetails($integration, $videoId);
        if (is_array($videoResult) && isset($videoResult['error'])) {
            $apiErrors[] = 'Video ma\'lumotlari: '.$videoResult['error'];
        } else {
            $videoData = $videoResult;
        }

        // Fetch video analytics
        $analyticsResult = $this->fetchVideoAnalytics($integration, $videoId);
        if (is_array($analyticsResult) && isset($analyticsResult['error'])) {
            $apiErrors[] = 'Video analitikasi: '.$analyticsResult['error'];
        } else {
            $videoAnalytics = $analyticsResult;
        }

        return Inertia::render('Shared/YouTubeAnalytics/VideoDetail', [
            'panelType' => $this->getPanelType($request),
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
            'videoData' => $videoData,
            'videoAnalytics' => $videoAnalytics,
            'apiErrors' => $apiErrors,
        ]);
    }

    /**
     * Fetch individual video details
     */
    private function fetchVideoDetails(AdIntegration $integration, string $videoId)
    {
        try {
            $response = Http::withToken($integration->access_token)
                ->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet,statistics,contentDetails',
                    'id' => $videoId,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                if (! empty($data['items'][0])) {
                    $video = $data['items'][0];

                    // Parse duration
                    $duration = $video['contentDetails']['duration'] ?? 'PT0S';
                    $interval = new \DateInterval($duration);
                    $durationSeconds = ($interval->h * 3600) + ($interval->i * 60) + $interval->s;

                    return [
                        'id' => $video['id'],
                        'title' => $video['snippet']['title'] ?? '',
                        'description' => $video['snippet']['description'] ?? '',
                        'thumbnail' => $video['snippet']['thumbnails']['high']['url'] ?? $video['snippet']['thumbnails']['medium']['url'] ?? null,
                        'thumbnail_maxres' => $video['snippet']['thumbnails']['maxres']['url'] ?? $video['snippet']['thumbnails']['high']['url'] ?? null,
                        'published_at' => $video['snippet']['publishedAt'] ?? null,
                        'channel_title' => $video['snippet']['channelTitle'] ?? '',
                        'tags' => $video['snippet']['tags'] ?? [],
                        'category_id' => $video['snippet']['categoryId'] ?? null,
                        'duration' => $durationSeconds,
                        'duration_formatted' => $this->formatDuration($durationSeconds),
                        'definition' => $video['contentDetails']['definition'] ?? 'sd',
                        'views' => (int) ($video['statistics']['viewCount'] ?? 0),
                        'likes' => (int) ($video['statistics']['likeCount'] ?? 0),
                        'comments' => (int) ($video['statistics']['commentCount'] ?? 0),
                        'favorites' => (int) ($video['statistics']['favoriteCount'] ?? 0),
                    ];
                }

                return ['error' => 'Video topilmadi'];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();

            return ['error' => $errorMessage];
        } catch (\Exception $e) {
            \Log::error('YouTube video details fetch failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch video analytics for last 28 days
     */
    private function fetchVideoAnalytics(AdIntegration $integration, string $videoId)
    {
        try {
            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(28)->format('Y-m-d');

            $response = Http::withToken($integration->access_token)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==MINE',
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'metrics' => 'views,estimatedMinutesWatched,averageViewDuration,averageViewPercentage,subscribersGained,likes,dislikes,comments,shares',
                    'dimensions' => 'day',
                    'filters' => 'video=='.$videoId,
                    'sort' => 'day',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $rows = $data['rows'] ?? [];

                $totals = [
                    'views' => 0,
                    'watchTime' => 0,
                    'avgViewDuration' => 0,
                    'avgViewPercentage' => 0,
                    'subscribersGained' => 0,
                    'likes' => 0,
                    'dislikes' => 0,
                    'comments' => 0,
                    'shares' => 0,
                ];

                $dailyData = [];

                foreach ($rows as $row) {
                    $totals['views'] += $row[1] ?? 0;
                    $totals['watchTime'] += $row[2] ?? 0;
                    $totals['subscribersGained'] += $row[5] ?? 0;
                    $totals['likes'] += $row[6] ?? 0;
                    $totals['dislikes'] += $row[7] ?? 0;
                    $totals['comments'] += $row[8] ?? 0;
                    $totals['shares'] += $row[9] ?? 0;

                    $dailyData[] = [
                        'date' => $row[0],
                        'views' => $row[1] ?? 0,
                        'watchTime' => $row[2] ?? 0,
                        'avgViewDuration' => $row[3] ?? 0,
                        'avgViewPercentage' => $row[4] ?? 0,
                        'likes' => $row[6] ?? 0,
                        'comments' => $row[8] ?? 0,
                    ];
                }

                // Calculate averages
                if (count($rows) > 0) {
                    $totals['avgViewDuration'] = array_sum(array_column($rows, 3)) / count($rows);
                    $totals['avgViewPercentage'] = array_sum(array_column($rows, 4)) / count($rows);
                }

                // Get traffic sources
                $trafficResponse = Http::withToken($integration->access_token)
                    ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                        'ids' => 'channel==MINE',
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'metrics' => 'views',
                        'dimensions' => 'insightTrafficSourceType',
                        'filters' => 'video=='.$videoId,
                        'sort' => '-views',
                    ]);

                $trafficSources = [];
                if ($trafficResponse->successful()) {
                    $trafficData = $trafficResponse->json();
                    foreach ($trafficData['rows'] ?? [] as $row) {
                        $trafficSources[] = [
                            'source' => $this->translateTrafficSource($row[0]),
                            'views' => $row[1] ?? 0,
                        ];
                    }
                }

                // Get demographics (age/gender)
                $demographicsResponse = Http::withToken($integration->access_token)
                    ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                        'ids' => 'channel==MINE',
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'metrics' => 'viewerPercentage',
                        'dimensions' => 'ageGroup,gender',
                        'filters' => 'video=='.$videoId,
                        'sort' => '-viewerPercentage',
                    ]);

                $demographics = [];
                if ($demographicsResponse->successful()) {
                    $demoData = $demographicsResponse->json();
                    foreach ($demoData['rows'] ?? [] as $row) {
                        $demographics[] = [
                            'ageGroup' => $row[0],
                            'gender' => $row[1] === 'male' ? 'Erkak' : 'Ayol',
                            'percentage' => round($row[2] ?? 0, 1),
                        ];
                    }
                }

                // Get countries
                $countriesResponse = Http::withToken($integration->access_token)
                    ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                        'ids' => 'channel==MINE',
                        'startDate' => $startDate,
                        'endDate' => $endDate,
                        'metrics' => 'views',
                        'dimensions' => 'country',
                        'filters' => 'video=='.$videoId,
                        'sort' => '-views',
                        'maxResults' => 10,
                    ]);

                $countries = [];
                if ($countriesResponse->successful()) {
                    $countryData = $countriesResponse->json();
                    foreach ($countryData['rows'] ?? [] as $row) {
                        $countries[] = [
                            'code' => $row[0],
                            'name' => $this->getCountryName($row[0]),
                            'views' => $row[1] ?? 0,
                        ];
                    }
                }

                return [
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate,
                    ],
                    'totals' => $totals,
                    'daily' => $dailyData,
                    'trafficSources' => $trafficSources,
                    'demographics' => $demographics,
                    'countries' => $countries,
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();

            return ['error' => $errorMessage];
        } catch (\Exception $e) {
            \Log::error('YouTube video analytics fetch failed: '.$e->getMessage());

            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Format duration in seconds to readable string
     */
    private function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
        }

        return sprintf('%d:%02d', $minutes, $secs);
    }

    /**
     * Translate traffic source to Uzbek
     */
    private function translateTrafficSource(string $source): string
    {
        $translations = [
            'ADVERTISING' => 'Reklama',
            'ANNOTATION' => 'Annotatsiya',
            'CAMPAIGN_CARD' => 'Kampaniya kartasi',
            'END_SCREEN' => 'Oxirgi ekran',
            'EXT_URL' => 'Tashqi havola',
            'HASHTAGS' => 'Heshteglar',
            'NO_LINK_EMBEDDED' => 'Joylashtirilgan (havolasiz)',
            'NO_LINK_OTHER' => 'Boshqa (havolasiz)',
            'NOTIFICATION' => 'Bildirishnoma',
            'PLAYLIST' => 'Pleylist',
            'PROMOTED' => 'Reklama qilingan',
            'RELATED_VIDEO' => 'Tavsiya etilgan video',
            'SHORTS' => 'Shorts',
            'SUBSCRIBER' => 'Obunachi',
            'YT_CHANNEL' => 'YouTube kanal',
            'YT_OTHER_PAGE' => 'YouTube boshqa sahifa',
            'YT_SEARCH' => 'YouTube qidiruv',
            'VIDEO_REMIXES' => 'Video remikslar',
        ];

        return $translations[$source] ?? $source;
    }

    /**
     * Get country name by code
     */
    private function getCountryName(string $code): string
    {
        $countries = [
            'UZ' => "O'zbekiston",
            'RU' => 'Rossiya',
            'KZ' => "Qozog'iston",
            'US' => 'AQSH',
            'TR' => 'Turkiya',
            'DE' => 'Germaniya',
            'GB' => 'Buyuk Britaniya',
            'TJ' => 'Tojikiston',
            'KG' => "Qirg'iziston",
            'AZ' => 'Ozarbayjon',
            'UA' => 'Ukraina',
            'BY' => 'Belarus',
        ];

        return $countries[$code] ?? $code;
    }

    /**
     * Fetch channel-level traffic sources
     */
    private function fetchChannelTrafficSources(AdIntegration $integration)
    {
        try {
            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(28)->format('Y-m-d');

            $response = Http::withToken($integration->access_token)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==MINE',
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'metrics' => 'views,estimatedMinutesWatched',
                    'dimensions' => 'insightTrafficSourceType',
                    'sort' => '-views',
                    'maxResults' => 10,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $sources = [];
                $totalViews = 0;

                foreach ($data['rows'] ?? [] as $row) {
                    $totalViews += $row[1] ?? 0;
                }

                foreach ($data['rows'] ?? [] as $row) {
                    $views = $row[1] ?? 0;
                    $sources[] = [
                        'source' => $this->translateTrafficSource($row[0]),
                        'sourceKey' => $row[0],
                        'views' => $views,
                        'watchTime' => round($row[2] ?? 0),
                        'percentage' => $totalViews > 0 ? round(($views / $totalViews) * 100, 1) : 0,
                    ];
                }

                return $sources;
            }

            return ['error' => 'Traffic sources fetch failed'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch channel-level demographics
     */
    private function fetchChannelDemographics(AdIntegration $integration)
    {
        try {
            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(28)->format('Y-m-d');

            $response = Http::withToken($integration->access_token)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==MINE',
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'metrics' => 'viewerPercentage',
                    'dimensions' => 'ageGroup,gender',
                    'sort' => '-viewerPercentage',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $demographics = [];
                $byAge = [];
                $byGender = ['male' => 0, 'female' => 0];

                foreach ($data['rows'] ?? [] as $row) {
                    $ageGroup = $row[0];
                    $gender = $row[1];
                    $percentage = round($row[2] ?? 0, 1);

                    $demographics[] = [
                        'ageGroup' => $ageGroup,
                        'gender' => $gender === 'male' ? 'Erkak' : 'Ayol',
                        'genderKey' => $gender,
                        'percentage' => $percentage,
                    ];

                    // Aggregate by age
                    if (! isset($byAge[$ageGroup])) {
                        $byAge[$ageGroup] = 0;
                    }
                    $byAge[$ageGroup] += $percentage;

                    // Aggregate by gender
                    $byGender[$gender] += $percentage;
                }

                return [
                    'detailed' => $demographics,
                    'byAge' => $byAge,
                    'byGender' => [
                        'male' => round($byGender['male'], 1),
                        'female' => round($byGender['female'], 1),
                    ],
                    'primaryAge' => ! empty($byAge) ? array_keys($byAge, max($byAge))[0] : null,
                    'primaryGender' => $byGender['male'] > $byGender['female'] ? 'male' : 'female',
                ];
            }

            return ['error' => 'Demographics fetch failed'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Fetch channel-level countries
     */
    private function fetchChannelCountries(AdIntegration $integration)
    {
        try {
            $endDate = now()->format('Y-m-d');
            $startDate = now()->subDays(28)->format('Y-m-d');

            $response = Http::withToken($integration->access_token)
                ->get('https://youtubeanalytics.googleapis.com/v2/reports', [
                    'ids' => 'channel==MINE',
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'metrics' => 'views,estimatedMinutesWatched',
                    'dimensions' => 'country',
                    'sort' => '-views',
                    'maxResults' => 10,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $countries = [];
                $totalViews = 0;

                foreach ($data['rows'] ?? [] as $row) {
                    $totalViews += $row[1] ?? 0;
                }

                foreach ($data['rows'] ?? [] as $row) {
                    $views = $row[1] ?? 0;
                    $countries[] = [
                        'code' => $row[0],
                        'name' => $this->getCountryName($row[0]),
                        'views' => $views,
                        'watchTime' => round($row[2] ?? 0),
                        'percentage' => $totalViews > 0 ? round(($views / $totalViews) * 100, 1) : 0,
                    ];
                }

                return $countries;
            }

            return ['error' => 'Countries fetch failed'];
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Generate insights based on analytics data
     */
    private function generateInsights($current, $previous, $videos, $channel): array
    {
        $insights = [];

        if (! $current || ! isset($current['totals'])) {
            return $insights;
        }

        $currentTotals = $current['totals'];
        $previousTotals = $previous['totals'] ?? null;

        // Calculate growth rates
        $viewsGrowth = $this->calculateGrowth($currentTotals['views'] ?? 0, $previousTotals['views'] ?? 0);
        $watchTimeGrowth = $this->calculateGrowth($currentTotals['watchTime'] ?? 0, $previousTotals['watchTime'] ?? 0);
        $subscribersGrowth = $this->calculateGrowth($currentTotals['subscribersGained'] ?? 0, $previousTotals['subscribersGained'] ?? 0);
        $engagementGrowth = $this->calculateGrowth(
            ($currentTotals['likes'] ?? 0) + ($currentTotals['comments'] ?? 0),
            ($previousTotals['likes'] ?? 0) + ($previousTotals['comments'] ?? 0)
        );

        // Views insight
        $insights[] = [
            'type' => 'views',
            'title' => "Ko'rishlar",
            'value' => $currentTotals['views'] ?? 0,
            'previousValue' => $previousTotals['views'] ?? 0,
            'change' => $viewsGrowth,
            'trend' => $viewsGrowth > 0 ? 'up' : ($viewsGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'eye',
            'color' => $viewsGrowth >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('views', $viewsGrowth),
        ];

        // Watch time insight
        $watchTimeHours = round(($currentTotals['watchTime'] ?? 0) / 60, 1);
        $insights[] = [
            'type' => 'watchTime',
            'title' => 'Tomosha vaqti',
            'value' => $watchTimeHours,
            'unit' => 'soat',
            'previousValue' => round(($previousTotals['watchTime'] ?? 0) / 60, 1),
            'change' => $watchTimeGrowth,
            'trend' => $watchTimeGrowth > 0 ? 'up' : ($watchTimeGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'clock',
            'color' => $watchTimeGrowth >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('watchTime', $watchTimeGrowth),
        ];

        // Subscribers insight
        $netSubscribers = ($currentTotals['subscribersGained'] ?? 0) - ($currentTotals['subscribersLost'] ?? 0);
        $insights[] = [
            'type' => 'subscribers',
            'title' => 'Yangi obunachilar',
            'value' => $currentTotals['subscribersGained'] ?? 0,
            'netValue' => $netSubscribers,
            'lost' => $currentTotals['subscribersLost'] ?? 0,
            'change' => $subscribersGrowth,
            'trend' => $subscribersGrowth > 0 ? 'up' : ($subscribersGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'users',
            'color' => $netSubscribers >= 0 ? 'green' : 'red',
            'description' => $this->getInsightDescription('subscribers', $subscribersGrowth, $netSubscribers),
        ];

        // Engagement insight
        $totalEngagement = ($currentTotals['likes'] ?? 0) + ($currentTotals['comments'] ?? 0) + ($currentTotals['shares'] ?? 0);
        $engagementRate = ($currentTotals['views'] ?? 0) > 0
            ? round(($totalEngagement / $currentTotals['views']) * 100, 2)
            : 0;

        $insights[] = [
            'type' => 'engagement',
            'title' => 'Engagement',
            'value' => $engagementRate,
            'unit' => '%',
            'likes' => $currentTotals['likes'] ?? 0,
            'comments' => $currentTotals['comments'] ?? 0,
            'shares' => $currentTotals['shares'] ?? 0,
            'change' => $engagementGrowth,
            'trend' => $engagementGrowth > 0 ? 'up' : ($engagementGrowth < 0 ? 'down' : 'stable'),
            'icon' => 'heart',
            'color' => $engagementRate >= 3 ? 'green' : ($engagementRate >= 1 ? 'yellow' : 'red'),
            'description' => $this->getEngagementDescription($engagementRate),
        ];

        // Average view duration insight
        $avgDuration = $currentTotals['avgViewDuration'] ?? 0;
        $insights[] = [
            'type' => 'avgDuration',
            'title' => "O'rtacha tomosha",
            'value' => $avgDuration,
            'formatted' => $this->formatDuration((int) $avgDuration),
            'icon' => 'play',
            'color' => $avgDuration >= 60 ? 'green' : ($avgDuration >= 30 ? 'yellow' : 'red'),
            'description' => $this->getDurationDescription($avgDuration),
        ];

        // Top performing video
        if (! empty($videos)) {
            $topVideo = collect($videos)->sortByDesc('views')->first();
            if ($topVideo) {
                $insights[] = [
                    'type' => 'topVideo',
                    'title' => 'Eng ko\'p ko\'rilgan',
                    'video' => $topVideo,
                    'icon' => 'trophy',
                    'color' => 'purple',
                    'description' => "Bu oyda eng ko'p ko'rilgan video",
                ];
            }
        }

        return $insights;
    }

    /**
     * Generate recommendations based on analytics
     */
    private function generateRecommendations($current, $previous, $videos, $trafficSources, $demographics): array
    {
        $recommendations = [];

        if (! $current || ! isset($current['totals'])) {
            return $recommendations;
        }

        $currentTotals = $current['totals'];
        $previousTotals = $previous['totals'] ?? null;

        // Calculate metrics
        $viewsGrowth = $this->calculateGrowth($currentTotals['views'] ?? 0, $previousTotals['views'] ?? 0);
        $avgDuration = $currentTotals['avgViewDuration'] ?? 0;
        $engagementRate = ($currentTotals['views'] ?? 0) > 0
            ? (($currentTotals['likes'] ?? 0) + ($currentTotals['comments'] ?? 0)) / $currentTotals['views'] * 100
            : 0;

        // Recommendation 1: Based on view trends
        if ($viewsGrowth < -10) {
            $recommendations[] = [
                'priority' => 'high',
                'icon' => 'trending-down',
                'color' => 'red',
                'title' => "Ko'rishlar kamaymoqda",
                'description' => "Oxirgi 28 kunda ko'rishlar ".abs(round($viewsGrowth))."% ga kamaydi. Ko'proq video joylash va SEO optimizatsiya qilish tavsiya etiladi.",
                'actions' => [
                    'Haftalik kontent rejasini tuzing',
                    'Video sarlavha va tavsiflarni optimizatsiya qiling',
                    'Trending mavzularda video tayyorlang',
                ],
            ];
        } elseif ($viewsGrowth > 20) {
            $recommendations[] = [
                'priority' => 'info',
                'icon' => 'trending-up',
                'color' => 'green',
                'title' => "Ajoyib o'sish!",
                'description' => "Ko'rishlar ".round($viewsGrowth)."% ga o'sdi. Bu momentumni davom ettiring!",
                'actions' => [
                    'Muvaffaqiyatli videolar formatini takrorlang',
                    'Auditoriya bilan faol muloqot qiling',
                    'Ko\'proq content joylashni davom ettiring',
                ],
            ];
        }

        // Recommendation 2: Based on average view duration
        if ($avgDuration < 30) {
            $recommendations[] = [
                'priority' => 'high',
                'icon' => 'clock',
                'color' => 'orange',
                'title' => 'Tomosha vaqti past',
                'description' => "O'rtacha tomosha vaqti ".round($avgDuration).' soniya. Bu YouTube algoritmi uchun yaxshi emas.',
                'actions' => [
                    'Video boshlanishini qiziqarli qiling (hook)',
                    'Keraksiz qismlarni olib tashlang',
                    'Videolar davomiyligini qisqartiring',
                    "Qiziqarli momentlarni oldindan ko'rsating",
                ],
            ];
        } elseif ($avgDuration >= 120) {
            $recommendations[] = [
                'priority' => 'info',
                'icon' => 'clock',
                'color' => 'green',
                'title' => 'Yaxshi tomosha vaqti!',
                'description' => "Auditoriyangiz videolaringizni 2 daqiqadan ko'proq tomosha qilmoqda. Bu YouTube algoritmi uchun juda yaxshi.",
                'actions' => [
                    'Bu formatni davom ettiring',
                    "Uzunroq, chuqurroq kontentlarni sinab ko'ring",
                ],
            ];
        }

        // Recommendation 3: Based on engagement
        if ($engagementRate < 1) {
            $recommendations[] = [
                'priority' => 'medium',
                'icon' => 'message-circle',
                'color' => 'yellow',
                'title' => 'Engagement pastroq',
                'description' => 'Layk va izohlar '.round($engagementRate, 2)."% ni tashkil qiladi. Bu ko'rsatkichni oshirish kerak.",
                'actions' => [
                    'Videolarda savol bering va izoh yozishga chaqiring',
                    "Layk bosishni so'rang",
                    'Izohlarga javob bering',
                    'Konkurslar o\'tkazing',
                ],
            ];
        }

        // Recommendation 4: Based on traffic sources
        if (! empty($trafficSources) && is_array($trafficSources)) {
            $searchTraffic = collect($trafficSources)->firstWhere('sourceKey', 'YT_SEARCH');
            $suggestedTraffic = collect($trafficSources)->firstWhere('sourceKey', 'RELATED_VIDEO');

            if ($searchTraffic && ($searchTraffic['percentage'] ?? 0) < 20) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'icon' => 'search',
                    'color' => 'blue',
                    'title' => 'SEO optimizatsiya qiling',
                    'description' => 'YouTube qidiruvidan kelayotgan trafik atigi '.($searchTraffic['percentage'] ?? 0).'%. SEO orqali yangi auditoriya toping.',
                    'actions' => [
                        "Kalit so'zlarni sarlavhada ishlating",
                        'Tavsif va teglarni optimizatsiya qiling',
                        "Trending kalit so'zlarni toping",
                        'Subtitle/CC qo\'shing',
                    ],
                ];
            }

            if ($suggestedTraffic && ($suggestedTraffic['percentage'] ?? 0) > 30) {
                $recommendations[] = [
                    'priority' => 'info',
                    'icon' => 'zap',
                    'color' => 'purple',
                    'title' => 'Tavsiyalar yaxshi ishlayapti!',
                    'description' => 'Videolaringiz YouTube tavsiyalarida '.($suggestedTraffic['percentage'] ?? 0)."% ko'rinmoqda. Bu ajoyib!",
                    'actions' => [
                        'Pleylistlar yarating',
                        'End screen va kartalardan foydalaning',
                        "O'xshash mavzularda video qiling",
                    ],
                ];
            }
        }

        // Recommendation 5: Based on demographics
        if (! empty($demographics) && is_array($demographics) && isset($demographics['primaryAge'])) {
            $primaryAge = $demographics['primaryAge'];
            $primaryGender = $demographics['primaryGender'] ?? 'male';
            $genderText = $primaryGender === 'male' ? 'erkaklar' : 'ayollar';

            $recommendations[] = [
                'priority' => 'info',
                'icon' => 'users',
                'color' => 'indigo',
                'title' => 'Auditoriyangizni tushunin',
                'description' => "Asosiy auditoriyangiz: {$primaryAge} yoshdagi {$genderText}. Kontentingizni ular uchun optimizatsiya qiling.",
                'actions' => [
                    'Bu auditoriya qiziqadigan mavzularni tanlang',
                    'Ular ishlatadiganda tilni ishlating',
                    'Ularning faol vaqtida video joylang',
                ],
            ];
        }

        // Recommendation 6: Video frequency
        if (! empty($videos)) {
            $videosLast28Days = collect($videos)->filter(function ($video) {
                $publishedAt = $video['published_at'] ?? null;
                if (! $publishedAt) {
                    return false;
                }

                return now()->diffInDays(new \DateTime($publishedAt)) <= 28;
            })->count();

            if ($videosLast28Days < 2) {
                $recommendations[] = [
                    'priority' => 'high',
                    'icon' => 'calendar',
                    'color' => 'red',
                    'title' => "Ko'proq video joylang",
                    'description' => "Oxirgi 28 kunda atigi {$videosLast28Days} ta video joylangandi. Kanal o'sishi uchun haftada kamida 1-2 ta video kerak.",
                    'actions' => [
                        'Haftalik kontent rejasi tuzing',
                        "Oldindan video tayyorlab qo'ying",
                        'Shorts videolar ham joylang',
                    ],
                ];
            } elseif ($videosLast28Days >= 8) {
                $recommendations[] = [
                    'priority' => 'info',
                    'icon' => 'check-circle',
                    'color' => 'green',
                    'title' => 'Faol kontent yaratuvchi!',
                    'description' => "Siz oyiga {$videosLast28Days} ta video joylayapsiz. Bu juda yaxshi davomiylik!",
                    'actions' => [
                        'Sifatni saqlab qoling',
                        'Eng yaxshi ishlagan formatlarni aniqlang',
                    ],
                ];
            }
        }

        // Sort by priority
        $priorityOrder = ['high' => 0, 'medium' => 1, 'info' => 2];
        usort($recommendations, function ($a, $b) use ($priorityOrder) {
            return ($priorityOrder[$a['priority']] ?? 3) <=> ($priorityOrder[$b['priority']] ?? 3);
        });

        return array_slice($recommendations, 0, 5); // Return top 5 recommendations
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
    private function getInsightDescription(string $type, float $change, $extra = null): string
    {
        $direction = $change > 0 ? 'oshdi' : ($change < 0 ? 'kamaydi' : "o'zgarmadi");
        $absChange = abs(round($change));

        switch ($type) {
            case 'views':
                if ($change > 20) {
                    return "Ajoyib! Ko'rishlar {$absChange}% ga oshdi";
                }
                if ($change > 0) {
                    return "Yaxshi! Ko'rishlar {$absChange}% ga oshdi";
                }
                if ($change < -20) {
                    return "Diqqat! Ko'rishlar {$absChange}% ga kamaydi";
                }
                if ($change < 0) {
                    return "Ko'rishlar {$absChange}% ga kamaydi";
                }

                return "Ko'rishlar barqaror";

            case 'watchTime':
                if ($change > 0) {
                    return "Tomosha vaqti {$absChange}% ga oshdi";
                }
                if ($change < 0) {
                    return "Tomosha vaqti {$absChange}% ga kamaydi";
                }

                return 'Tomosha vaqti barqaror';

            case 'subscribers':
                if ($extra !== null) {
                    $netText = $extra >= 0 ? "+{$extra}" : (string) $extra;

                    return "Sof o'sish: {$netText} obunachi";
                }

                return "Obunachi soni {$direction}";

            default:
                return "O'tgan davrga nisbatan {$absChange}% {$direction}";
        }
    }

    /**
     * Get engagement description
     */
    private function getEngagementDescription(float $rate): string
    {
        if ($rate >= 5) {
            return 'Ajoyib engagement! Auditoriya juda faol';
        }
        if ($rate >= 3) {
            return 'Yaxshi engagement darajasi';
        }
        if ($rate >= 1) {
            return "O'rtacha engagement. Yaxshilash mumkin";
        }

        return "Engagement past. Auditoriya bilan ko'proq muloqot qiling";
    }

    /**
     * Get duration description
     */
    private function getDurationDescription(float $seconds): string
    {
        if ($seconds >= 180) {
            return 'Ajoyib! Tomoshachilar uzoq tomosha qilmoqda';
        }
        if ($seconds >= 60) {
            return 'Yaxshi tomosha vaqti';
        }
        if ($seconds >= 30) {
            return "O'rtacha. Video boshlanishini yaxshilang";
        }

        return "Juda past. Videolar boshlanishiga e'tibor bering";
    }
}
