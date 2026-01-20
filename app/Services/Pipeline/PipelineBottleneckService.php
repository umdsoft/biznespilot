<?php

namespace App\Services\Pipeline;

use App\Models\Business;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\SalesAlert;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PipelineBottleneckService
{
    /**
     * Bottleneck larni aniqlash
     */
    public function detectBottlenecks(Business $business): array
    {
        $bottlenecks = [];

        // Har bir stage da necha kun turganini tekshirish
        $stageAnalysis = Lead::where('business_id', $business->id)
            ->whereNotNull('status')
            ->whereNotIn('status', ['won', 'lost']) // Yakuniy stage larni chiqarib tashlash
            ->whereNotNull('stage_changed_at')
            ->select(
                'status',
                DB::raw('COUNT(*) as lead_count'),
                DB::raw('AVG(TIMESTAMPDIFF(DAY, stage_changed_at, NOW())) as avg_days'),
                DB::raw('MAX(TIMESTAMPDIFF(DAY, stage_changed_at, NOW())) as max_days')
            )
            ->groupBy('status')
            ->having('avg_days', '>', 5) // 5 kundan ko'p o'rtacha
            ->get();

        $stages = PipelineStage::where('business_id', $business->id)
            ->get()
            ->keyBy('slug');

        foreach ($stageAnalysis as $analysis) {
            $stage = $stages->get($analysis->status);

            if ($stage) {
                $bottlenecks[] = [
                    'stage' => $stage,
                    'stage_slug' => $analysis->status,
                    'stage_name' => $stage->name,
                    'lead_count' => $analysis->lead_count,
                    'avg_days' => round($analysis->avg_days, 1),
                    'max_days' => (int) $analysis->max_days,
                    'severity' => $this->calculateSeverity($analysis->avg_days, $analysis->lead_count),
                ];
            }
        }

        // Severity bo'yicha tartiblash
        usort($bottlenecks, fn ($a, $b) => $this->severityOrder($b['severity']) <=> $this->severityOrder($a['severity']));

        return $bottlenecks;
    }

    /**
     * Stagnant leads (uzoq vaqt harakatsiz)
     */
    public function getStagnantLeads(Business $business, int $daysThreshold = 7): Collection
    {
        return Lead::where('business_id', $business->id)
            ->whereNotIn('status', ['won', 'lost'])
            ->where(function ($q) use ($daysThreshold) {
                $q->where('stage_changed_at', '<', now()->subDays($daysThreshold))
                    ->orWhere(function ($q2) use ($daysThreshold) {
                        $q2->whereNull('stage_changed_at')
                            ->where('created_at', '<', now()->subDays($daysThreshold));
                    });
            })
            ->with(['assignedTo:id,name'])
            ->orderBy('stage_changed_at')
            ->get()
            ->map(function ($lead) {
                $daysSinceChange = $lead->stage_changed_at
                    ? $lead->stage_changed_at->diffInDays(now())
                    : $lead->created_at->diffInDays(now());

                return [
                    'id' => $lead->id,
                    'name' => $lead->name,
                    'company' => $lead->company,
                    'status' => $lead->status,
                    'assigned_to' => $lead->assignedTo?->name,
                    'days_in_stage' => $daysSinceChange,
                    'is_urgent' => $daysSinceChange >= 14,
                ];
            });
    }

    /**
     * Pipeline statistikasi
     */
    public function getPipelineStats(Business $business): array
    {
        $stages = PipelineStage::where('business_id', $business->id)
            ->ordered()
            ->get();

        $leadCounts = Lead::where('business_id', $business->id)
            ->whereNotNull('status')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [];
        $totalActive = 0;

        foreach ($stages as $stage) {
            $count = $leadCounts->get($stage->slug, 0);

            if (! $stage->is_won && ! $stage->is_lost) {
                $totalActive += $count;
            }

            $stats[] = [
                'slug' => $stage->slug,
                'name' => $stage->name,
                'color' => $stage->color,
                'count' => $count,
                'is_won' => $stage->is_won,
                'is_lost' => $stage->is_lost,
            ];
        }

        return [
            'stages' => $stats,
            'total_active' => $totalActive,
            'total_won' => $leadCounts->get('won', 0),
            'total_lost' => $leadCounts->get('lost', 0),
        ];
    }

    /**
     * Bottleneck alert yaratish
     */
    public function createBottleneckAlert(Business $business, array $bottleneck): void
    {
        $existingAlert = SalesAlert::where('business_id', $business->id)
            ->where('type', 'pipeline_bottleneck')
            ->where('data->stage_slug', $bottleneck['stage_slug'])
            ->where('status', 'unread')
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($existingAlert) {
            return;
        }

        SalesAlert::create([
            'business_id' => $business->id,
            'user_id' => null, // Barcha manager larga ko'rinsin
            'type' => 'pipeline_bottleneck',
            'title' => "Pipeline bottleneck: {$bottleneck['stage_name']}",
            'message' => "{$bottleneck['lead_count']} ta lead '{$bottleneck['stage_name']}' bosqichida o'rtacha {$bottleneck['avg_days']} kun turibdi",
            'priority' => $bottleneck['severity'] === 'critical' ? 'urgent' : 'high',
            'data' => [
                'stage_slug' => $bottleneck['stage_slug'],
                'stage_name' => $bottleneck['stage_name'],
                'lead_count' => $bottleneck['lead_count'],
                'avg_days' => $bottleneck['avg_days'],
                'max_days' => $bottleneck['max_days'],
                'severity' => $bottleneck['severity'],
            ],
        ]);
    }

    /**
     * Severity hisoblash
     */
    protected function calculateSeverity(float $avgDays, int $leadCount): string
    {
        $score = ($avgDays * 2) + ($leadCount * 0.5);

        return match (true) {
            $score >= 30 => 'critical',
            $score >= 20 => 'high',
            $score >= 10 => 'medium',
            default => 'low',
        };
    }

    /**
     * Severity order (sorting uchun)
     */
    protected function severityOrder(string $severity): int
    {
        return match ($severity) {
            'critical' => 4,
            'high' => 3,
            'medium' => 2,
            'low' => 1,
            default => 0,
        };
    }
}
