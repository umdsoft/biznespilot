<?php

namespace App\Services\Telephony\Utel;

use App\Models\CallLog;
use App\Models\UtelAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * UTEL Statistics Service — balans, summary, daily stats.
 */
class UtelStatisticsService
{
    public function __construct(
        private UtelAuthService $auth,
    ) {}

    /**
     * Account balansini olish
     */
    public function getBalance(UtelAccount $account): array
    {
        $token = $this->auth->getValidToken($account);
        if (!$token) {
            return ['success' => false, 'error' => 'Token olib bo\'lmadi'];
        }

        try {
            $response = Http::timeout(15)
                ->withToken($token)
                ->accept('application/json')
                ->get($account->getApiBaseUrl() . '/v1/balance');

            if (!$response->successful()) {
                return ['success' => false, 'error' => 'API xato: ' . $response->status()];
            }

            $data = $response->json();
            $balance = $data['result']['balance'] ?? $data['balance'] ?? 0;
            $currency = $data['result']['currency'] ?? $data['currency'] ?? 'UZS';

            $account->update(['balance' => (int) $balance, 'currency' => $currency]);

            return ['success' => true, 'balance' => $balance, 'currency' => $currency];
        } catch (\Exception $e) {
            Log::error('UtelStats balance xato', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Biznes uchun call statistikasi (mahalliy DB dan)
     */
    public function getCallStats(string $businessId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $stats = DB::table('call_logs')
            ->where('business_id', $businessId)
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN direction = "incoming" THEN 1 ELSE 0 END) as incoming,
                SUM(CASE WHEN direction = "outgoing" THEN 1 ELSE 0 END) as outgoing,
                SUM(CASE WHEN status = "missed" THEN 1 ELSE 0 END) as missed,
                SUM(CASE WHEN status = "answered" THEN 1 ELSE 0 END) as answered,
                SUM(duration) as total_duration,
                AVG(CASE WHEN status = "answered" THEN duration ELSE NULL END) as avg_duration,
                COUNT(DISTINCT lead_id) as unique_leads
            ')
            ->first();

        $answerRate = $stats->total > 0 ? round($stats->answered / $stats->total * 100, 1) : 0;

        return [
            'period' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'total' => (int) $stats->total,
            'incoming' => (int) $stats->incoming,
            'outgoing' => (int) $stats->outgoing,
            'missed' => (int) $stats->missed,
            'answered' => (int) $stats->answered,
            'answer_rate' => $answerRate,
            'total_duration_min' => round(($stats->total_duration ?? 0) / 60),
            'avg_duration_sec' => round($stats->avg_duration ?? 0),
            'unique_leads' => (int) $stats->unique_leads,
        ];
    }

    /**
     * Bugungi statistika
     */
    public function getTodayStats(string $businessId): array
    {
        return $this->getCallStats($businessId, now()->startOfDay(), now()->endOfDay());
    }

    /**
     * Operator bo'yicha statistika
     */
    public function getByOperator(string $businessId, int $days = 30): array
    {
        $since = now()->subDays($days);

        return DB::table('call_logs as cl')
            ->leftJoin('users as u', 'cl.user_id', '=', 'u.id')
            ->where('cl.business_id', $businessId)
            ->whereNotNull('cl.user_id')
            ->where('cl.created_at', '>=', $since)
            ->select('cl.user_id', 'u.name as operator_name')
            ->selectRaw('
                COUNT(*) as calls,
                SUM(CASE WHEN cl.status = "answered" THEN 1 ELSE 0 END) as answered,
                SUM(CASE WHEN cl.status = "missed" THEN 1 ELSE 0 END) as missed,
                SUM(cl.duration) as total_duration
            ')
            ->groupBy('cl.user_id', 'u.name')
            ->orderByDesc('calls')
            ->get()
            ->toArray();
    }
}
