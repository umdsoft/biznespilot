<?php

namespace App\Http\Controllers\Business;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCurrentBusiness;
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

        if (! $business) {
            return redirect()->route('login');
        }

        // Check if Meta Ads integration exists - redirect to target-analysis
        $metaIntegration = Integration::where('business_id', $business->id)
            ->where('type', 'meta_ads')
            ->whereIn('status', ['connected', 'expired'])
            ->first();

        if ($metaIntegration) {
            // Redirect to proper Meta Ads dashboard
            return redirect()->route('business.target-analysis.index');
        }

        // Fallback to old Facebook channel page if no Meta integration
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

    // Instagram analysis moved to /integrations/instagram (InstagramAnalysisController)
}
