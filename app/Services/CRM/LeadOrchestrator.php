<?php

namespace App\Services\CRM;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * CRM BRAIN — yagona lead aql.
 *
 * Marketing Orchestrator kabi — barcha CRM ma'lumotlarini yig'adi va sinxron tahlil beradi.
 *
 * Beradi:
 *   - CRM health score
 *   - Today's actions (qaysi lidlarga qachon e'tibor)
 *   - Hot/Warm/Cold leads
 *   - Bottleneck va stale leads
 *   - Cross-module insights (sotuv, marketing bog'liqliklar)
 */
class LeadOrchestrator
{
    private const CACHE_TTL = 600;

    public function __construct(
        private LeadLifecycleTracker $lifecycle,
    ) {}

    /**
     * To'liq CRM snapshot
     */
    public function getSnapshot(string $businessId): array
    {
        return Cache::remember("crm_snapshot:{$businessId}", self::CACHE_TTL, function () use ($businessId) {
            try {
                $stats = $this->getStats($businessId);
                $health = $this->calculateHealth($stats);
                $bottleneck = $this->lifecycle->findBottleneck($businessId);
                $stale = $this->lifecycle->getStaleLeads($businessId, 7);
                $velocity = $this->lifecycle->getConversionVelocity($businessId);

                return [
                    'business_id' => $businessId,
                    'generated_at' => now()->toISOString(),
                    'stats' => $stats,
                    'health' => $health,
                    'bottleneck' => $bottleneck,
                    'stale_leads_count' => count($stale),
                    'stale_leads_top' => array_slice($stale, 0, 5),
                    'conversion_velocity' => $velocity,
                    'priorities' => $this->generatePriorities($stats, $bottleneck, $stale),
                    'today_actions' => $this->todayActions($businessId),
                ];
            } catch (\Exception $e) {
                Log::error('LeadOrchestrator xato', ['error' => $e->getMessage()]);
                return ['error' => $e->getMessage()];
            }
        });
    }

    /**
     * Kunlik briefing
     */
    public function dailyBriefing(string $businessId): array
    {
        $snapshot = $this->getSnapshot($businessId);
        $hour = (int) now()->format('H');
        $greeting = $hour < 12 ? 'Xayrli tong' : ($hour < 18 ? 'Xayrli kun' : 'Xayrli kech');

        return [
            'greeting' => "{$greeting}! CRM sog'ligi: {$snapshot['health']['overall']}/100",
            'health' => $snapshot['health'],
            'today_priorities' => array_slice($snapshot['priorities'] ?? [], 0, 5),
            'hot_leads_count' => $snapshot['stats']['hot_leads'] ?? 0,
            'urgent_callbacks' => $this->getUrgentCallbacks($businessId),
            'stale_warning' => $snapshot['stale_leads_count'] > 10
                ? "⚠️ {$snapshot['stale_leads_count']} ta lid 7+ kun e'tiborsiz"
                : null,
        ];
    }

    /**
     * Statistika yig'ish
     */
    private function getStats(string $businessId): array
    {
        $byStatus = DB::table('leads')
            ->where('business_id', $businessId)
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')
            ->pluck('c', 'status')
            ->toArray();

        $total = array_sum($byStatus);

        // Hot leads (high score)
        $hot = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['won', 'lost'])
            ->where('score', '>=', 70)
            ->count();

        // This week new
        $thisWeekNew = DB::table('leads')
            ->where('business_id', $businessId)
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        // Pipeline value
        $pipelineValue = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['won', 'lost'])
            ->sum('estimated_value') ?? 0;

        // Conversion rate
        $won = $byStatus['won'] ?? 0;
        $conversionRate = $total > 0 ? round($won / $total * 100, 1) : 0;

        // Unassigned
        $unassigned = DB::table('leads')
            ->where('business_id', $businessId)
            ->whereNotIn('status', ['won', 'lost'])
            ->whereNull('assigned_to')
            ->count();

        return [
            'total' => $total,
            'by_status' => $byStatus,
            'won' => $won,
            'lost' => $byStatus['lost'] ?? 0,
            'new' => $byStatus['new'] ?? 0,
            'in_progress' => $total - ($byStatus['won'] ?? 0) - ($byStatus['lost'] ?? 0),
            'hot_leads' => $hot,
            'this_week_new' => $thisWeekNew,
            'pipeline_value' => $pipelineValue,
            'conversion_rate' => $conversionRate,
            'unassigned_count' => $unassigned,
        ];
    }

    /**
     * Health score
     */
    private function calculateHealth(array $stats): array
    {
        // Coverage (hammasi assign qilingan)
        $coverage = $stats['in_progress'] > 0
            ? round((($stats['in_progress'] - $stats['unassigned_count']) / $stats['in_progress']) * 100)
            : 100;

        // Conversion
        $conversion = $stats['conversion_rate'] >= 25 ? 100
            : ($stats['conversion_rate'] >= 15 ? 75
            : ($stats['conversion_rate'] >= 8 ? 50 : 25));

        // Velocity (hot leads ratio)
        $hotRatio = $stats['in_progress'] > 0
            ? ($stats['hot_leads'] / $stats['in_progress']) * 100
            : 0;
        $velocity = min(100, round($hotRatio * 5));

        // Pipeline
        $pipeline = $stats['pipeline_value'] > 50_000_000 ? 100
            : ($stats['pipeline_value'] > 10_000_000 ? 75
            : ($stats['pipeline_value'] > 1_000_000 ? 50 : 25));

        $overall = (int) round(
            $coverage * 0.30
            + $conversion * 0.30
            + $velocity * 0.20
            + $pipeline * 0.20
        );

        return [
            'overall' => $overall,
            'grade' => $this->grade($overall),
            'coverage' => $coverage,
            'conversion' => $conversion,
            'velocity' => $velocity,
            'pipeline' => $pipeline,
        ];
    }

    /**
     * Ustuvor ishlar generatsiya
     */
    private function generatePriorities(array $stats, ?array $bottleneck, array $stale): array
    {
        $priorities = [];

        // Unassigned lidlar
        if ($stats['unassigned_count'] > 0) {
            $priorities[] = [
                'severity' => $stats['unassigned_count'] > 10 ? 'critical' : 'high',
                'title' => "{$stats['unassigned_count']} ta lid biriktirilmagan",
                'description' => 'Ushbu lidlar bilan hech kim ishlamayapti',
                'action' => 'Lidlar > Filter: assign yo\'q',
                'priority_score' => $stats['unassigned_count'] > 10 ? 95 : 75,
            ];
        }

        // Stale leads
        if (count($stale) >= 5) {
            $priorities[] = [
                'severity' => 'high',
                'title' => count($stale) . " ta qotib qolgan lid",
                'description' => 'Lidlar 7+ kun e\'tiborsiz qolgan',
                'action' => 'Lidlar > Stale ko\'rish',
                'priority_score' => 70,
            ];
        }

        // Bottleneck
        if ($bottleneck && $bottleneck['avg_hours'] > 48) {
            $priorities[] = [
                'severity' => 'medium',
                'title' => "Bottleneck: {$bottleneck['stage']}",
                'description' => $bottleneck['message'],
                'action' => 'Pipeline tahlil',
                'priority_score' => 60,
            ];
        }

        // Hot leads kam
        if ($stats['hot_leads'] === 0 && $stats['in_progress'] > 5) {
            $priorities[] = [
                'severity' => 'medium',
                'title' => 'Hot leads yo\'q',
                'description' => 'Yuqori bal (70+) lidlar yo\'q. Lead scoring kerak',
                'action' => 'Lidlarni baholash',
                'priority_score' => 50,
            ];
        }

        usort($priorities, fn($a, $b) => $b['priority_score'] - $a['priority_score']);
        return $priorities;
    }

    /**
     * Bugun bajarilishi kerak vazifalar
     */
    private function todayActions(string $businessId): array
    {
        // Bugun follow-up qilish kerak (qoldirilgan task)
        $todayFollowups = DB::table('todos')
            ->where('business_id', $businessId)
            ->whereDate('due_date', today())
            ->where('status', '!=', 'completed')
            ->count();

        // Bugun missed call'ga qaytarish kerak
        $missedCallbacks = DB::table('call_logs')
            ->where('business_id', $businessId)
            ->whereIn('status', ['missed', 'no_answer'])
            ->whereDate('created_at', '>=', now()->subDays(2))
            ->count();

        return [
            'followups_today' => $todayFollowups,
            'missed_callbacks' => $missedCallbacks,
        ];
    }

    /**
     * Shoshilinch callback'lar
     */
    private function getUrgentCallbacks(string $businessId): int
    {
        return DB::table('call_logs')
            ->where('business_id', $businessId)
            ->whereIn('status', ['missed', 'no_answer'])
            ->where('created_at', '>=', now()->subHours(24))
            ->count();
    }

    private function grade(int $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 75 => 'B',
            $score >= 60 => 'C',
            $score >= 40 => 'D',
            default => 'F',
        };
    }

    /**
     * Cache invalidate
     */
    public function invalidate(string $businessId): void
    {
        Cache::forget("crm_snapshot:{$businessId}");
    }
}
