<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
use App\Models\Business;
use App\Models\MarketingChannel;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AIAnalysisController extends Controller
{
    use HasCurrentBusiness;

    public function facebook()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
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

    public function instagram()
    {
        $business = $this->getCurrentBusiness();

        if (!$business) {
            return redirect()->route('login');
        }

        $channel = MarketingChannel::where('business_id', $business->id)
            ->where('type', 'instagram')
            ->first();

        $metrics = [];
        if ($channel) {
            $metrics = $channel->instagramMetrics()
                ->where('metric_date', '>=', now()->subDays(30))
                ->orderBy('metric_date', 'desc')
                ->get();
        }

        return Inertia::render('Marketing/AI/Instagram', [
            'channel' => $channel,
            'metrics' => $metrics,
        ]);
    }

    public function telegram()
    {
        $business = $this->getCurrentBusiness();

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

    public function youtube()
    {
        $business = $this->getCurrentBusiness();

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

    public function googleAds()
    {
        $business = $this->getCurrentBusiness();

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
