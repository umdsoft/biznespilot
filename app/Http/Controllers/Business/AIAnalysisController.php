<?php

namespace App\Http\Controllers\Business;

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

        return Inertia::render('Business/AI/Facebook', [
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

        return Inertia::render('Business/AI/Instagram', [
            'channel' => $channel,
            'metrics' => $metrics,
        ]);
    }
}
