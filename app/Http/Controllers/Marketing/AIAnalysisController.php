<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\InstagramAccount;
use App\Models\InstagramMedia;
use App\Models\InstagramDailyInsight;
use App\Models\Integration;
use App\Models\MarketingChannel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIAnalysisController extends Controller
{
    use HasCurrentBusiness;

    public function facebook(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        // Check if Meta Ads integration exists - redirect to target-analysis
        $metaIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->whereIn('status', ['connected', 'expired'])
            ->first();

        if ($metaIntegration) {
            // Redirect to proper Meta Ads dashboard (marketing panel)
            return redirect()->route('marketing.target-analysis.index');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('type', 'facebook')
            ->first();

        $metrics = [];
        if ($channel) {
            $metrics = $channel->facebookMetrics()
                ->where('metric_date', '>=', now()->subDays(30))
                ->orderBy('metric_date', 'desc')
                ->get();
        }

        return Inertia::render('Marketing/AI/Facebook', [
            'channel' => $channel,
            'metrics' => $metrics,
        ]);
    }

    public function instagram(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        // Check if Meta/Instagram integration exists
        $instagramIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'instagram')
            ->whereIn('status', ['connected', 'expired'])
            ->first();

        // If no dedicated Instagram integration, check Meta Ads (includes Instagram)
        if (!$instagramIntegration) {
            $metaIntegration = Integration::where('business_id', $business->id)
                ->where('type', 'meta_ads')
                ->whereIn('status', ['connected', 'expired'])
                ->first();

            if (!$metaIntegration) {
                // No integration at all - show connect page
                return Inertia::render('Marketing/AI/Instagram', [
                    'channel' => null,
                    'accounts' => [],
                    'metrics' => [],
                    'recentMedia' => [],
                ]);
            }
        }

        // Get Instagram accounts for this business
        $accounts = InstagramAccount::where('business_id', $business->id)
            ->orderBy('followers_count', 'desc')
            ->get();

        // Get primary account or first account
        $primaryAccount = $accounts->firstWhere('is_primary', true) ?? $accounts->first();

        $metrics = [];
        $recentMedia = [];

        if ($primaryAccount) {
            // Get daily insights for the last 30 days
            $metrics = InstagramDailyInsight::where('account_id', $primaryAccount->id)
                ->where('insight_date', '>=', now()->subDays(30))
                ->orderBy('insight_date', 'desc')
                ->get();

            // Get recent media posts
            $recentMedia = InstagramMedia::where('account_id', $primaryAccount->id)
                ->orderBy('posted_at', 'desc')
                ->take(20)
                ->get();
        }

        // Format channel data for AIChannelPage component
        $channelData = null;
        if ($primaryAccount) {
            $channelData = [
                'id' => $primaryAccount->id,
                'name' => $primaryAccount->name ?? $primaryAccount->username,
                'username' => $primaryAccount->username,
                'metrics' => [
                    'followers_count' => number_format($primaryAccount->followers_count),
                    'following_count' => number_format($primaryAccount->follows_count),
                    'media_count' => number_format($primaryAccount->media_count),
                    'engagement_rate' => '0',
                ],
            ];
        }

        return Inertia::render('Marketing/AI/Instagram', [
            'channel' => $channelData,
            'accounts' => $accounts,
            'primaryAccount' => $primaryAccount,
            'metrics' => $metrics,
            'recentMedia' => $recentMedia,
        ]);
    }

    public function telegram(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('type', 'telegram')
            ->first();

        $metrics = [];
        if ($channel) {
            $metrics = $channel->telegramMetrics()
                ->where('metric_date', '>=', now()->subDays(30))
                ->orderBy('metric_date', 'desc')
                ->get();
        }

        return Inertia::render('Marketing/AI/Telegram', [
            'channel' => $channel,
            'metrics' => $metrics,
        ]);
    }

    public function youtube(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('type', 'youtube')
            ->first();

        return Inertia::render('Marketing/AI/YouTube', [
            'channel' => $channel,
            'metrics' => [],
        ]);
    }

    public function googleAds(Request $request)
    {
        $business = $this->getCurrentBusiness($request);

        if (!$business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('type', 'google_ads')
            ->first();

        $metrics = [];
        if ($channel) {
            $metrics = $channel->googleAdsMetrics()
                ->where('metric_date', '>=', now()->subDays(30))
                ->orderBy('metric_date', 'desc')
                ->get();
        }

        return Inertia::render('Marketing/AI/GoogleAds', [
            'channel' => $channel,
            'metrics' => $metrics,
        ]);
    }
}
