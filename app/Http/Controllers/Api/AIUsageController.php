<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AIUsageLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * AI xarajat kuzatuvi API.
 */
class AIUsageController extends Controller
{
    /**
     * GET /api/v1/ai-usage/summary — oylik xulosa
     */
    public function summary(Request $request): JsonResponse
    {
        $business = Auth::user()->business;
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 422);
        }

        $month = $request->input('month', now()->format('Y-m'));

        $stats = AIUsageLog::where('business_id', $business->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
            ->selectRaw("
                COUNT(*) as total_requests,
                SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits,
                SUM(tokens_input) as total_input_tokens,
                SUM(tokens_output) as total_output_tokens,
                SUM(cost_usd) as total_cost_usd
            ")
            ->first();

        // Model bo'yicha taqsimot
        $byModel = AIUsageLog::where('business_id', $business->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
            ->where('cache_hit', false)
            ->select('model')
            ->selectRaw('COUNT(*) as count, SUM(cost_usd) as cost')
            ->groupBy('model')
            ->get();

        // Agent bo'yicha taqsimot
        $byAgent = AIUsageLog::where('business_id', $business->id)
            ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$month])
            ->select('agent_type')
            ->selectRaw('COUNT(*) as count, SUM(cost_usd) as cost')
            ->groupBy('agent_type')
            ->get();

        return response()->json([
            'success' => true,
            'month' => $month,
            'summary' => [
                'total_requests' => (int) $stats->total_requests,
                'cache_hits' => (int) $stats->cache_hits,
                'cache_hit_rate' => $stats->total_requests > 0
                    ? round(($stats->cache_hits / $stats->total_requests) * 100, 1)
                    : 0,
                'total_input_tokens' => (int) $stats->total_input_tokens,
                'total_output_tokens' => (int) $stats->total_output_tokens,
                'total_cost_usd' => round((float) $stats->total_cost_usd, 4),
            ],
            'by_model' => $byModel,
            'by_agent' => $byAgent,
        ]);
    }

    /**
     * GET /api/v1/ai-usage/daily — kunlik taqsimot
     */
    public function daily(Request $request): JsonResponse
    {
        $business = Auth::user()->business;
        if (!$business) {
            return response()->json(['success' => false, 'message' => 'Biznes topilmadi'], 422);
        }

        $days = $request->input('days', 30);

        $daily = AIUsageLog::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw("
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(tokens_input + tokens_output) as total_tokens,
                SUM(cost_usd) as cost_usd,
                SUM(CASE WHEN cache_hit = 1 THEN 1 ELSE 0 END) as cache_hits
            ")
            ->groupByRaw('DATE(created_at)')
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $daily,
        ]);
    }
}
