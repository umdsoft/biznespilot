<?php

namespace App\Http\Controllers;

use App\Services\ChannelAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ChannelAnalyticsController extends Controller
{
    protected ChannelAnalyticsService $analyticsService;

    public function __construct(ChannelAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $channel = $request->get('channel', 'whatsapp');
        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $analytics = $this->analyticsService->getChannelAnalytics(
            $currentBusiness,
            $channel,
            $startDate,
            $endDate
        );

        return Inertia::render('Business/Analytics/Channels', [
            'analytics' => $analytics,
            'selectedChannel' => $channel,
            'dateRange' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
            ],
            'currentBusiness' => [
                'id' => $currentBusiness->id,
                'name' => $currentBusiness->name,
            ],
        ]);
    }

    public function compare(Request $request)
    {
        $user = Auth::user();
        $currentBusiness = session('current_business_id')
            ? $user->businesses()->find(session('current_business_id'))
            : $user->businesses()->first();

        $startDate = $request->get('start_date')
            ? Carbon::parse($request->get('start_date'))
            : now()->subDays(30);
        $endDate = $request->get('end_date')
            ? Carbon::parse($request->get('end_date'))
            : now();

        $comparison = $this->analyticsService->compareChannels(
            $currentBusiness,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'comparison' => $comparison,
        ]);
    }
}
